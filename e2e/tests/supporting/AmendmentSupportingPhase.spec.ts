import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminMotionPage } from '../../pages/AdminMotionPage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';
import { MotionPage } from '../../pages/MotionPage';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

const SUBDOMAIN = 'supporter';
const CONSULTATION = 'supporter';

test.describe('Supporting: AmendmentSupportingPhase', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('amendment supporting phase flow', async ({ page }) => {
        await new ConsultationHomePage(page).open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
        });
        await expect(page.locator('#sidebar .collecting')).toHaveCount(0);

        const motionPage = new MotionPage(page);
        await motionPage.open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
            motionSlug: 116,
        });
        await expect(page.locator('#sidebar .amendmentCreate a').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        await expect(page.locator('#sidebar .amendmentCreate a').first()).toBeVisible();
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#motionStatus').first().selectOption('15');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await new AdminIndexPage(page).open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
        });
        const appearancePage = new AdminAppearancePage(page);
        await appearancePage.open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
        });
        await test.step('activate the collecting page', async () => {
            await page.locator('#collectingPage').first().check();
            await appearancePage.saveForm();

            await logout(page);

            await new ConsultationHomePage(page).open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION,
            });
            await loginAsStdUser(page);
            await motionPage.open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION,
                motionSlug: 116,
            });
        });

        await test.step('check that amendments created as normal person are in supporting phase', async () => {
            await page.locator('#sidebar .amendmentCreate a').click();
            await page.locator('#sections_30').first().fill('New title');
            await page.locator('#amendmentEditForm [name="save"]').click();
            await page.locator('#amendmentConfirmForm [name="confirm"]').click();

            await expect(page.locator('body')).toContainText('benötigt dieser mindestens 1 Unterstützer*innen.');

            await new ConsultationHomePage(page).open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION,
            });
            await expect(page.locator('.myAmendmentList')).toContainText('Unterstützer*innen sammeln');
            await expect(page.locator('.myAmendmentList').getByText('Eingereicht (ungeprüft)').filter({ visible: true })).toHaveCount(0);

            await page.locator('#sidebar .collecting a').click();
            await expect(page.locator('.motionList')).toContainText('ÄA von Testuser, ab Zeile 1');
            await expect(page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`)).toContainText(
                'Aktueller Stand: 0 / 1',
            );

            await motionPage.open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION,
                motionSlug: 116,
            });
        });

        await page.locator('#sidebar .amendmentCreate a').click();
        await page.locator('#sections_30').first().fill('Title as organization');
        await page.locator('#personTypeOrga').first().check();
        await page.locator('#initiatorPrimaryName').first().fill('My orga name');
        await page.locator('#resolutionDate').first().fill('01.01.2016');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            'Du hast den Änderungsantrag eingereicht. Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.',
        );

        await new ConsultationHomePage(page).open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
        });
        await expect(page.locator('.myAmendmentList')).toContainText('Eingereicht (ungeprüft)');

        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
        });
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
            motionTypeId: 10,
        });
        await expect(page.locator('#typeMinSupporters')).toHaveValue('1');
        await page.locator('#typeSupportType').first().selectOption('1');
        await expect(page.locator('#typeMinSupporters').filter({ visible: true })).toHaveCount(0);
        await page.locator('#typeSupportType').first().selectOption('2');
        await expect(page.locator('#typeMinSupporters').first()).toBeVisible();

        await page.locator('#policyFixForm [name="supportCollPolicyFix"]').click();

        await logout(page);

        const amendmentUrl = page.url();
        const amendmentUrlParts = amendmentUrl.split('/');
        const amendmentUrlPath = amendmentUrlParts.slice(3).join('/');

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/${amendmentUrlPath}`);
        await test.step('enable/disable liking and disliking', async () => {
            await expect(page.locator('body')).toContainText('Dieser Änderungsantrag ist noch nicht eingereicht.');
            await expect(page.locator('body')).toContainText('Du musst dich einloggen, um Anträge unterstützen zu können.');
            await expect(page.locator('button[name=motionSupport]').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.likes').filter({ visible: true })).toHaveCount(0);

            await loginAsStdAdmin(page);
            await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/${amendmentUrlPath}`);
            await expect(page.locator('button[name=motionSupport]').first()).toBeVisible();
            await expect(page.locator('button[name=motionLike]').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('button[name=motionDislike]').filter({ visible: true })).toHaveCount(0);

            await new AdminIndexPage(page).open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION,
            });
            await motionTypePage.open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION,
                motionTypeId: 10,
            });
            await expect(page.locator('.amendmentDislike')).not.toBeChecked();
            await expect(page.locator('.amendmentLike')).not.toBeChecked();
            await page.locator('.amendmentLike').first().check();
            await page.locator('.amendmentDislike').first().check();
            await page.locator('#typeHasOrga').first().check();
            await page.locator('.adminTypeForm [name="save"]').click();

            await logout(page);

            await loginAsStdAdmin(page);
            await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/${amendmentUrlPath}`);
            await expect(page.locator('section.likes').first()).toBeVisible();
            await expect(page.locator('button[name=motionLike]').first()).toBeVisible();
            await expect(page.locator('button[name=motionDislike]').first()).toBeVisible();
            await expect(page.locator('button[name=motionSupport]').first()).toBeVisible();
        });

        await test.step('support this motion', async () => {
            await page.locator('input[name=motionSupportName]').first().fill('My name');
            await page.locator('input[name=motionSupportOrga]').first().fill('My organisation');
        });

        await test.step('revoke the support', async () => {
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).toContainText('Du unterstützt diesen Änderungsantrag nun.');
            await expect(page.locator('button[name=motionSupport]').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.supporters')).toContainText('Du!');
            await expect(page.locator('section.supporters').getByText('Testadmin').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.supporters')).toContainText('My name');
            await expect(page.locator('section.supporters')).toContainText('My organisation');
            await expect(page.locator('body')).toContainText('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
            await expect(page.locator('button[name=motionSupportRevoke]').first()).toBeVisible();
        });

        await test.step('support it again', async () => {
            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('body')).toContainText('Du stehst diesem Änderungsantrag wieder neutral gegenüber');
            await expect(page.locator('body')).toContainText('aktueller Stand: 0');

            await page.evaluate(() => {
                document.querySelectorAll('[required]').forEach((el) => el.removeAttribute('required'));
            });
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).not.toContainText('Du unterstützt diesen Änderungsantrag nun.', { useInnerText: true });
            await expect(page.locator('body')).toContainText('No organization entered');

            await page.locator('input[name=motionSupportOrga]').first().fill('My organisation');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).toContainText('Du unterstützt diesen Änderungsantrag nun.');

            await logout(page);

            await loginAsStdUser(page);
            await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/${amendmentUrlPath}`);
        });

        await test.step('submit the amendment', async () => {
            await expect(page.locator('section.supporters')).toContainText('Testadmin');
            await page.locator('.amendmentSupportFinishForm [name="amendmentSupportFinish"]').click();
            await expect(page.locator('body')).toContainText('Der Änderungsantrag ist nun offiziell eingereicht');
            await expect(page.locator('.motionData')).toContainText('Eingereicht (ungeprüft)');

            await logout(page);

            await loginAsStdAdmin(page);
            await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/${amendmentUrlPath}`);
        });

        await test.step('ensure I can\\\'t revoke my support once the amendment has been submitted', async () => {
            await expect(page.locator('section.supporters')).toContainText('Du!');
            await expect(page.locator('button[name=motionSupportRevoke]').filter({ visible: true })).toHaveCount(0);
        });

    });
});