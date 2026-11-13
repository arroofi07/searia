import { formatSwimTime, parseSwimTime } from './swim-time-input.js';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const board = document.getElementById('heat-result-board');
if (!board) {
  // not on heat result page
} else {
  const fastInput = board.dataset.fastInput === '1';
  const locked = board.dataset.locked === '1';
  const pending = new Set();

  function setState(row, state, message = '') {
    row.dataset.saveState = state;
    const indicator = row.querySelector('[data-save-indicator]');
    if (!indicator) return;

    const labels = {
      idle: '—',
      dirty: 'Belum disimpan',
      saving: 'Menyimpan…',
      saved: 'Tersimpan',
      error: message || 'Gagal',
    };
    indicator.textContent = labels[state] || state;
    indicator.className = 'text-xs ' + ({
      idle: 'text-slate-400',
      dirty: 'text-amber-700',
      saving: 'text-slate-600',
      saved: 'text-teal-700',
      error: 'text-red-700',
    }[state] || 'text-slate-400');
  }

  function syncTimeEnabled(row) {
    const status = row.querySelector('[data-status-input]')?.value;
    const timeInput = row.querySelector('[data-time-input]');
    const dsqInput = row.querySelector('[data-dsq-input]');
    if (!timeInput || !dsqInput) return;

    const needsTime = status === 'ok';
    const needsDsq = status === 'dsq';
    const dsqWrap = row.querySelector('[data-dsq-wrap]');
    const timeWrap = row.querySelector('[data-time-wrap]');

    timeInput.disabled = locked || !needsTime;
    dsqInput.disabled = locked || !needsDsq;
    dsqWrap?.classList.toggle('hidden', !needsDsq);
    timeWrap?.classList.toggle('opacity-50', !needsTime);

    if (!needsTime) {
      timeInput.value = '';
      const preview = row.querySelector('[data-time-preview]');
      if (preview) preview.textContent = '—';
    }
  }

  function parsedTime(value) {
    try {
      return parseSwimTime(value, fastInput);
    } catch {
      return undefined;
    }
  }

  function updatePreview(row) {
    const timeInput = row.querySelector('[data-time-input]');
    const preview = row.querySelector('[data-time-preview]');
    if (!timeInput || !preview) return;

    const parsed = parsedTime(timeInput.value);
    if (parsed === undefined) {
      preview.textContent = 'format salah';
      return;
    }

    preview.textContent = parsed == null
      ? (timeInput.value.trim() === '' ? '—' : 'NT')
      : formatSwimTime(parsed);
  }

  function syncRecordedProgress(fullyRecorded) {
    const rows = [...board.querySelectorAll('[data-lane-row][data-lane-id]')];
    const saved = rows.filter((row) => row.dataset.saveState === 'saved').length;
    const badge = document.querySelector('[data-recorded-count]');
    if (badge) {
      badge.textContent = `${saved}/${rows.length} lintasan tercatat`;
    }

    const lockButton = document.getElementById('lock-heat-button');
    const lockHint = document.querySelector('[data-lock-hint]');
    if (!lockButton) return;

    const unsettled = rows.some((row) => ['dirty', 'saving', 'error'].includes(row.dataset.saveState ?? ''));
    const ready = rows.length > 0 && !unsettled && (fullyRecorded || saved === rows.length);

    lockButton.disabled = !ready;
    if (lockHint) {
      lockHint.textContent = ready
        ? 'Semua lintasan terisi sudah tercatat. Kunci seri jika yakin hasil benar.'
        : 'Tombol kunci aktif setelah semua lintasan terisi punya hasil (waktu atau DNS/DNF/DSQ).';
    }
  }

  async function saveRow(row, attempt = 0) {
    if (locked) return;

    const url = row.dataset.saveUrl;
    const status = row.querySelector('[data-status-input]')?.value;
    const time = row.querySelector('[data-time-input]')?.value ?? '';
    const dsqCode = row.querySelector('[data-dsq-input]')?.value ?? '';

    if (!url || !status) return;

    let timeToSend = time;
    if (status === 'ok') {
      if (time.trim() === '') {
        setState(row, 'dirty');
        return;
      }

      const parsed = parsedTime(time);
      if (parsed == null) {
        setState(row, 'error', parsed === undefined ? 'Format waktu tidak valid' : 'Waktu tidak sah');
        updatePreview(row);
        return;
      }

      timeToSend = formatSwimTime(parsed);
      const timeInput = row.querySelector('[data-time-input]');
      if (timeInput) timeInput.value = timeToSend;
      updatePreview(row);
    }

    if (status === 'dsq' && dsqCode === '') {
      setState(row, 'error', 'Pilih alasan diskualifikasi');
      return;
    }

    setState(row, 'saving');
    pending.add(row);

    try {
      const response = await fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          status,
          time: status === 'ok' ? timeToSend : null,
          dsq_code: status === 'dsq' ? dsqCode : null,
        }),
      });

      if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        const error = new Error(payload.message || 'Gagal menyimpan');
        error.status = response.status;
        throw error;
      }

      const payload = await response.json();
      setState(row, 'saved');
      pending.delete(row);

      if (status === 'ok' && payload.result?.time_formatted) {
        const timeInput = row.querySelector('[data-time-input]');
        if (timeInput) timeInput.value = payload.result.time_formatted;
        updatePreview(row);
      }

      syncRecordedProgress(Boolean(payload.heat_fully_recorded));
    } catch (error) {
      setState(row, 'error', error.message);
      pending.delete(row);
      syncRecordedProgress(false);
      if (attempt < 4 && (error.status == null || error.status >= 500)) {
        const delay = Math.min(1000 * 2 ** attempt, 8000);
        setTimeout(() => saveRow(row, attempt + 1), delay);
      }
    }
  }

  board.querySelectorAll('[data-lane-row][data-lane-id]').forEach((row) => {
    syncTimeEnabled(row);
    updatePreview(row);
    if (row.dataset.saveState === 'saved') {
      setState(row, 'saved');
    } else if (!locked && (row.querySelector('[data-time-input]')?.value ?? '').trim() !== '') {
      saveRow(row);
    }

    row.querySelector('[data-status-input]')?.addEventListener('change', () => {
      syncTimeEnabled(row);
      setState(row, 'dirty');
      if (row.querySelector('[data-status-input]')?.value === 'dsq') {
        row.querySelector('[data-dsq-input]')?.focus();
      }
      saveRow(row);
    });

    row.querySelector('[data-dsq-input]')?.addEventListener('change', () => {
      setState(row, 'dirty');
      saveRow(row);
    });

    const timeInput = row.querySelector('[data-time-input]');
    timeInput?.addEventListener('input', () => {
      updatePreview(row);
      setState(row, 'dirty');
    });
    timeInput?.addEventListener('blur', () => saveRow(row));
    timeInput?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      saveRow(row);
      const rows = [...board.querySelectorAll('[data-lane-row][data-lane-id] [data-time-input]:not(:disabled)')];
      const index = rows.indexOf(event.target);
      if (index >= 0 && index < rows.length - 1) {
        rows[index + 1].focus();
      }
    });
  });

  window.addEventListener('beforeunload', (event) => {
    const dirty = [...board.querySelectorAll('[data-lane-row]')].some((row) =>
      ['dirty', 'saving', 'error'].includes(row.dataset.saveState)
    );
    if (dirty || pending.size > 0) {
      event.preventDefault();
      event.returnValue = '';
    }
  });
}
