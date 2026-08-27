import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MotionListAmendmentAsTemplate', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create amendment from amendment template (otherInitiator)', async ({ page }) => {
        await page.goto('/1laenderrat2015/1laenderrat2015');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await test.step('test if I can create an amendment using another one as template', async () => {
            await dispatchClick(page, '.adminMotionTable .amendment13 .actionCol .dropdown-toggle');
            await dispatchClick(page, '.adminMotionTable .amendment13 .actionCol .asTemplate');

            await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG');
            await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Robin Stapf');
            await expect(page.locator('#resolutionDate').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#personTypeNatural')).toBeChecked();
            await expect(page.locator('input[name=otherInitiator]')).toBeChecked();

            const name1 = await page.evaluate(
                () => {
                    const row = document.querySelectorAll('.supporterData .supporterRow')[0];
                    const input = row ? row.querySelector<HTMLInputElement>('input.name') : null;
                    return input ? input.value : '';
                },
            );
            const name2 = await page.evaluate(
                () => {
                    const row = document.querySelectorAll('.supporterData .supporterRow')[1];
                    const input = row ? row.querySelector<HTMLInputElement>('input.name') : null;
                    return input ? input.value : '';
                },
            );
            expect(name1).toEqual('Lena Vaatz, LV Rack');
            expect(name2).toEqual('Wolfram Ruth, LV Brandenburg');
        });
    });

    test('create amendment from amendment template (amendment body)', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();

        await dispatchClick(page, '.adminMotionTable .amendment1 .actionCol .dropdown-toggle');
        await dispatchClick(page, '.adminMotionTable .amendment1 .actionCol .asTemplate');

        await expect(page.locator('h1')).toContainText(
            'ÄNDERUNGSANTRAG ZU A2: O’ZAPFT IS! STELLEN',
        );

        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Tester');
        await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');

        await dispatchClick(page, '#section_holder_2 .resetText');
        await expect(page.locator('.ice-ins').getByText('Oamoi a Maß').filter({ visible: true })).toHaveCount(0);
    });
});