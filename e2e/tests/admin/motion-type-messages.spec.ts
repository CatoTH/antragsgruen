import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_TYPE } from '../../utils/constants';

test.describe('Admin: MotionTypeMessages', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change a motion type text and reset it', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();

        await test.step('create a new motion type from a template', async () => {
            await page.locator('.motionTypeCreate a').click();

            await page.locator('#typeTitleSingular').first().fill('Bewerbung');
            await page.locator('#typeTitlePlural').first().fill('Bewerbungen');
            await page.locator('#typeCreateTitle').first().fill('Bewirb dich!');
            await page.locator('#typeMotionPrefix').first().fill('B');
            await page.locator('.presetApplication').first().check();
            await page.locator('.motionTypeCreateForm [name="create"]').click();

            await expect(page.locator('body')).toContainText(
                'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
            );
            await expect(page.locator('#typeTitleSingular')).toHaveValue('Bewerbung');
        });

        await test.step('change the motion type text', async () => {
            await page.locator('.motionTypeTranslations').click();
            await expect(page.locator('#string_motion_create_explanation').first()).toBeVisible();
            await page
                .locator('#string_motion_create_explanation')
                .fill('<ul><li>This is how you can create an application:</li>');
            await page.locator('#translationForm [name="save"]').click();
            await expect(page.locator('#string_motion_create_explanation')).toHaveValue(
                '<ul><li>This is how you can create an application:</li></ul>',
            );

            await new ConsultationHomePage(page).open();
        });

        await test.step('see the changed text on the page', async () => {
            await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE} a`).click();
            await expect(page.locator('body')).toContainText('This is how you can create an application:');
            await expect(page.locator('body')).not.toContainText('Antrag oder Änderungsantrag?', { useInnerText: true });

            await new ConsultationHomePage(page).open();
            await page.locator('#sidebar .createMotion1').click();
            await expect(page.locator('body')).not.toContainText('This is how you can create an application:', { useInnerText: true });
            await expect(page.locator('body')).toContainText('Antrag oder Änderungsantrag?');

            await page.locator('#adminLink').click();
        });

        await test.step('reset it to the original text', async () => {
            await page.locator('#translationLink').click();
            await page.locator(`.motionTypeTranslation${FIRST_FREE_MOTION_TYPE}`).click();
            await page.locator('#string_motion_create_explanation').first().fill('');
            await page.locator('#translationForm [name="save"]').click();
            await expect(page.locator('#string_motion_create_explanation')).toHaveValue('');

            await new ConsultationHomePage(page).open();
            await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE} a`).click();
            await expect(page.locator('body')).toContainText('Antrag oder Änderungsantrag?');
        });
    });
});