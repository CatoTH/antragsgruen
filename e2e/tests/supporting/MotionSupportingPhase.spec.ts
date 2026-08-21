import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';

const SUBDOMAIN = 'supporter';
const CONSULTATION = 'supporter';

test.describe('Supporting: MotionSupportingPhase', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motion supporting phase flow', async ({ page }) => {
        await new ConsultationHomePage(page).open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await expect(page.locator('#sidebar .collecting')).toHaveCount(0);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
            motionTypeId: 10,
        });
        await expect(page.locator('#typeMinSupporters')).toHaveValue('1');
        await page.locator('#typeSupportType').selectOption('1');
        await expect(page.locator('#typeMinSupporters')).toHaveCount(0);
        await page.locator('#typeSupportType').selectOption('2');
        await expect(page.locator('#typeMinSupporters')).toBeVisible();
        await page.locator("input[name='motionInitiatorSettings[contactGender]'][value='2']").check();
        await page.locator('.adminTypeForm [name="save"]').click();

        const appearancePage = new AdminAppearancePage(page);
        await appearancePage.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await page.locator('#collectingPage').check();
        await appearancePage.saveForm();

        await new ConsultationHomePage(page).open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await page.locator('#sidebar .collecting a').click();

        await expect(page.locator('.motionList')).toContainText('Support me!');
        await expect(page.locator('.motion116')).toContainText('Aktueller Stand: 0 / 1');

        await logout(page);

        const motionUrl = `${SUBDOMAIN}/${CONSULTATION}/motion/116`;
        await page.goto(`/${motionUrl}`);
        await expect(page.locator('body')).toContainText('Dieser Antrag ist noch nicht eingereicht.');
        await expect(page.locator('body')).toContainText('Du musst dich einloggen, um Anträge unterstützen zu können.');
        await expect(page.locator('button[name=motionSupport]')).toHaveCount(0);
        await expect(page.locator('section.likes form')).toHaveCount(0);

        await loginAsStdUser(page);
        await page.goto(`/${motionUrl}`);
        await expect(page.locator('button[name=motionSupport]')).toBeVisible();
        await expect(page.locator('button[name=motionLike]')).toHaveCount(0);
        await expect(page.locator('button[name=motionDislike]')).toHaveCount(0);

        await logout(page);
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await motionTypePage.open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
            motionTypeId: 10,
        });
        await expect(page.locator('.motionDislike')).not.toBeChecked();
        await expect(page.locator('.motionLike')).not.toBeChecked();
        await page.locator('.motionLike').check();
        await page.locator('.motionDislike').check();
        await page.locator('#typeHasOrga').check();
        await page.locator('.adminTypeForm [name="save"]').click();

        await logout(page);

        await loginAsStdUser(page);
        await page.goto(`/${motionUrl}`);
        await expect(page.locator('section.likes form')).toBeVisible();
        await expect(page.locator('button[name=motionLike]')).toBeVisible();
        await expect(page.locator('button[name=motionDislike]')).toBeVisible();
        await expect(page.locator('button[name=motionSupport]')).toBeVisible();

        await page.locator('input[name=motionSupportName]').fill('My name');
        await page.locator('input[name=motionSupportOrga]').fill('My organisation');

        await page.locator('.motionSupportForm [name="motionSupport"]').click();
        await expect(page.locator('.bootbox')).toBeVisible();
        await expect(page.locator('.bootbox')).toContainText('Bitte gib etwas im Gender-Feld an');
        await page.locator('.bootbox .btn-primary').click();
        await page.locator('.bootbox').waitFor({ state: 'detached' });

        await page.locator('#motionSupportGender').selectOption('Männlich');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('body')).toContainText('Du unterstützt diesen Antrag nun.');
        await expect(page.locator('button[name=motionSupport]')).toHaveCount(0);
        await expect(page.locator('section.supporters')).toContainText('Du!');
        await expect(page.locator('section.supporters')).not.toContainText('Testuser');
        await expect(page.locator('section.supporters')).toContainText('My name');
        await expect(page.locator('section.supporters')).toContainText('My organisation');
        await expect(page.locator('body')).toContainText('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
        await expect(page.locator('button[name=motionSupportRevoke]')).toBeVisible();

        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText('Du stehst diesem Antrag wieder neutral gegenüber');
        await expect(page.locator('body')).toContainText('aktueller Stand: 0');

        await page.evaluate(() => {
            document.querySelectorAll("input[name=motionSupportOrga]").forEach((el) => el.removeAttribute('required'));
        });
        await page.locator('#motionSupportGender').selectOption('Keine Angabe');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();
        await expect(page.locator('body')).not.toContainText('Du unterstützt diesen Antrag nun.');
        await expect(page.locator('body')).toContainText('No organization entered');

        await page.locator('input[name=motionSupportOrga]').fill('My organisation');
        await page.locator('#motionSupportGender').selectOption('Keine Angabe');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();
        await expect(page.locator('body')).toContainText('Du unterstützt diesen Antrag nun.');

        await logout(page);

        await loginAsStdAdmin(page);
        await page.goto(`/${motionUrl}`);
        await expect(page.locator('section.supporters')).toContainText('Testuser');
        await page.locator('.motionSupportFinishForm [name="motionSupportFinish"]').click();
        await expect(page.locator('body')).toContainText('Der Antrag ist nun offiziell eingereicht');
        await expect(page.locator('.motionData')).toContainText('Eingereicht (ungeprüft)');

        await new AdminIndexPage(page).open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await motionTypePage.open({
            subdomain: SUBDOMAIN,
            consultationPath: CONSULTATION,
            motionTypeId: 10,
        });
        await page.locator("input[name='motionInitiatorSettings[contactGender]'][value='0']").check();
        await page.locator('.adminTypeForm [name="save"]').click();

        await logout(page);

        await loginAsStdUser(page);
        await page.goto(`/${motionUrl}`);
        await expect(page.locator('section.supporters')).toContainText('Du!');
        await expect(page.locator('button[name=motionSupportRevoke]')).toHaveCount(0);

        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await home.gotoMotionCreatePage(10);
        await page.locator('#sections_30').fill('Title as normal person');
        await setCkEditorContent(page, 'sections_31_wysiwyg', '<p><strong>Test</strong></p>');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText('benötigt dieser mindestens 1 Unterstützer*innen.');

        await home.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await home.gotoMotionCreatePage(10);
        await page.locator('#sections_30').fill('Title as organization');
        await setCkEditorContent(page, 'sections_31_wysiwyg', '<p><strong>Test</strong></p>');
        await page.locator('#personTypeOrga').check();
        await page.locator('#initiatorPrimaryName').fill('My organization');
        await page.locator('#resolutionDate').fill('01.01.2016');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            'Du hast den Antrag eingereicht. Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.',
        );

        await home.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION });
        await expect(page.locator('.myMotionList')).toContainText('Eingereicht (ungeprüft)');
        await expect(page.locator('.myMotionList')).toContainText('Unterstützer*innen sammeln');
    });
});