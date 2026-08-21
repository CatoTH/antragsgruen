import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { FIRST_FREE_MOTION_SECTION } from '../../utils/constants';

test.describe('Manager: create single motion type congress', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create single-motion congress and create a motion', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await loginAsStdAdmin(page);

        await expect(page.locator('.siteCreateForm')).toBeVisible();
        await page.locator('.siteCreateForm [type="submit"]').click();

        await expect(page.locator('#panelFunctionality')).toContainText(
            'Welche Bestandteile soll die Seite haben?',
        );
        await expect(page.locator('.checkbox-label.value-motion.active')).toBeVisible();
        await expect(page.locator('.checkbox-label.value-manifesto.active')).toHaveCount(0);
        await page.locator('.checkbox-label.value-motion').click();
        await page.locator('.checkbox-label.value-manifesto').click();
        await page.waitForTimeout(200);
        await expect(page.locator('.checkbox-label.value-motion.active')).toHaveCount(0);
        await expect(page.locator('.checkbox-label.value-manifesto.active')).toBeVisible();
        await page.locator('#panelFunctionality button.btn-next').click();

        await page.locator('#panelSingleMotion .value-1').click();
        await page.locator('#panelSingleMotion button.btn-next').click();

        await page.locator('#panelHasAmendments .value-0').click();
        await page.locator('#panelHasAmendments button.btn-next').click();

        await page.locator('#panelComments .value-1').click();
        await page.locator('#panelComments button.btn-next').click();

        await page.locator('#panelOpenNow .value-1').click();
        await page.locator('#panelOpenNow button.btn-next').click();

        await page.locator('#siteTitle').fill('Test-Congress');
        await page.locator('#siteOrganization').fill('My party');
        await expect(page.locator('.subdomainError')).toHaveCount(0);
        await page.locator('#siteSubdomain').fill('testcongress');
        await page.locator('#siteContact').fill('I myself\nMy address');

        await page.locator('form.siteCreate [name="create"]').click();

        await expect(page.locator('body')).toContainText('Die Veranstaltung wurde angelegt.');
        await expect(page.locator('button')).toContainText('Hier kannst du nun den Text eingeben');

        await page.locator('.createdForm [type="submit"]').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('h1')).toContainText('Kapitel anlegen');
        await page
            .locator(`#sections_${FIRST_FREE_MOTION_SECTION + 0}`)
            .fill('Chapter title');
        const ckField = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
        await setCkEditorContent(page, ckField, '<p>Chapter content</p>');
        await page.locator('#initiatorPrimaryName').fill('My name');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach((el) => el.removeAttribute('required'));
        });
        await page.waitForTimeout(1000);

        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await page.goto('/testcongress/testcongress');

        await expect(page.locator('h1')).toContainText('A1: Chapter title');
        await expect(page.locator('body')).toContainText('Chapter content');
        await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins')).toBeVisible();
        await expect(page.locator('section.comments')).toBeVisible();
    });
});
