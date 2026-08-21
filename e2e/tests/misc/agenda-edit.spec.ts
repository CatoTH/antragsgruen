import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_AGENDA_ITEM_ID } from '../../utils/constants';

test.describe('Misc: agenda edit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('reorder, add, set date/time, then delete agenda items', async ({ page }) => {
        await page.goto('/parteitag/parteitag');

        await expect(page.locator('h1')).toContainText('Parteitag');
        await expect(page.locator('.agendaEditLink')).toHaveCount(0);
        await expect(page.locator('.motionListWithinAgenda')).toContainText('0. Tagesordnung');
        await expect(page.locator('.motionListWithinAgenda')).toContainText('1. 1. Vorsitzende');
        await expect(page.locator('.motionListWithinAgenda')).toContainText('3. Sonstiges');
        await expect(page.locator('.motionListWithinAgenda')).not.toContainText('1. Sonstiges');
        await expect(page.locator('#agendaitem_3 > div > h3')).toContainText('Bewerben');
        await expect(page.locator('#agendaitem_6 > div > h3')).toContainText('Antrag stellen');

        await loginAsStdAdmin(page);
        await expect(page.locator('h1')).toContainText('Parteitag');
        await expect(page.locator('.motionListWithinAgenda')).toContainText('Tagesordnung');
        await expect(page.locator('.agendaEditLink')).toHaveCount(1);
        await page.locator('.agendaEditLink').click();

        await page.waitForTimeout(300);
        await expect(page.locator('.agendaEditWidget')).toBeVisible();

        await page.evaluate(() => {
            const widget = (window as any).agendaWidget;
            if (!widget) return;
            const ref = widget.$refs['agenda-edit-widget'];
            const data = JSON.parse(JSON.stringify(ref.getAgendaTest()));
            const lastItem = data.items.pop();
            lastItem.code = '10.';
            data.items[0].code = null;
            data.items.unshift(lastItem);
            data.items.push({
                type: 'item',
                code: null,
                title: 'More motions',
                settings: {
                    has_speaking_list: false,
                    in_proposed_procedures: true,
                    motion_types: [5],
                },
                children: [],
            });
            data.items = Object.values(data.items);
            ref.setAgendaTest(data);
        });
        await page.waitForTimeout(300);

        const newFirstTitle = await page.evaluate(() => {
            const el = document.querySelector(
                '.agendaEditWidget > ul > li:first .titleCol',
            ) as HTMLInputElement | null;
            return el ? (el as HTMLInputElement).value : null;
        });
        expect(newFirstTitle).toBe('Sonstiges');

        await page.locator('.agendaEditWidget .btnSave').click();
        await page.waitForTimeout(1000);

        await page.locator('.backHomeLink').click();
        await expect(
            page.locator(`#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID} > div > h3`),
        ).toContainText('14. More motions');
        await expect(
            page.locator(`#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID} > div > h3`),
        ).toContainText('Antrag stellen');

        await page.locator('.agendaEditLink').click();

        await expect(page.locator('.datetimepicker.time')).toHaveCount(0);
        await page.locator('.showTimeSelector').click();
        await expect(page.locator('.datetimepicker.time')).toBeVisible();

        await page.evaluate(() => {
            const widget = (window as any).agendaWidget;
            if (!widget) return;
            const ref = widget.$refs['agenda-edit-widget'];
            const data = JSON.parse(JSON.stringify(ref.getAgendaTest()));
            data.items[0].time = '17:30';
            data.items.unshift({
                type: 'date_separator',
                date: '2020-02-02',
                title: '',
                settings: {
                    has_speaking_list: false,
                    in_proposed_procedures: true,
                    motion_types: [],
                },
                children: [],
            });
            data.items = Object.values(data.items);
            ref.setAgendaTest(data);
        });
        await page.waitForTimeout(300);

        await page.locator('.agendaEditWidget .btnSave').click();
        await page.waitForTimeout(1000);

        await page.locator('.backHomeLink').click();

        await expect(
            page.locator(`#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID + 1} h3`),
        ).toContainText('Sonntag, 2. Februar 2020');
        await expect(page.locator('#agendaitem_7 .time')).toContainText('17:30');

        await page.locator('.agendaEditLink').click();
        await expect(page.locator('.datetimepicker.time')).toBeVisible();

        await page.evaluate(() => {
            const widget = (window as any).agendaWidget;
            if (!widget) return;
            const ref = widget.$refs['agenda-edit-widget'];
            const data = JSON.parse(JSON.stringify(ref.getAgendaTest()));
            data.items.shift();
            data.items.pop();
            data.items = Object.values(data.items);
            ref.setAgendaTest(data);
        });
        await page.waitForTimeout(300);

        await page.locator('.agendaEditWidget .btnSave').click();
        await page.waitForTimeout(1000);

        await page.locator('.backHomeLink').click();

        await expect(page.locator('body')).not.toContainText('Sonntag, 2. Februar 2020');
        await expect(page.locator('body')).not.toContainText('More motions');
        await expect(page.locator('body')).toContainText('10. Sonstiges');
    });
});
