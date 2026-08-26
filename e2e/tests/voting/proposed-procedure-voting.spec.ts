import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { disableCurrentlyDebated } from '../../utils/navigation';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { VotingAdminPage } from '../../pages/VotingAdminPage';
import { dispatchClick } from '../../utils/dom';

const RESULTS_PUBLIC_YES = '1';
const RESULTS_PUBLIC_NO = '0';

test.describe('Proposed procedure voting', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('no votings are active initially', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();

        await test.step('enable non-quota speech lists', async () => {
            await expect(page.locator('.currentVotings')).toHaveCount(0);
            await expect(page.locator('.voting_amendment_3')).toHaveCount(0);
            await expect(page.locator('#votingResultsLink')).toHaveCount(0);
        });
    });

    test('an offline voting can be activated, opened, voted on and closed', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        // The fixture has the "Currently debated" module on, which would take the place of the voting widget
        await disableCurrentlyDebated(page);

        const votingAdmin = new VotingAdminPage(page);
        await votingAdmin.open();

        await expect(page.locator('.voting1 h2')).toContainText('Ä2 or Ä3');
        await expect(page.locator('.voting1 .voting_amendment_3')).toContainText('Ä2 zu A2');
        await test.step('Enable online voting for this voting', async () => {
            await expect(page.locator('.voting1 .activateHeader input')).not.toBeChecked();
            await expect(page.locator('.voting1 .btnOpen').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('Remove Ä3', async () => {
            await expect(page.locator('.voting1 .voting_amendment_270 .removeBtn').filter({ visible: true })).toHaveCount(0);

            await dispatchClick(page, '.voting1 .activateHeader input');
            await expect(page.locator('.voting1 .btnOpen').first()).toBeVisible();

            await home.open();
            await expect(page.locator('.currentVotings')).toBeAttached();
            await expect(page.locator('.voting_amendment_3')).toHaveCount(0);

            await votingAdmin.open();
            await expect(page.locator('.voting1 .activateHeader input')).toBeChecked();

            await expect(page.locator('.voting1 .voting_amendment_270').first()).toBeVisible();
            await expect(page.locator('.voting1 .voting_amendment_270 .removeBtn').first()).toBeVisible();
            await dispatchClick(page, '.voting1 .voting_amendment_270 .removeBtn');
            await expect(page.locator('.voting1 .voting_amendment_270').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('Open the voting', async () => {
            await expect(page.locator('.voting1 .btnClose').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting1 .btnReset').filter({ visible: true })).toHaveCount(0);
            await expect(
                page.locator('.voting1 .voting_amendment_3 .votingTableSingle'),
            ).not.toBeVisible();
            await dispatchClick(page, '.voting1 .btnOpen');
        });

        await test.step('hide the numeric results from the voting', async () => {
            await expect(page.locator('#voting1 .titleSetting').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#voting1 .settingsToggleGroup button');
            await expect(page.locator('#voting1 .titleSetting').first()).toBeVisible();
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
            await dispatchClick(page, '#voting1 .btnSave');

            await expect(page.locator('.voting1 .btnOpen').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting1 .btnClose').first()).toBeVisible();
            await expect(page.locator('.voting1 .btnReset').first()).toBeVisible();
            await expect(page.locator('.voting1 .voting_amendment_3 .votingTableSingle').first()).toBeVisible();

            await expect(page.locator('.voting_amendment_3 .voteCount_no')).toContainText('0');
            await expect(page.locator('.voting_amendment_3 .voteCount_abstention')).toContainText('0');
            await expect(page.locator('.voting_amendment_3 .voteCountTotal')).toContainText('0');

            await home.open();
        });

        await test.step('Vote no, but correct it to yes', async () => {
            await expect(page.locator('.currentVotings').first()).toBeVisible();
            await expect(page.locator('.voting_amendment_3').first()).toBeVisible();
            await expect(page.locator('.voting_amendment_270').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting_amendment_3 .btnNo').first()).toBeVisible();

            await dispatchClick(page, '.voting_amendment_3 .btnNo');
            await expect(page.locator('.voting_amendment_3 .voted')).toContainText('Nein');
            await expect(page.locator('.voting_amendment_3 .btnNo').filter({ visible: true })).toHaveCount(0);

            await dispatchClick(page, '.voting_amendment_3 .btnUndo');
            await expect(page.locator('.voting_amendment_3 .btnNo').first()).toBeVisible();

            await dispatchClick(page, '.voting_amendment_3 .btnYes');
            await dispatchClick(page, '.voting_amendment_274 .btnNo');
            await expect(page.locator('.voting_amendment_3 .voted')).toContainText('Ja');
        });

        await test.step('See the updated results', async () => {
            await page.locator('.votingsAdminLink').click();

            await expect(page.locator('.voting_amendment_3 .voteCount_yes')).toContainText('1');
            await expect(page.locator('.voting_amendment_3 .voteCount_no')).toContainText('0');
            await expect(page.locator('.voting_amendment_3 .voteCount_abstention')).toContainText('0');
            await expect(page.locator('.voting_amendment_3 .voteCountTotal')).toContainText('1');
            await expect(page.locator('.voting_amendment_274 .voteCount_yes')).toContainText('0');
            await expect(page.locator('.voting_amendment_274 .voteCount_no')).toContainText('1');
        });

        await test.step('Close the voting', async () => {
            await dispatchClick(page, '.voting1 .btnClose');
            await expect(page.locator('.voting_amendment_3')).toContainText('Angenommen');
            await expect(page.locator('.voting_amendment_274')).toContainText('Abgelehnt');

            await home.open();
        });

        await test.step('see the voting result on the public page', async () => {
            await page.locator('#votingResultsLink').click();
            await expect(page.locator('.voting_motion_114 .votingTableSingle').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.voting_amendment_3 .accepted').first()).toBeVisible();
            await expect(page.locator('.voting_amendment_274 .rejected').first()).toBeVisible();
        });
    });
});
