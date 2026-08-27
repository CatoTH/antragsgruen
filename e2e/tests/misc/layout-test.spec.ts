import { test, expect } from '../../fixtures';
import { validateHTML, validatePa11y } from '../../utils/validators';

const ACCEPTED_HTML_ERRORS = [
    'Bad value "popup" for attribute "rel"',
    'Attribute "value" not allowed on element "li" at this point',
    'CKEDITOR',
    'autocomplete',
];

test.describe('Misc: layout (HTML/Pa11y)', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('home page layout', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await validatePa11y(page);
        await validateHTML(page);

        await expect(page.locator('.breadcrumb')).toContainText('Test2');
        await expect(page.locator('.breadcrumb')).toContainText('Antrag');
        await expect(page.locator('.breadcrumb').getByText('HoesslTo').filter({ visible: true })).toHaveCount(0);
    });

    test('motion create page layout', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .createMotion, #sidebar .createMotionBtn').first().click();
        await validateHTML(page, ACCEPTED_HTML_ERRORS);
        await validatePa11y(page);
    });

    test('motion view layout', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();
        await validateHTML(page, ACCEPTED_HTML_ERRORS);
        await validatePa11y(page);
    });

    test('amendment view layout', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();
        await page.locator('.amendment3, .amendment3 a').first().click();
        await page.locator('.motionData').waitFor();
        await validateHTML(page, ACCEPTED_HTML_ERRORS);
        await validatePa11y(page);
    });

    test('login page layout', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#loginLink').click();
        await validateHTML(page);
        await validatePa11y(page);
    });

    test('manager start page layout', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await validateHTML(page);
        await validatePa11y(page);
    });
});
