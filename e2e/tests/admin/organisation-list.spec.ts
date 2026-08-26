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
        await test.step('create three organisations and use them for a motion', async () => {
            await loginAsStdAdmin(page);
            await page.locator('#adminLink').click();
            await page.locator('#userAdministrationLink').click();

            await expect(page.locator('.editOrganisationModal').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.orgaOpenerHolder .orgaOpener');
            await expect(page.locator('.editOrganisationModal').first()).toBeVisible();
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
            await test.step('see the organisations when creating motions', async () => {
                await page.locator("input[name='tags[]'][value='1']").first().check();
                await page.locator("[name='sections[1]']").first().fill('Testing motion');
                await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Test</strong></p>');
                await setCkEditorContent(page, 'sections_3_wysiwyg', '<p><strong>Test 2</strong></p>');

                await expect(page.locator('#initiatorPrimaryName').first()).toBeVisible();
                await expect(page.locator('#initiatorPrimaryOrgaName').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('#initiatorPrimaryOrgaName')).toBeAttached();
                await expect(page.locator('#initiatorOrga').first()).toBeVisible();

                await page.locator('#initiatorPrimaryName').first().fill('Tester');
                await page.evaluate(() => {
                    const w = window as any;
                    w.$('#initiatorOrga').val('Working group: infrastructure');
                    w.$('#initiatorOrga').trigger('change');
                });
                await page.locator('#initiatorEmail').first().fill('tobias@hoessl.eu');
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

                await expect(page.locator('#initiatorPrimaryName').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('#initiatorPrimaryOrgaName').first()).toBeVisible();
                await expect(page.locator('#initiatorOrga').filter({ visible: true })).toHaveCount(0);

                await page.evaluate(() => {
                    const w = window as any;
                    w.$('#initiatorPrimaryOrgaName')
                        .val('Working group: infrastructure')
                        .trigger('change');
                });
                await page.locator('#resolutionDate').first().fill('07.12.2019');

                await page.locator('#motionEditForm [name="save"]').click();

                await expect(page.locator('body')).toContainText(
                    'Working group: infrastructure (dort beschlossen am: 07.12.2019)',
                );

                await page.locator('#motionConfirmForm [name="modify"]').click();

                await expect(page.locator('#initiatorPrimaryName').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('#initiatorPrimaryOrgaName').first()).toBeVisible();
                await expect(page.locator('#initiatorOrga').filter({ visible: true })).toHaveCount(0);
                const selectedOrga2 = await page.evaluate(
                    () => (document.getElementById('initiatorPrimaryOrgaName') as HTMLInputElement).value,
                );
                expect(selectedOrga2).toEqual('Working group: infrastructure');
            });
        });

        await test.step('test the same for amendments', async () => {
            await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');

            await expect(page.locator('#initiatorPrimaryName').first()).toBeVisible();
            await expect(page.locator('#initiatorPrimaryOrgaName').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#initiatorPrimaryOrgaName')).toBeAttached();
            await expect(page.locator('#initiatorOrga').first()).toBeVisible();

            await page.locator('#initiatorPrimaryName').first().fill('Tester');
            await page.evaluate(() => {
                const w = window as any;
                w.$('#initiatorOrga').val('Working group: infrastructure');
                w.$('#initiatorOrga').trigger('change');
            });
            await page.locator('#initiatorEmail').first().fill('tobias@hoessl.eu');
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

            await expect(page.locator('#initiatorPrimaryName').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#initiatorPrimaryOrgaName').first()).toBeVisible();
            await expect(page.locator('#initiatorOrga').filter({ visible: true })).toHaveCount(0);

            await page.evaluate(() => {
                const w = window as any;
                w.$('#initiatorPrimaryOrgaName')
                    .val('Working group: infrastructure')
                    .trigger('change');
            });
            await page.locator('#resolutionDate').first().fill('07.12.2019');

            await page.locator('#amendmentEditForm [name="save"]').click();

            await expect(page.locator('body')).toContainText(
                'Working group: infrastructure (dort beschlossen am: 07.12.2019)',
            );

            await page.locator('#amendmentConfirmForm [name="modify"]').click();

            await expect(page.locator('#initiatorPrimaryName').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#initiatorPrimaryOrgaName').first()).toBeVisible();
            await expect(page.locator('#initiatorOrga').filter({ visible: true })).toHaveCount(0);
            const selectedOrga2 = await page.evaluate(
                () => (document.getElementById('initiatorPrimaryOrgaName') as HTMLInputElement).value,
            );
            expect(selectedOrga2).toEqual('Working group: infrastructure');
        });
    });
});