import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin, logout, loginAsStdUser } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MotionSupporterEdit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit a motion (admin)', async ({ page }) => {
        await test.step('edit a motion (admin)', async () => {
            await loginAsStdAdmin(page);
            await page.locator('#motionListLink').click();
            await page
                .locator('.adminMotionTable .motion116 .titleCol a')
                .first()
                .click();

            await expect(page.locator('#motionSupporterHolder > ul > li')).toHaveCount(0);
            await dispatchClick(page, '.supporterRowAdder');
            await dispatchClick(page, '.supporterRowAdder');
            await expect(page.locator('#motionSupporterHolder > ul > li')).toHaveCount(2);

            await page.evaluate(() => {
                const setVal = (selector: string, value: string) => {
                    const el = document.querySelector(selector) as HTMLInputElement | null;
                    if (el) {
                        el.value = value;
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };
                setVal('#motionSupporterHolder > ul > li:nth(0) .supporterName', 'My Name 1');
                setVal('#motionSupporterHolder > ul > li:nth(0) .supporterOrga', 'My Orga 1');
                setVal('#motionSupporterHolder > ul > li:nth(1) .supporterName', 'My Name 2');
            });
            await page.locator('#motionUpdateForm [name="save"]').click();

            await page.locator('#adminLink').click();
            await page.locator('.motionType10').click();
            await page.locator('#typeHasOrga').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();
            await logout(page);
        });

        await test.step('support the motion as a regular user', async () => {
            await page.goto('/supporter/supporter/321-o-zapft-is');
            await loginAsStdUser(page);
            await page.locator('input[name=motionSupportName]').first().fill('My login-name');
            await page.locator('input[name=motionSupportOrga]').first().fill('My login-organisation');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('section.supporters ul li')).toHaveCount(3);
            const thirdName = await page.evaluate(
                () => { const el = document.querySelector('section.supporters ul li:nth(2)'); return el ? el.textContent : null; },
            );
            expect(thirdName).toContain('My login-name');
            await logout(page);
        });

        await test.step('edit the motion again', async () => {
            await page.locator('#motionListLink').click();
            await page
                .locator('.adminMotionTable .motion116 .titleCol a')
                .first()
                .click();
            await expect(page.locator('#motionSupporterHolder > ul > li')).toHaveCount(3);
            await expect(
                page.locator('#motionSupporterHolder > ul > li input.supporterName').first(),
            ).toHaveValue('My login-name');
            await expect(page.locator('#motionSupporterHolder > ul > li')).toContainText(
                'testuser@example.org',
            );

            await page.evaluate(() => {
                const w = window as any;
                const target = w.$('#motionSupporterHolder > ul > li:nth(2)');
                w.$(target).prependTo(w.$('#motionSupporterHolder > ul'));
                w.$('#motionSupporterHolder > ul > li:nth(0) .supporterName').val('My login-name 2');
            });

            await dispatchClick(page, '.initiatorData .moreInitiatorsAdder .adderBtn');
            await dispatchClick(page, '.initiatorData .moreInitiatorsAdder .adderBtn');

            const lineNumbers = await page.evaluate(() => {
                const w = window as any;
                w.$('.initiatorData .initiatorRow').eq(1).remove();
                w.$('.initiatorData .initiatorRow:nth(0) .name').val('Initiator 2');
                w.$('.initiatorData .initiatorRow:nth(0) .organization').val('Organization 2');
                return w.$('.initiatorData .initiatorRow').length;
            });
            expect(lineNumbers).toEqual(1);

            await page.locator('#motionUpdateForm [name="save"]').click();

            await page.goto('/supporter/supporter/321-o-zapft-is');
            const firstName = await page.evaluate(
                () => { const el = document.querySelector('section.supporters ul li:nth(0)'); return el ? el.textContent : null; },
            );
            expect(firstName).toContain('My login-name 2');
            await expect(page.locator('.motionDataTable')).toContainText('Initiator 2 (Organization 2)');
        });

        await test.step('add some supporters using full-text', async () => {
            await page.locator('#motionListLink').click();
            await page
                .locator('.adminMotionTable .motion116 .titleCol a')
                .first()
                .click();
            await test.step('add some suporters using full-text', async () => {
                await expect(page.locator('#supporterFullTextHolder').filter({ visible: true })).toHaveCount(0);
                await page.locator('.fullTextAdder button').click();
                await expect(page.locator('#supporterFullTextHolder').first()).toBeVisible();

                await page
                    .locator('#supporterFullTextHolder textarea')
                    .fill('Yet another name, KV München; Another Name 3');
                await page.locator('#supporterFullTextHolder .fullTextAdd').click();

                await page.locator('#motionUpdateForm [name="save"]').click();

                await page.goto('/supporter/supporter/321-o-zapft-is');
                const fifthName = await page.evaluate(
                    () => { const el = document.querySelector('section.supporters ul li:nth(4)'); return el ? el.textContent : null; },
                );
                expect(fifthName).toContain('Another Name 3');
                const fourthName = await page.evaluate(
                    () => { const el = document.querySelector('section.supporters ul li:nth(3)'); return el ? el.textContent : null; },
                );
                expect(fourthName).toContain('KV München');
            });
        });
    });
});