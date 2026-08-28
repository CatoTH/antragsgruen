import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';

const RESULTS_PUBLIC_YES = '1';
const RESULTS_PUBLIC_NO = '0';

test.describe('Proposed procedure voting', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('no votings are active initially', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();

        await expect(page.locator('.currentVotings')).toHaveCount(0);
        await expect(page.locator('.voting_amendment_3')).toHaveCount(0);
        await expect(page.locator('#votingResultsLink')).toHaveCount(0);
    });

    test('an offline voting can be activated, opened, voted on and closed', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();

        await expect(page.locator('.voting1 h2')).toContainText('Ä2 or Ä3');
        await expect(page.locator('.voting1 .voting_amendment_3')).toContainText('Ä2 zu A2');
        await expect(page.locator('.voting1 .activateHeader input')).not.toBeChecked();
        await expect(page.locator('.voting1 .btnOpen')).toHaveCount(0);
        await expect(page.locator('.voting1 .voting_amendment_270 .removeBtn')).toHaveCount(0);

        await page.locator('.voting1 .activateHeader input').click();
        await expect(page.locator('.voting1 .btnOpen')).toBeVisible();

        await home.open();
        await expect(page.locator('.currentVotings')).toBeAttached();
        await expect(page.locator('.voting_amendment_3')).toHaveCount(0);

        await votingAdmin.open();
        await expect(page.locator('.voting1 .activateHeader input')).toBeChecked();

        await expect(page.locator('.voting1 .voting_amendment_270')).toBeVisible();
        await expect(page.locator('.voting1 .voting_amendment_270 .removeBtn')).toBeVisible();
        await page.locator('.voting1 .voting_amendment_270 .removeBtn').click();
        await expect(page.locator('.voting1 .voting_amendment_270')).toHaveCount(0);

        await expect(page.locator('.voting1 .btnClose')).toHaveCount(0);
        await expect(page.locator('.voting1 .btnReset')).toHaveCount(0);
        await expect(
            page.locator('.voting1 .voting_amendment_3 .votingTableSingle'),
        ).toHaveCount(0);
        await page.locator('.voting1 .btnOpen').click();

        await expect(page.locator('#voting1 .titleSetting')).toHaveCount(0);
        await page.locator('#voting1 .settingsToggleGroup button').click();
        await expect(page.locator('#voting1 .titleSetting')).toBeVisible();
        await expect(
            page.locator('#voting1 .resultsPublicSettings input[type=radio]:checked'),
        ).toHaveValue(RESULTS_PUBLIC_YES);
        await expect(
            page.locator('#voting1 .votesPublicSettings input[type=radio]').first(),
        ).toBeDisabled();
        await page
            .locator(
                `#voting1 .resultsPublicSettings input[type=radio][value="${RESULTS_PUBLIC_NO}"]`,
            )
            .click();
        await page.locator('#voting1 .btnSave').click();

        await expect(page.locator('.voting1 .btnOpen')).toHaveCount(0);
        await expect(page.locator('.voting1 .btnClose')).toBeVisible();
        await expect(page.locator('.voting1 .btnReset')).toBeVisible();
        await expect(page.locator('.voting1 .voting_amendment_3 .votingTableSingle')).toBeVisible();

        await expect(page.locator('.voting_amendment_3 .voteCount_no')).toContainText('0');
        await expect(page.locator('.voting_amendment_3 .voteCount_abstention')).toContainText('0');
        await expect(page.locator('.voting_amendment_3 .voteCountTotal')).toContainText('0');

        await home.open();
        await expect(page.locator('.currentVotings')).toBeVisible();
        await expect(page.locator('.voting_amendment_3')).toBeVisible();
        await expect(page.locator('.voting_amendment_270')).toHaveCount(0);
        await expect(page.locator('.voting_amendment_3 .btnNo')).toBeVisible();

        await page.locator('.voting_amendment_3 .btnNo').click();
        await expect(page.locator('.voting_amendment_3 .voted')).toContainText('Nein');
        await expect(page.locator('.voting_amendment_3 .btnNo')).toHaveCount(0);

        await page.locator('.voting_amendment_3 .btnUndo').click();
        await expect(page.locator('.voting_amendment_3 .btnNo')).toBeVisible();

        await page.locator('.voting_amendment_3 .btnYes').click();
        await page.locator('.voting_amendment_274 .btnNo').click();
        await expect(page.locator('.voting_amendment_3 .voted')).toContainText('Ja');

        await page.locator('.votingsAdminLink').click();

        await expect(page.locator('.voting_amendment_3 .voteCount_yes')).toContainText('1');
        await expect(page.locator('.voting_amendment_3 .voteCount_no')).toContainText('0');
        await expect(page.locator('.voting_amendment_3 .voteCount_abstention')).toContainText('0');
        await expect(page.locator('.voting_amendment_3 .voteCountTotal')).toContainText('1');
        await expect(page.locator('.voting_amendment_274 .voteCount_yes')).toContainText('0');
        await expect(page.locator('.voting_amendment_274 .voteCount_no')).toContainText('1');

        await page.locator('.voting1 .btnClose').click();
        await expect(page.locator('.voting_amendment_3')).toContainText('Angenommen');
        await expect(page.locator('.voting_amendment_274')).toContainText('Abgelehnt');

        await home.open();
        await page.locator('#votingResultsLink').click();
        await expect(page.locator('.voting_motion_114 .votingTableSingle')).toHaveCount(0);
        await expect(page.locator('.voting_amendment_3 .accepted')).toBeVisible();
        await expect(page.locator('.voting_amendment_274 .rejected')).toBeVisible();
    });
});
