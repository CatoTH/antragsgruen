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

        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').fill('Bewerbung');
        await page.locator('#typeTitlePlural').fill('Bewerbungen');
        await page.locator('#typeCreateTitle').fill('Bewirb dich!');
        await page.locator('#typeMotionPrefix').fill('B');
        await page.locator('.presetApplication').check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await expect(page.locator('body')).toContainText(
            'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
        );
        await expect(page.locator('#typeTitleSingular')).toHaveValue('Bewerbung');

        await page.locator('.motionTypeTranslations').click();
        await expect(page.locator('#string_motion_create_explanation')).toBeVisible();
        await page
            .locator('#string_motion_create_explanation')
            .fill('<ul><li>This is how you can create an application:</li>');
        await page.locator('#translationForm [name="save"]').click();
        await expect(page.locator('#string_motion_create_explanation')).toHaveValue(
            '<ul><li>This is how you can create an application:</li></ul>',
        );

        await new ConsultationHomePage(page).open();
        await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE} a`).click();
        await expect(page.locator('body')).toContainText('This is how you can create an application:');
        await expect(page.locator('body')).not.toContainText('Antrag oder Änderungsantrag?');

        await new ConsultationHomePage(page).open();
        await page.locator('#sidebar .createMotion1').click();
        await expect(page.locator('body')).not.toContainText('This is how you can create an application:');
        await expect(page.locator('body')).toContainText('Antrag oder Änderungsantrag?');

        await page.locator('#adminLink').click();
        await page.locator('#translationLink').click();
        await page.locator(`.motionTypeTranslation${FIRST_FREE_MOTION_TYPE}`).click();
        await page.locator('#string_motion_create_explanation').fill('');
        await page.locator('#translationForm [name="save"]').click();
        await expect(page.locator('#string_motion_create_explanation')).toHaveValue('');

        await new ConsultationHomePage(page).open();
        await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE} a`).click();
        await expect(page.locator('body')).toContainText('Antrag oder Änderungsantrag?');
    });
});