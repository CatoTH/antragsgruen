import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_MOTION_SECTION,
    FIRST_FREE_MOTION_TYPE,
} from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MotionTypeCreate', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a new motion type from a template', async ({ page }) => {
        await loginAsStdAdmin(page);
        await test.step('create a motion type from a built-in template, inheriting the screening settings', async () => {
            await page.locator('#adminLink').click();

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
            await expect(page.locator('#typeTitlePlural')).toHaveValue('Bewerbungen');
            await expect(page.locator('#typeCreateTitle')).toHaveValue('Bewirb dich!');
            await expect(page.locator('#typeMotionPrefix')).toHaveValue('B');
            await expect(
                page.locator(`.section${FIRST_FREE_MOTION_SECTION} .sectionTitle input`),
            ).toHaveValue('Name');
            await expect(
                page.locator(`.section${FIRST_FREE_MOTION_SECTION + 1} .sectionTitle input`),
            ).toHaveValue('Foto');
        });
    });

    test('create another motion type from existing template', async ({ page }) => {
        await loginAsStdAdmin(page);
        await test.step('create a motion type based on a non-screened motion type, taking over its screening settings', async () => {
            await page.locator('#adminLink').click();
            await page.locator('.motionTypeCreate a').click();

            await page.locator('#typeTitleSingular').first().fill('Abc1');
            await page.locator('#typeTitlePlural').first().fill('Abc2');
            await page.locator('#typeCreateTitle').first().fill('Create type 2');
            await page.locator('#typeMotionPrefix').first().fill('C');
            await page.locator(`.preset${FIRST_FREE_MOTION_TYPE}`).first().check();
            await page.locator('.motionTypeCreateForm [name="create"]').click();

            await expect(page.locator('body')).toContainText(
                'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
            );
            await expect(page.locator('#typeTitleSingular')).toHaveValue('Abc1');
            await expect(page.locator('#typeTitlePlural')).toHaveValue('Abc2');
            await expect(page.locator('#typeCreateTitle')).toHaveValue('Create type 2');
            await expect(page.locator('#typeMotionPrefix')).toHaveValue('C');
            await expect(
                page.locator(`.section${FIRST_FREE_MOTION_SECTION + 5} .sectionTitle input`),
            ).toHaveValue('Name');
            await expect(
                page.locator(`.section${FIRST_FREE_MOTION_SECTION + 6} .sectionTitle input`),
            ).toHaveValue('Foto');
        });
    });

    test('highlight the create link in a big, pink button', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').first().fill('Abc1');
        await page.locator('#typeTitlePlural').first().fill('Abc2');
        await page.locator('#typeCreateTitle').first().fill('Create type 2');
        await page.locator('#typeMotionPrefix').first().fill('C');
        await page.locator(`.preset${FIRST_FREE_MOTION_TYPE}`).first().check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await page.locator('#typeCreateSidebar').first().check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
    });

    test('check if I can see the new types in the sidebar', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await expect(page.locator('#sidebar .createMotionHolder1 .createMotion1').first()).toBeVisible();
        await expect(page.locator('#sidebar .createMotionList .createMotion1').filter({ visible: true })).toHaveCount(0);
        await expect(
            page.locator(`#sidebar .createMotionHolder1 .createMotion${FIRST_FREE_MOTION_TYPE}`),
        ).not.toBeVisible();
        await expect(
            page.locator(`#sidebar .createMotionList .createMotion${FIRST_FREE_MOTION_TYPE}`),
        ).toBeVisible();
        await expect(
            page.locator(
                `#sidebar .createMotionHolder1 .createMotion${FIRST_FREE_MOTION_TYPE + 1}`,
            ),
        ).toBeVisible();
        await expect(
            page.locator(
                `#sidebar .createMotionList .createMotion${FIRST_FREE_MOTION_TYPE + 1}`,
            ),
        ).not.toBeVisible();

        await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE + 1}`).click();
        await expect(page.locator('body')).toContainText('Alter');

        await page.locator('#adminLink').click();
        await expect(page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`).first()).toBeVisible();
        await expect(
            page.locator(`.motionType${FIRST_FREE_MOTION_TYPE + 1}`),
        ).toBeVisible();
    });

    test('delete the first motion type again', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator(`#adminLink`).click();
        await page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`).click();
        await expect(page.locator('.deleteTypeForm').filter({ visible: true })).toHaveCount(0);
        await page.locator('.deleteTypeOpener button').click();
        await expect(page.locator('.deleteTypeOpener').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.deleteTypeForm').first()).toBeVisible();
        await page.locator('.deleteTypeForm [name="delete"]').click();
        await expect(page.locator('body')).toContainText(
            'Der Antragstyp wurde erfolgreich gelöscht.',
        );

        await page.locator('#adminLink').click();
        await expect(
            page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`),
        ).not.toBeVisible();
        await expect(page.locator(`.motionType${FIRST_FREE_MOTION_TYPE + 1}`).first()).toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE}`),
        ).not.toBeVisible();
    });

    test('delete the original motion type - should not work', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await expect(page.locator('.deleteTypeForm').filter({ visible: true })).toHaveCount(0);
        await page.locator('.deleteTypeOpener button').click();
        await expect(page.locator('.deleteTypeOpener').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('body')).toContainText(
            'Dieser Antragstyp kann (noch) nicht gelöscht werden',
        );
    });

    test('create a motion type without template', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').first().fill('Abc1');
        await page.locator('#typeTitlePlural').first().fill('Abc2');
        await page.locator('#typeCreateTitle').first().fill('Abc3');
        await page.locator('#typeMotionPrefix').first().fill('C');
        await page.locator('.presetNone').first().check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await expect(page.locator('body')).toContainText(
            'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
        );
    });

    test('enable screening for the newest motion type', async ({ page }) => {
        await loginAsStdAdmin(page);
        await expect(page.locator('#screeningMotions')).not.toBeChecked();
        await page.locator('#screeningMotions').first().check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await expect(page.locator('#screeningMotions')).toBeChecked();
        await expect(page.locator('#screeningAmendments')).not.toBeChecked();
    });

    test('inherit screening from a built-in template', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').first().fill('Def1');
        await page.locator('#typeTitlePlural').first().fill('Def2');
        await page.locator('#typeCreateTitle').first().fill('Def3');
        await page.locator('#typeMotionPrefix').first().fill('D');
        await page.locator('.presetApplication').first().check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await expect(page.locator('body')).toContainText(
            'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
        );
        await expect(page.locator('#screeningMotions')).toBeChecked();
        await expect(page.locator('#screeningAmendments')).not.toBeChecked();
    });

    test('create from a non-screened motion type does not inherit screening', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionTypeCreate a').click();

        await page.locator('#typeTitleSingular').first().fill('Ghi1');
        await page.locator('#typeTitlePlural').first().fill('Ghi2');
        await page.locator('#typeCreateTitle').first().fill('Ghi3');
        await page.locator('#typeMotionPrefix').first().fill('E');
        await page.locator('.preset1').first().check();
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await expect(page.locator('body')).toContainText(
            'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
        );
        await expect(page.locator('#screeningMotions')).not.toBeChecked();
        await expect(page.locator('#screeningAmendments')).not.toBeChecked();
    });
});