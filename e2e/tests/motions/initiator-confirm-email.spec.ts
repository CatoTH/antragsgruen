import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID, FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { AdminMotionPage } from '../../pages/AdminMotionPage';
import { AdminAmendmentPage } from '../../pages/AdminAmendmentPage';

const TYPE_NOTIFICATION_ADMIN = 3;
const TYPE_SUBMIT_CONFIRM = 8;

test.describe('Initiator confirmation emails', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('confirmation mails are only sent when the initiator submits themselves', async ({
        page,
    }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });
        await page.locator('#screeningMotions').check();
        await page.locator('#screeningAmendments').check();
        await motionType.saveForm();

        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await page.locator('#initiatorConfirmEmails').check();
        await consultation.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        let createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData('Testantrag1');
        await page.locator("input[name='otherInitiator']").uncheck();
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: testadmin@example.org (Type ${TYPE_NOTIFICATION_ADMIN})`,
        );
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: test@example.org (Type ${TYPE_SUBMIT_CONFIRM})`,
        );

        await home.open();
        createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData('Testantrag1');
        await expect(page.locator("input[name='otherInitiator']")).toBeChecked();
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: testadmin@example.org (Type ${TYPE_NOTIFICATION_ADMIN})`,
        );
        await expect(page.locator('body')).not.toContainText('E-Mail sent to: test@example.org');
    });

    test('amendment confirmation mails follow the same rule', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });
        await page.locator('#screeningMotions').check();
        await page.locator('#screeningAmendments').check();
        await motionType.saveForm();

        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await page.locator('#initiatorConfirmEmails').check();
        await consultation.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoAmendmentCreatePage();
        await page.locator("input[name='otherInitiator']").uncheck();
        await page.locator('#sections_1').fill('New title');
        await page.locator('#initiatorPrimaryName').fill('My Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: testadmin@example.org (Type ${TYPE_NOTIFICATION_ADMIN})`,
        );
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: test@example.org (Type ${TYPE_SUBMIT_CONFIRM})`,
        );

        await home.open();
        await home.gotoAmendmentCreatePage();
        await expect(page.locator("input[name='otherInitiator']")).toBeChecked();
        await page.locator('#sections_1').fill('New title');
        await page.locator('#initiatorPrimaryName').fill('My Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: testadmin@example.org (Type ${TYPE_NOTIFICATION_ADMIN})`,
        );
        await expect(page.locator('body')).not.toContainText('E-Mail sent to: test@example.org');
    });

    test('screening sends the confirmation mail to the initiator', async ({ page }) => {
        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await loginAsStdAdmin(page);
        await motionType.open({ motionTypeId: 1 });
        await page.locator('#screeningMotions').check();
        await page.locator('#screeningAmendments').check();
        await motionType.saveForm();

        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await page.locator('#initiatorConfirmEmails').check();
        await consultation.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData('Testantrag1');
        await page.locator("input[name='otherInitiator']").uncheck();
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await home.open();
        await home.gotoAmendmentCreatePage();
        await page.locator("input[name='otherInitiator']").uncheck();
        await page.locator('#sections_1').fill('New title');
        await page.locator('#initiatorPrimaryName').fill('My Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        const adminMotion = new AdminMotionPage(page);
        await adminMotion.open({ motionId: FIRST_FREE_MOTION_ID });
        await page.locator('#motionScreenForm [name="screen"]').click();
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: test@example.org (Type ${TYPE_SUBMIT_CONFIRM})`,
        );

        const adminAmendment = new AdminAmendmentPage(page);
        await adminAmendment.open({ amendmentId: FIRST_FREE_AMENDMENT_ID });
        await page.locator('#amendmentScreenForm [name="screen"]').click();
        await expect(page.locator('body')).toContainText(
            `E-Mail sent to: test@example.org (Type ${TYPE_SUBMIT_CONFIRM})`,
        );
    });
});
