import test from 'node:test';
import assert from 'node:assert/strict';
import { formatSwimTime, parseSwimTime } from '../../resources/js/swim-time-input.js';

const cases = [
  ['5220', 52200],
  ['3470', 34700],
  ['13470', 94700],
  ['013470', 94700],
  ['52.20', 52200],
  ['1:34.70', 94700],
  ['NT', null],
];

for (const [input, expected] of cases) {
  test(`parseSwimTime(${input})`, () => {
    assert.equal(parseSwimTime(input, true), expected);
  });
}

test('formatSwimTime mirrors php display', () => {
  assert.equal(formatSwimTime(52200), '00:52.20');
  assert.equal(formatSwimTime(94700), '01:34.70');
});
