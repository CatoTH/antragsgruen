import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_SECTION } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const TYPE_TEXT_SIMPLE = '1';
const TYPE_TABULAR = '4';
const MOTION_SLUG = '321-o-zapft-is';

test.describe('Motion section editing', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('sections can be rearranged and the order is reflected on the motion', async ({
        page,
    }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });

        expect(await motionType.getCurrentOrder()).toEqual(['1', '2', '4', '3', '5']);
        await motionType.setCurrentOrder([3, 2, 1, 4, 5]);
        expect(await motionType.getCurrentOrder()).toEqual(['3', '2', '1', '4', '5']);

        await page.locator('.adminTypeForm [name="save"]').first().click();
        expect(await motionType.getCurrentOrder()).toEqual(['3', '2', '1', '4', '5']);

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('.motionTextHolder0 h2')).toContainText(/begründung/i);
    });

    test('a tabular data section can be created, sorted and edited', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });

        await test.step('rearrange the list', async () => {
            await page.locator('.sectionAdder').click();
        });

        await test.step('check if the change is reflected on the motion', async () => {
            await expect(page.locator('.sectionnew0').first()).toBeVisible();
        });

        await test.step('create a tabular data section', async () => {
            await expect(page.locator('.sectionnew0 .tabularDataRow').getByText(AdminMotionTypePage.TABULAR_LABEL).filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sectionnew0 .commentRow').getByText(AdminMotionTypePage.COMMENTS_LABEL).filter({ visible: true })).toHaveCount(0);

            await page.locator('.sectionnew0 select.sectionType').first().selectOption(TYPE_TEXT_SIMPLE);
            await expect(page.locator('.sectionnew0 .commentRow')).toContainText(
                AdminMotionTypePage.COMMENTS_LABEL,
            );

            await page.locator('.sectionnew0 select.sectionType').first().selectOption(TYPE_TABULAR);
            await expect(page.locator('.sectionnew0 .tabularDataRow')).toContainText(
                AdminMotionTypePage.TABULAR_LABEL,
            );
            await expect(page.locator('.sectionnew0 .commentRow').getByText(AdminMotionTypePage.COMMENTS_LABEL).filter({ visible: true })).toHaveCount(0);
        });

        await page.locator('.sectionnew0 .sectionTitle input').first().fill('Some tabular data');
        await page.locator('.sectionnew0 .tabularDataRow ul li.no0 input').first().fill('Testrow');
        await page.locator('.sectionnew0 .tabularDataRow ul li.no1 input').first().fill('Testrow 2');
        await page.locator('.sectionnew0 .tabularDataRow ul li.no2 input').first().fill('Testrow 3');
        await page.locator('.sectionnew0 .positionRow input').first().selectOption('1');

        const initialOrder = await page.evaluate(() => {
            const w = window as any;
            return w.$('.sectionnew0 .tabularDataRow ul').data('sortable').toArray();
        });
        expect(initialOrder).toEqual(['ewb', 'ewc', 'ewd']);

        await page.evaluate(() => {
            const w = window as any;
            w.$('.sectionnew0 .tabularDataRow ul').data('sortable').sort(['ewb', 'ewd', 'ewc']);
        });

        const sortedOrder = await page.evaluate(() => {
            const w = window as any;
            return w.$('.sectionnew0 .tabularDataRow ul').data('sortable').toArray();
        });
        expect(sortedOrder).toEqual(['ewb', 'ewd', 'ewc']);

        await page.locator('.adminTypeForm [name="save"]').first().click();

        const newSection = `.section${FIRST_FREE_MOTION_SECTION}`;
        await expect(page.locator(newSection).first()).toBeVisible();
        await test.step('check if the changes to tabular data section were saved', async () => {
            await expect(page.locator(`${newSection} .sectionTitle input`)).toHaveValue(
                'Some tabular data',
            );
            await expect(
                page.locator(`${newSection} .tabularDataRow ul li.no1 input`),
            ).toHaveValue('Testrow 3');
            await expect(page.locator(`${newSection} .positionRow input`)).toHaveValue('1');
        });

        await test.step('change the tabular data afterwards', async () => {
            await page.locator(`${newSection} .sectionTitle input`).first().fill('My life');
            await page.locator(`${newSection} .tabularDataRow ul li.no1 input`).first().fill('Birth year');
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await expect(page.locator(newSection).first()).toBeVisible();
            await expect(page.locator(`${newSection} .sectionTitle input`)).toHaveValue('My life');
            await expect(
                page.locator(`${newSection} .tabularDataRow ul li.no1 input`),
            ).toHaveValue('Birth year');
        });

    });
});
