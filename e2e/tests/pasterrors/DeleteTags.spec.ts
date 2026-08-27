import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick, expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('DeleteTags', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('removing a tag still assigned to motions should show a bootbox warning', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();

        await page.waitForTimeout(500);

        await dispatchClick(page, '#tagsEditForm .editList li:nth-child(2) .remover');
        await expectBootboxDialog(
            page,
            'Es gibt Anträge oder Änderungsanträge, die diesem Thema zugeordnet sind',
        );
        await acceptBootbox(page);

        const consultationPage = new AdminConsultationPage(page);
        await new AdminIndexPage(page).open();
        await consultationPage.open();
        await consultationPage.saveForm();

        await page.waitForTimeout(500);
        await expect(page.locator('#consultationSettingsForm').first()).toBeVisible();

        const tagCount = await page.evaluate(
            () =>
                document.querySelectorAll('#tagsEditForm .editList li').length,
        );
        expect(tagCount).toBe(2);
    });
});