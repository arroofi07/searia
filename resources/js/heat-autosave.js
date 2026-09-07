import { formatSwimTime, parseSwimTime } from './swim-time-input';

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

    timeInput.disabled = locked || !needsTime;
    dsqInput.disabled = locked || !needsDsq;

    if (!needsTime) {
      timeInput.value = '';
      const preview = row.querySelector('[data-time-preview]');
      if (preview) preview.textContent = '—';
    }
  }

  function updatePreview(row) {
    const timeInput = row.querySelector('[data-time-input]');
    const preview = row.querySelector('[data-time-preview]');
    if (!timeInput || !preview) return;

    try {
      const ms = parseSwimTime(timeInput.value, fastInput);
      preview.textContent = ms == null ? (timeInput.value.trim() === '' ? '—' : 'NT') : formatSwimTime(ms);
    } catch {
      preview.textContent = 'format salah';
    }
  }

  async function saveRow(row, attempt = 0) {
    if (locked) return;

    const url = row.dataset.saveUrl;
    const status = row.querySelector('[data-status-input]')?.value;
    const time = row.querySelector('[data-time-input]')?.value ?? '';
    const dsqCode = row.querySelector('[data-dsq-input]')?.value ?? '';

    if (!url || !status) return;
    if (status === 'ok' && time.trim() === '') {
      setState(row, 'dirty');
      return;
    }
    if (status === 'dsq' && dsqCode === '') {
      setState(row, 'error', 'Kode DSQ wajib');
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
          time: status === 'ok' ? time : null,
          dsq_code: status === 'dsq' ? dsqCode : null,
        }),
      });

      if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.message || 'Gagal menyimpan');
      }

      const payload = await response.json();
      setState(row, 'saved');
      pending.delete(row);

      const lockButton = document.getElementById('lock-heat-button');
      if (lockButton && payload.heat_fully_recorded) {
        lockButton.disabled = false;
      }
    } catch (error) {
      setState(row, 'error', error.message);
      pending.delete(row);
      if (attempt < 4) {
        const delay = Math.min(1000 * 2 ** attempt, 8000);
        setTimeout(() => saveRow(row, attempt + 1), delay);
      }
    }
  }

  board.querySelectorAll('[data-lane-row][data-lane-id]').forEach((row) => {
    syncTimeEnabled(row);
    if (row.dataset.saveState === 'saved') {
      setState(row, 'saved');
    }

    row.querySelector('[data-status-input]')?.addEventListener('change', () => {
      syncTimeEnabled(row);
      setState(row, 'dirty');
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
