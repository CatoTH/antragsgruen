import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID, FIRST_FREE_TAG_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Amendments: Tags', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate tags for amendments', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('.multipleTagsGroup').filter({ visible: true })).toHaveCount(0);

        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('#tagsEditForm .editList0').first()).toBeVisible();
        await expect(page.locator('#tagsEditForm .editList2').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.tagTypeSelector input[value="2"]');
        await expect(page.locator('#tagsEditForm .editList0').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#tagsEditForm .editList2').first()).toBeVisible();
        await dispatchClick(page, '#tagsEditForm .adderRow button');
        await page.evaluate(() => {
            const inputs = document.querySelectorAll(
                '#tagsEditForm .editList2 input',
            ) as NodeListOf<HTMLInputElement>;
            const social = inputs[inputs.length - 1]; if (social) social.value = 'Social Issues';
        });
        await dispatchClick(page, '#tagsEditForm .adderRow button');
        await page.evaluate(() => {
            const inputs = document.querySelectorAll(
                '#tagsEditForm .editList2 input',
            ) as NodeListOf<HTMLInputElement>;
            const env = inputs[inputs.length - 1]; if (env) env.value = 'Environmental Issues';
        });
        await dispatchClick(page, '#tagsEditForm .adderRow button');
        await page.evaluate(() => {
            const inputs = document.querySelectorAll(
                '#tagsEditForm .editList2 input',
            ) as NodeListOf<HTMLInputElement>;
            const med = inputs[inputs.length - 1]; if (med) med.value = 'Medical Issues';
        });

        await page.locator('#allowMultipleTags').first().check();
        await page.locator('#consultationSettingsForm [name="save"]').click();

        await dispatchClick(page, '.tagTypeSelector input[value="2"]');
    });

    test('Create an amendment with a tag', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('.multipleTagsGroup')).toContainText('Social Issues');
        await page
            .locator(`input[name='tags[]'][value='${FIRST_FREE_TAG_ID}']`)
            .check();

        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator("[name='sections[1]']").first().fill('Test');
        await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
        await page.locator('#initiatorEmail').first().fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
    });

    test('Confirm the tag is visible', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentView(FIRST_FREE_AMENDMENT_ID);
        await expect(page.locator('.motionDataTable .tags')).toContainText('Social Issues');

        await page.locator('#motionListLink').click();
        await expect(
            page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID} .tagsCol`),
        ).toContainText('Social Issues');
    });
});