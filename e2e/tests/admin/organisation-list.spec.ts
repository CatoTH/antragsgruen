import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';
import { setCkEditorContent } from '../../utils/dom';

test.describe('Admin: OrganisationList', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create three organisations and use them for a motion', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#userAdministrationLink').click();

        await expect(page.locator('.editOrganisationModal')).not.toBeVisible();
        await dispatchClick(page, '.orgaOpenerHolder .orgaOpener');
        await expect(page.locator('.editOrganisationModal')).toBeVisible();
        await dispatchClick(page, '.editOrganisationModal .btnAdd');
        await dispatchClick(page, '.editOrganisationModal .btnAdd');
        await dispatchClick(page, '.editOrganisationModal .btnAdd');

        await page.evaluate(() => {
            const inputs = document.querySelectorAll(
                '.editOrganisationModal input.form-control',
            ) as NodeListOf<HTMLInputElement>;
            if (inputs[0]) inputs[0].value = 'Working group: environment';
            if (inputs[1]) inputs[1].value = 'Working group: infrastructure';
            if (inputs[2]) inputs[2].value = 'Working group: education';
        });

        await dispatchClick(page, '.editOrganisationModal .btnSave');

        await dispatchClick(page, '.orgaOpenerHolder .orgaOpener');
        await expect(page.locator('.editOrganisationModal input').first()).toHaveValue(
            'Working group: environment',
        );
        const inputs = await page.locator('.editOrganisationModal input').all();
        for (const input of inputs) {
            const value = await input.inputValue();
            expect([
                'Working group: environment',
                'Working group: infrastructure',
                'Working group: education',
            ]).toContain(value);
        }

        await new ConsultationHomePage(page).open();
        await new ConsultationHomePage(page).gotoMotionCreatePage();
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Testing motion');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Test</strong></p>');
        await setCkEditorContent(page, 'sections_3_wysiwyg', '<p><strong>Test 2</strong></p>');

        await expect(page.locator('#initiatorPrimaryName')).toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).not.toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).toBeAttached();
        await expect(page.locator('#initiatorOrga')).toBeVisible();

        await page.locator('#initiatorPrimaryName').fill('Tester');
        await page.evaluate(() => {
            const w = window as any;
            w.$('#initiatorOrga').val('Working group: infrastructure');
            w.$('#initiatorOrga').trigger('change');
        });
        await page.locator('#initiatorEmail').fill('tobias@hoessl.eu');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText(
            'Tester (Working group: infrastructure)',
        );

        await page.locator('#motionConfirmForm [name="modify"]').click();
        const selectedOrga = await page.evaluate(
            () => (document.getElementById('initiatorOrga') as HTMLInputElement).value,
        );
        expect(selectedOrga).toEqual('Working group: infrastructure');

        await page.evaluate(() => {
            const w = window as any;
            w.$('#personTypeOrga').prop('checked', true).trigger('change');
        });

        await expect(page.locator('#initiatorPrimaryName')).not.toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).toBeVisible();
        await expect(page.locator('#initiatorOrga')).not.toBeVisible();

        await page.evaluate(() => {
            const w = window as any;
            w.$('#initiatorPrimaryOrgaName')
                .val('Working group: infrastructure')
                .trigger('change');
        });
        await page.locator('#resolutionDate').fill('07.12.2019');

        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText(
            'Working group: infrastructure (dort beschlossen am: 07.12.2019)',
        );

        await page.locator('#motionConfirmForm [name="modify"]').click();

        await expect(page.locator('#initiatorPrimaryName')).not.toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).toBeVisible();
        await expect(page.locator('#initiatorOrga')).not.toBeVisible();
        const selectedOrga2 = await page.evaluate(
            () => (document.getElementById('initiatorPrimaryOrgaName') as HTMLInputElement).value,
        );
        expect(selectedOrga2).toEqual('Working group: infrastructure');
    });

    test('test the same for amendments', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');

        await expect(page.locator('#initiatorPrimaryName')).toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).not.toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).toBeAttached();
        await expect(page.locator('#initiatorOrga')).toBeVisible();

        await page.locator('#initiatorPrimaryName').fill('Tester');
        await page.evaluate(() => {
            const w = window as any;
            w.$('#initiatorOrga').val('Working group: infrastructure');
            w.$('#initiatorOrga').trigger('change');
        });
        await page.locator('#initiatorEmail').fill('tobias@hoessl.eu');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText(
            'Tester (Working group: infrastructure)',
        );

        await page.locator('#amendmentConfirmForm [name="modify"]').click();
        const selectedOrga = await page.evaluate(
            () => (document.getElementById('initiatorOrga') as HTMLInputElement).value,
        );
        expect(selectedOrga).toEqual('Working group: infrastructure');

        await page.evaluate(() => {
            const w = window as any;
            w.$('#personTypeOrga').prop('checked', true).trigger('change');
        });

        await expect(page.locator('#initiatorPrimaryName')).not.toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).toBeVisible();
        await expect(page.locator('#initiatorOrga')).not.toBeVisible();

        await page.evaluate(() => {
            const w = window as any;
            w.$('#initiatorPrimaryOrgaName')
                .val('Working group: infrastructure')
                .trigger('change');
        });
        await page.locator('#resolutionDate').fill('07.12.2019');

        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText(
            'Working group: infrastructure (dort beschlossen am: 07.12.2019)',
        );

        await page.locator('#amendmentConfirmForm [name="modify"]').click();

        await expect(page.locator('#initiatorPrimaryName')).not.toBeVisible();
        await expect(page.locator('#initiatorPrimaryOrgaName')).toBeVisible();
        await expect(page.locator('#initiatorOrga')).not.toBeVisible();
        const selectedOrga2 = await page.evaluate(
            () => (document.getElementById('initiatorPrimaryOrgaName') as HTMLInputElement).value,
        );
        expect(selectedOrga2).toEqual('Working group: infrastructure');
    });
});