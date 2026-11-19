import { test, expect } from '@playwright/test';

// Uses the same standard fixture as the two legacy GotoPrefixCept.php tests.

test('motion prefix A2 opens the canonical motion page (stdparteitag)', async ({ page, baseURL }) => {
  const response = await page.goto('/stdparteitag/std-parteitag/A2');

  expect(response?.status()).toBe(200);
  expect(page.url()).toBe(new URL('/stdparteitag/std-parteitag/motion/321-o-zapft-is', baseURL).href);
  await expect(page.getByText('Wui helfgod Wiesn').filter({ visible: true })).toBeVisible();
});

test('amendment prefix A2/Ä5 opens the canonical amendment page (stdparteitag)', async ({ page, baseURL }) => {
  const response = await page.goto('/stdparteitag/std-parteitag/A2/%C3%845');

  expect(response?.status()).toBe(200);
  expect(page.url()).toBe(new URL('/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/273', baseURL).href);
  await expect(page.getByText('und irgendw').filter({ visible: true })).toBeVisible();
});

test('motion prefix F-01 opens the canonical motion page (laenderrat-to)', async ({ page, baseURL }) => {
  const response = await page.goto('/laenderrat-to/laenderrat-to/F-01');

  expect(response?.status()).toBe(200);
  expect(page.url()).toBe(new URL('/laenderrat-to/laenderrat-to/motion/62', baseURL).href);
  await expect(page.getByRole('heading', { level: 1, name: /F-01/ })).toBeVisible();
});

test('amendment prefix Z-01-224-1 opens the canonical amendment page (laenderrat-to)', async ({ page, baseURL }) => {
  const response = await page.goto('/laenderrat-to/laenderrat-to/Z-01-224-1');

  expect(response?.status()).toBe(200);
  expect(page.url()).toBe(new URL('/laenderrat-to/laenderrat-to/motion/64/amendment/182', baseURL).href);
  await expect(page.getByRole('heading', { level: 1, name: /Z-01-224-1/ })).toBeVisible();
});

test('unknown motion prefix redirects to the consultation home', async ({ page, baseURL }) => {
  const response = await page.goto('/stdparteitag/std-parteitag/ZZ-MISSING-PREFIX-987');

  expect(response?.status()).toBe(200);
  expect(page.url()).toBe(new URL('/stdparteitag/std-parteitag', baseURL).href);
  await expect(page.getByRole('heading', { level: 1, name: /Test2/ })).toBeVisible();
});

test('unknown amendment prefix under a valid motion stays at the requested path (HTTP 404)', async ({ page, baseURL }) => {
  const response = await page.goto('/stdparteitag/std-parteitag/A2/ZZ-MISSING-PREFIX-987');

  expect(response?.status()).toBe(404);
  expect(page.url()).toBe(new URL('/stdparteitag/std-parteitag/A2/ZZ-MISSING-PREFIX-987', baseURL).href);
});
