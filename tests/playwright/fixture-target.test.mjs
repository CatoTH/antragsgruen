import { test } from 'node:test';
import assert from 'node:assert/strict';
import { getFixtureBaseURL } from './fixture-target.mjs';

const targetError = { name: 'Error', message: /ANTRAGSGRUEN_PLAYWRIGHT_URL.*127\.0\.0\.1/ };

test('returns an explicit loopback origin unchanged', () => {
  assert.equal(getFixtureBaseURL('http://127.0.0.1:43123'), 'http://127.0.0.1:43123');
});

test('missing value throws an actionable error', () => {
  for (const missing of [undefined, '']) {
    assert.throws(() => getFixtureBaseURL(missing), targetError);
  }
});

test('invalid or non-loopback target throws an actionable error', () => {
  const invalidTargets = [
    'http://example.com:43123',
    'http://192.0.2.10:43123',
    'http://127.0.0.1@example.com:43123',
    'file://127.0.0.1/tmp/fixture',
    'not a URL',
  ];

  for (const target of invalidTargets) {
    assert.throws(() => getFixtureBaseURL(target), targetError);
  }
});
