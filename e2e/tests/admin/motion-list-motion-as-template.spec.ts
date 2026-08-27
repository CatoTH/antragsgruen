import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MotionListMotionAsTemplate', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create motion from motion template (organization)', async ({ page }) => {
        await page.goto('/1laenderrat2015/1laenderrat2015');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await test.step('test if I can create a motion using another one as template', async () => {
            await dispatchClick(page, '.adminMotionTable .motion8 .actionCol .dropdown-toggle');
            await dispatchClick(page, '.adminMotionTable .motion8 .actionCol .asTemplate');

            await expect(page.locator('h1')).toContainText('ANTRAG STELLEN');
            await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Bundesvorstand');
            await expect(page.locator('#resolutionDate').first()).toBeVisible();
            await expect(page.locator('#resolutionDate')).toHaveValue('09.03.2015');
            await expect(page.locator('#personTypeOrga')).toBeChecked();
            await expect(page.locator('input[name=otherInitiator]')).toBeChecked();
        });
    });

    test('create motion from motion template (natural person)', async ({ page }) => {
        await page.goto('/1laenderrat2015/1laenderrat2015');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await dispatchClick(page, '.adminMotionTable .motion48 .actionCol .dropdown-toggle');
        await dispatchClick(page, '.adminMotionTable .motion48 .actionCol .asTemplate');

        await expect(page.locator('h1')).toContainText('ANTRAG STELLEN');
        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Axel Wolbring');
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
        expect(name1).toEqual('Wilma Daßler');
        expect(name2).toEqual('Oliver Ende');
    });
});