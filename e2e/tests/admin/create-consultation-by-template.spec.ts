import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_CONSULTATION_ID,
    FIRST_FREE_MOTION_TYPE,
} from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: CreateConsultationByTemplate', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a new consultation and verify cloning', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.siteConsultationsLink').click();

        await expect(page.locator('.consultation1')).toContainText('Standard-Veranstaltung');

        await page.locator('#newTitle').fill('Neue Veranstaltung 1');
        await page.locator('#newShort').fill('NeuKurz');
        await page.locator('#newPath').fill('neukurz');
        await page.locator('#newSetStandard').uncheck();
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();

        await expect(page.locator('body')).toContainText('Die neue Veranstaltung wurde angelegt.');
        await expect(page.locator(`.consultation${FIRST_FREE_CONSULTATION_ID}`)).toContainText(
            'Neue Veranstaltung 1',
        );
        await expect(page.locator('.consultation1')).toContainText('Standard-Veranstaltung');

        await page.locator('#adminLink').click();
        await page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`).click();
        await expect(page.locator('#sectionsList > li')).toHaveCount(5);

        await page.locator('#adminLink').click();
        await page.locator('#userAdministrationLink').click();
        await expect(page.locator('.userList')).toContainText('Single-Consultation Admin');
        await expect(page.locator('.userList')).toContainText('Veranstaltungs-Admin');
        await expect(page.locator('.groupList')).toContainText('Veranstaltungs-Admin');
    });

    test('create the same again, should not work', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.siteConsultationsLink').click();

        await page.locator('#newTitle').fill('Neue Veranstaltung 2');
        await page.locator('#newShort').fill('NeuKurz 2');
        await page.locator('#newPath').fill('neukurz');
        await page.locator('#newSetStandard').uncheck();
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();

        await expect(page.locator('body')).toContainText(
            'Diese Adresse ist leider schon von einer anderen Veranstaltung auf dieser Seite vergeben.',
        );
    });

    test('create a new consultation without taking motion types, users etc.', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.siteConsultationsLink').click();

        await page.locator('#newTitle').fill('Eine leere Veranstaltung');
        await page.locator('#newShort').fill('NeuKurzLeer');
        await page.locator('#newPath').fill('neukurzleer');
        await page.locator('#newSetStandard').uncheck();
        await page.locator("input[name='newConsultation[templateSubselect][]'][value='tags']").uncheck();
        await page
            .locator("input[name='newConsultation[templateSubselect][]'][value='motiontypes']")
            .uncheck();
        await page
            .locator("input[name='newConsultation[templateSubselect][]'][value='texts']")
            .uncheck();
        await page
            .locator("input[name='newConsultation[templateSubselect][]'][value='users']")
            .uncheck();
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();

        await page.locator('#adminLink').click();
        await page.locator('#userAdministrationLink').click();
        await expect(page.locator('.userList')).not.toContainText('Single-Consultation Admin');
        await expect(page.locator('.userList')).not.toContainText('Veranstaltungs-Admin');
        await expect(page.locator('.groupList')).toContainText('Veranstaltungs-Admin');
    });

    test('create a new standard consultation', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.siteConsultationsLink').click();

        await page.locator('#newTitle').fill('Noch eine neue Veranstaltung');
        await page.locator('#newShort').fill('NeuKurz2');
        await page.locator('#newPath').fill('neukurz2');
        await page.locator('#newSetStandard').check();
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();

        await expect(page.locator('body')).toContainText('Die neue Veranstaltung wurde angelegt.');
        await expect(
            page.locator(`.consultation${FIRST_FREE_CONSULTATION_ID + 2}`),
        ).toContainText('Noch eine neue Veranstaltung');
        await expect(page.locator(`.consultation${FIRST_FREE_CONSULTATION_ID + 2}`)).toContainText(
            'Standard-Veranstaltung',
        );

        await page.goto('/stdparteitag');
        await expect(page.locator('h1')).toContainText('Noch eine neue Veranstaltung');
    });

    test('set another consultation as standard', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.siteConsultationsLink').click();

        await dispatchClick(
            page,
            `.consultation${FIRST_FREE_CONSULTATION_ID} .stdbox button`,
        );
        await expect(page.locator('body')).toContainText(
            'Die Veranstaltung wurde als Standard-Veranstaltung festgelegt.',
        );

        await page.goto('/stdparteitag');
        await expect(page.locator('h1')).toContainText('Neue Veranstaltung 1');
    });
});