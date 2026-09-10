import { formatSwimTime, parseSwimTime } from './swim-time-input.js';

function initPublicNav() {
  const toggle = document.querySelector('[data-public-nav-toggle]');
  const panel = document.querySelector('[data-public-nav-panel]');

  if (!toggle || !panel) {
    return;
  }

  toggle.addEventListener('click', () => {
    const open = panel.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('overflow-hidden', open);
  });

  panel.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      panel.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('overflow-hidden');
    });
  });
}

function pad2(value) {
  return String(value).padStart(2, '0');
}

function partsFromMs(milliseconds) {
  const ms = Number(milliseconds);
  const hours = Math.floor(ms / 3_600_000);
  let remain = ms % 3_600_000;
  const minutes = Math.floor(remain / 60_000);
  remain %= 60_000;
  const seconds = Math.floor(remain / 1000);
  const hundredths = Math.floor((remain % 1000) / 10);

  return { hours, minutes, seconds, hundredths };
}

function spokenTime(milliseconds) {
  if (milliseconds == null) {
    return 'Tidak ada catatan waktu (NT). Atlet akan diletakkan di seri belakang.';
  }

  const { hours, minutes, seconds, hundredths } = partsFromMs(milliseconds);
  const detik = `${seconds},${pad2(hundredths)} detik`;

  if (hours > 0) {
    return `${hours} jam ${minutes} menit ${detik}`;
  }

  if (minutes === 0) {
    return detik;
  }

  return `${minutes} menit ${detik}`;
}

function boxesFromMs(milliseconds) {
  if (milliseconds == null) {
    return ['', '', '', '', '', ''];
  }

  const { minutes, seconds, hundredths } = partsFromMs(milliseconds);
  const digits = `${pad2(minutes)}${pad2(seconds)}${pad2(hundredths)}`;

  return digits.split('');
}

function renderSeedBoxes(root, digits) {
  if (!root) {
    return;
  }

  root.querySelectorAll('[data-seed-digit]').forEach((el, index) => {
    el.textContent = digits[index] || '·';
    el.classList.toggle('filled', Boolean(digits[index]));
  });
}

function previewSeed(input) {
  const wrap = input.closest('[data-seed-field]') ?? input.parentElement;
  const target = wrap?.querySelector('[data-seed-preview]');
  const boxes = wrap?.querySelector('[data-seed-boxes]');
  const raw = input.value.trim();

  if (!target) {
    return;
  }

  if (raw === '') {
    target.textContent = input.dataset.emptyPreview || 'Kosong = NT (belum punya catatan waktu).';
    target.dataset.state = 'empty';
    renderSeedBoxes(boxes, ['', '', '', '', '', '']);
    return;
  }

  try {
    const ms = parseSwimTime(raw, true);
    const formatted = formatSwimTime(ms);
    target.textContent = ms == null
      ? `Terbaca NT — ${spokenTime(null)}`
      : `Terbaca ${formatted} — ${spokenTime(ms)}`;
    target.dataset.state = ms == null ? 'empty' : 'ok';
    renderSeedBoxes(boxes, boxesFromMs(ms));
  } catch {
    target.textContent = 'Format belum dikenali. Ketik 6 angka, misalnya 013470.';
    target.dataset.state = 'error';
  }
}

function initSeedFields(root = document) {
  root.querySelectorAll('[data-seed-input]').forEach((input) => {
    const run = () => previewSeed(input);
    input.addEventListener('input', run);
    input.addEventListener('focus', run);
    run();
  });
}

function initEventForm() {
  const form = document.getElementById('event-form');

  if (!form) {
    return;
  }

  const max = Number(form.dataset.maxEvents);
  const used = Number(form.dataset.usedEvents);
  const boxes = [...form.querySelectorAll('.event-box')];
  const quota = document.getElementById('quota');
  const quotaBar = document.getElementById('quota-bar');

  function syncCard(box) {
    const card = box.closest('[data-event-card]');
    const input = card?.querySelector('[data-seed-input]');
    const field = card?.querySelector('[data-seed-field]');

    card?.classList.toggle('selected', box.checked);

    if (input && field) {
      input.disabled = !box.checked;
      field.hidden = !box.checked;

      if (!box.checked) {
        input.value = '';
        previewSeed(input);
      } else if (!input.value && input.dataset.suggest) {
        input.value = input.dataset.suggest;
        previewSeed(input);
      }
    }
  }

  function refreshQuota() {
    const selected = boxes.filter((box) => box.checked).length;
    const total = used + selected;
    const text = `Terpakai ${total} dari ${max} nomor yang diizinkan.`;

    if (quota) {
      quota.textContent = text;
    }

    if (quotaBar) {
      quotaBar.textContent = text;
    }
  }

  boxes.forEach((box) => {
    syncCard(box);
    box.addEventListener('change', () => {
      const selected = boxes.filter((item) => item.checked).length;

      if (used + selected > max) {
        box.checked = false;
        window.alert(`Maksimal ${max} nomor per atlet. Lepas centang nomor lain dulu jika ingin mengganti.`);
      }

      syncCard(box);
      refreshQuota();
    });
  });

  refreshQuota();
}

document.addEventListener('DOMContentLoaded', () => {
  initPublicNav();
  initEventForm();
  initSeedFields();
});
