import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick, setCkEditorContent } from '../../utils/dom';
import { FIRST_FREE_MOTION_SECTION } from '../../utils/constants';

test.describe('Manager: create single motion type congress', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create single-motion congress and create a motion', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await loginAsStdAdmin(page);

        await test.step('go to creation form', async () => {
            await expect(page.locator('.siteCreateForm').first()).toBeVisible();
            await page.locator('.siteCreateForm [type="submit"]').click();
        });

        await test.step('click through the wizard', async () => {
            await expect(page.locator('#panelFunctionality')).toContainText(
                'Welche Bestandteile soll die Seite haben?',
            );
            await expect(page.locator('.checkbox-label.value-motion.active').first()).toBeVisible();
            await expect(page.locator('.checkbox-label.value-manifesto.active').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.checkbox-label.value-motion');
            await dispatchClick(page, '.checkbox-label.value-manifesto');
            await page.waitForTimeout(200);
            await expect(page.locator('.checkbox-label.value-motion.active').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.checkbox-label.value-manifesto.active').first()).toBeVisible();
            await page.locator('#panelFunctionality button.btn-next').click();

            await page.locator('#panelSingleMotion .value-1').click();
            await page.locator('#panelSingleMotion button.btn-next').click();

            await page.locator('#panelHasAmendments .value-0').click();
            await page.locator('#panelHasAmendments button.btn-next').click();

            await page.locator('#panelComments .value-1').click();
            await page.locator('#panelComments button.btn-next').click();

            await page.locator('#panelOpenNow .value-1').click();
            await page.locator('#panelOpenNow button.btn-next').click();

            await page.locator('#siteTitle').first().fill('Test-Congress');
            await page.locator('#siteOrganization').first().fill('My party');
            await expect(page.locator('.subdomainError').filter({ visible: true })).toHaveCount(0);
            await page.locator('#siteSubdomain').first().fill('testcongress');
            await page.locator('#siteContact').first().fill('I myself\nMy address');

            await page.locator('form.siteCreate [name="create"]').click();

            await expect(page.locator('body')).toContainText('Die Veranstaltung wurde angelegt.');
            await expect(page.locator('button')).toContainText('Hier kannst du nun den Text eingeben');
        });

        await test.step('create the motion', async () => {
            await page.locator('.createdForm [type="submit"]').click();
            await page.waitForTimeout(1000);

            await expect(page.locator('h1')).toContainText('Kapitel anlegen');
            await page
                .locator(`#sections_${FIRST_FREE_MOTION_SECTION + 0}`)
                .fill('Chapter title');
            const ckField = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
            await setCkEditorContent(page, ckField, '<p>Chapter content</p>');
            await page.locator('#initiatorPrimaryName').first().fill('My name');
            await page.evaluate(() => {
                document.querySelectorAll('[required]').forEach((el) => el.removeAttribute('required'));
            });
            await page.waitForTimeout(1000);

            await page.locator('#motionEditForm [name="save"]').click();
            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await page.goto('/testcongress/testcongress');

            await expect(page.locator('h1')).toContainText('A1: Chapter title');
            await expect(page.locator('body')).toContainText('Chapter content');
            await expect(page.locator('#sidebar .amendmentCreate .onlyAdmins').first()).toBeVisible();
            await expect(page.locator('section.comments').first()).toBeVisible();
        });
    });
});
