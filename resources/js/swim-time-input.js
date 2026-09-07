/**
 * Client-side swim time parser mirroring App\Support\SwimTime.
 */

export function parseSwimTime(input, fastInput = true) {
  if (input == null) {
    return null;
  }

  const trimmed = String(input).trim();
  if (trimmed === '') {
    return null;
  }

  const normalized = trimmed.replace(',', '.');
  const token = normalized.toUpperCase();

  if (['NT', '-', '99:99:99'].includes(token)) {
    return null;
  }

  if (fastInput && /^\d{4,6}$/.test(normalized)) {
    return parseFastDigits(normalized);
  }

  if (/^\d+([.]\d{1,3})?$/.test(normalized)) {
    return secondsToMs(normalized);
  }

  if (/^\d{1,2}:\d{1,2}([.]\d{1,3})?$/.test(normalized)) {
    const [minutes, seconds] = normalized.split(':', 2);
    return Number(minutes) * 60_000 + secondsToMs(seconds);
  }

  if (/^\d{1,2}:\d{1,2}:\d{1,2}([.]\d{1,3})?$/.test(normalized)) {
    const [hours, minutes, seconds] = normalized.split(':', 3);
    return Number(hours) * 3_600_000 + Number(minutes) * 60_000 + secondsToMs(seconds);
  }

  throw new Error('Format waktu tidak valid');
}

export function formatSwimTime(milliseconds, noTime = 'NT') {
  if (milliseconds == null) {
    return noTime;
  }

  const ms = Number(milliseconds);
  const hours = Math.floor(ms / 3_600_000);
  let remain = ms % 3_600_000;
  const minutes = Math.floor(remain / 60_000);
  remain %= 60_000;
  const seconds = Math.floor(remain / 1000);
  const hundredths = Math.floor((remain % 1000) / 10);

  const pad = (n) => String(n).padStart(2, '0');

  if (hours > 0) {
    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}.${pad(hundredths)}`;
  }

  return `${pad(minutes)}:${pad(seconds)}.${pad(hundredths)}`;
}

function parseFastDigits(digits) {
  const length = digits.length;

  if (length === 4) {
    return secondsToMs(`${digits.slice(0, 2)}.${digits.slice(2, 4)}`);
  }

  if (length === 5) {
    return Number(digits[0]) * 60_000 + secondsToMs(`${digits.slice(1, 3)}.${digits.slice(3, 5)}`);
  }

  return Number(digits.slice(0, 2)) * 60_000 + secondsToMs(`${digits.slice(2, 4)}.${digits.slice(4, 6)}`);
}

function secondsToMs(seconds) {
  const [whole, fraction = '00'] = seconds.split('.', 2);
  const hundredths = (fraction + '00').slice(0, 2);

  return Number(whole) * 1000 + Number(hundredths) * 10;
}
