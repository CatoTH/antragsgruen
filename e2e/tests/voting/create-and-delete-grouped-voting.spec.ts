import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { AdminMotionPage } from '../../pages/AdminMotionPage';
import { AdminAmendmentPage } from '../../pages/AdminAmendmentPage';

const RESULTS_PUBLIC_YES = '1';
const VOTES_PUBLIC_NO = '0';
const STATUS_ACCEPTED = '4';
const VOTING_BASE_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

test.describe('Grouped voting creation and deletion', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('no votings are present initially', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();

        await expect(page.locator('.currentVotings')).toHaveCount(0);
        await expect(page.locator('.voting_amendment_3')).toHaveCount(0);
        await expect(page.locator('#votingResultsLink')).toHaveCount(0);
    });

    test('a grouped voting can be created, voted on and deleted', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const adminAmendment = new AdminAmendmentPage(page);
        await adminAmendment.open({ amendmentId: 1 });
        await expect(page.locator('.votingDataHolder')).toHaveCount(0);
        await page.locator('.votingDataOpener').click();
        await expect(page.locator('.votingDataHolder')).toBeVisible();

        await expect(page.locator('.newBlock')).toHaveCount(0);
        await page.evaluate(() => {
            const w = window as any;
            w.$('#votingBlockId').val('NEW').trigger('change');
        });
        await expect(page.locator('.newBlock')).toBeVisible();

        await page.locator('#newBlockTitle').fill('Newly created voting');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('.votingDataHolder')).toBeVisible();
        await expect(page.locator('#votingBlockId')).toHaveValue(
            String(FIRST_FREE_VOTING_BLOCK_ID),
        );
        await expect(page.locator('#votingItemBlockName')).toHaveCount(0);

        const adminMotion = new AdminMotionPage(page);
        await adminMotion.open({ motionId: 114 });
        await expect(page.locator('.votingDataHolder')).toHaveCount(0);
        await page.locator('.votingDataOpener').click();
        await expect(page.locator('.votingDataHolder')).toBeVisible();

        await expect(page.locator('#votingItemBlockName')).toHaveCount(0);
        await expect(page.locator('.newBlock')).toHaveCount(0);
        await expect(
            page.locator(`.votingItemBlockRow${FIRST_FREE_VOTING_BLOCK_ID}`),
        ).toHaveCount(0);

        await page.evaluate((blockId) => {
            const w = window as any;
            w.$('#votingBlockId').val(String(blockId)).trigger('change');
        }, FIRST_FREE_VOTING_BLOCK_ID);
        await expect(page.locator('.newBlock')).toHaveCount(0);
        await expect(
            page.locator(`.votingItemBlockRow${FIRST_FREE_VOTING_BLOCK_ID}`),
        ).toBeVisible();

        await page
            .locator(`#votingItemBlockId${FIRST_FREE_VOTING_BLOCK_ID}`)
            .selectOption({ label: 'Ä1' });
        await expect(page.locator('#votingItemBlockName')).toBeVisible();
        await page
            .locator('#votingItemBlockName')
            .fill('Vote for Ä1 and A5 at the same time');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(page.locator('#votingItemBlockName')).toHaveValue(
            'Vote for Ä1 and A5 at the same time',
        );

        await adminAmendment.open({ amendmentId: 1 });
        await expect(page.locator('.votingDataHolder')).toBeVisible();
        await expect(page.locator('#votingItemBlockName')).toHaveValue(
            'Vote for Ä1 and A5 at the same time',
        );

        await page.locator('.votingEditLink').click();
        await expect(page.locator(VOTING_BASE_ID)).toBeVisible();
        await expect(page.locator('.voting_motion_114')).toContainText(
            'Vote for Ä1 and A5 at the same time',
        );

        await expect(page.locator(`${VOTING_BASE_ID} .titleSetting`)).toHaveCount(0);
        await page.locator(`${VOTING_BASE_ID} .settingsToggleGroup button`).click();
        await expect(page.locator(`${VOTING_BASE_ID} .titleSetting`)).toBeVisible();

        await expect(
            page.locator(`${VOTING_BASE_ID} .resultsPublicSettings input[type=radio]:checked`),
        ).toHaveValue(RESULTS_PUBLIC_YES);
        await expect(
            page.locator(`${VOTING_BASE_ID} .votesPublicSettings input[type=radio]:checked`),
        ).toHaveValue(VOTES_PUBLIC_NO);

        await expect(page.locator(`${VOTING_BASE_ID} .titleSetting input`)).toHaveValue(
            'Newly created voting',
        );
        await page
            .locator(`${VOTING_BASE_ID} .titleSetting input`)
            .fill('New voting for testing');
        await page
            .locator(`${VOTING_BASE_ID} .assignedMotion select`)
            .selectOption({ label: 'A5: Leerzeichen-Test' });
        await page.locator(`${VOTING_BASE_ID} .btnSave`).click();
        await expect(page.locator(`${VOTING_BASE_ID} h2`)).toContainText('New voting for testing');

        await page.locator(`${VOTING_BASE_ID} .btnOpen`).click();
        await expect(page.locator(`${VOTING_BASE_ID} .btnOpen`)).toHaveCount(0);
        await expect(page.locator(`${VOTING_BASE_ID} .btnClose`)).toBeVisible();

        await home.open();
        await expect(page.locator('.currentVotings')).toBeAttached();
        await expect(page.locator('.voting_motion_114')).toHaveCount(0);
        await expect(page.locator('#votingResultsLink')).toHaveCount(0);

        await page.locator('.motionLink114').click();
        await expect(page.locator('.currentVotings')).toBeVisible();
        await expect(page.locator('.voting_motion_114')).toBeVisible();
        await expect(page.locator('.voting_motion_114')).toContainText('A5: Leerzeichen-Test');
        await expect(page.locator('.voting_motion_114')).toContainText('Ä1 zu A2: O’zapft is!');
        await expect(page.locator('.voting_motion_114')).toContainText(
            'Vote for Ä1 and A5 at the same time',
        );

        await page.locator('.voting_motion_114 .btnYes').click();
        await expect(page.locator('body')).toContainText('1 Stimme abgegeben');
        await page.locator('.votingsAdminLink').click();

        await page.locator(`${VOTING_BASE_ID} .btnClose`).click();
        await expect(page.locator('.voting_motion_114 .accepted')).toBeVisible();

        await page.locator('.adminUrl114').click();
        await expect(page.locator('input[name=votingStatus]:checked')).toHaveValue(
            STATUS_ACCEPTED,
        );
        await expect(page.locator('#votesYes')).toHaveValue('1');
        await expect(page.locator('#votesNo')).toHaveValue('0');
        await expect(page.locator('#votesAbstention')).toHaveValue('0');

        await adminAmendment.open({ amendmentId: 1 });
        await expect(page.locator('input[name=votingStatus]:checked')).toHaveValue(
            STATUS_ACCEPTED,
        );
        await expect(page.locator('#votesYes')).toHaveValue('1');
        await expect(page.locator('#votesNo')).toHaveValue('0');
        await expect(page.locator('#votesAbstention')).toHaveValue('0');

        await home.open();
        await page.locator('#votingResultsLink').click();
        await expect(
            page.locator('.voting_motion_114 .votingTableSingle .voteCount_yes'),
        ).toContainText('1');
        await expect(page.locator('.voting_motion_114 .accepted')).toBeVisible();

        await page.locator('.sidebarActions .admin a').click();
        await expect(page.locator(`${VOTING_BASE_ID} .btnDelete`)).toHaveCount(0);
        await page.locator(`${VOTING_BASE_ID} .settingsToggleGroup button`).click();
        await expect(page.locator(`${VOTING_BASE_ID} .btnDelete`)).toBeVisible();
        await page.locator(`${VOTING_BASE_ID} .btnDelete`).click();

        await expectBootboxDialog(page, /gelöscht/);
        await acceptBootbox(page);

        await expect(page.locator(VOTING_BASE_ID)).toHaveCount(0);
    });
});
