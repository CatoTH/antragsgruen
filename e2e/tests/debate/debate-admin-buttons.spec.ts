import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { gotoConsultationHome } from '../../utils/navigation';

const TAB_DEBATED = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(1)';
const TAB_VOTING = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(3)';

// The std-parteitag fixture ships with an ongoing debate on motion A2 ("O’zapft is!"), which has no
// speaking list and no voting yet - so the debated tab offers the "Activate" / "Create" variants.

test.describe('Debate: DebateAdminButtons', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate the speaking list directly from the debated tab', async ({ page }) => {
        await gotoConsultationHome(page);
        await loginAsStdAdmin(page);
        await gotoConsultationHome(page);
        await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
            timeout: 5000,
        });

        await test.step('activate the speaking list directly from the debated tab', async () => {
            // no active list yet
            await expect(page.locator('.currentDebateAdmin .manageSpeechBtn')).toContainText(
                'Redeliste aktivieren',
            );
            await page.locator('.currentDebateAdmin .manageSpeechBtn').click();
            // activated + switched to the tab
            await expect(
                page.locator('.currentDebateAdmin .speechTab .speechAdmin'),
            ).toBeVisible({ timeout: 8000 });
        });

        await test.step('see the speaking-list button switch to "manage" once a list is active', async () => {
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('.currentDebateAdmin .manageSpeechBtn')).toContainText(
                'Redeliste verwalten',
                { timeout: 5000 },
            );
            // now just switches to the tab
            await page.locator('.currentDebateAdmin .manageSpeechBtn').click();
            await expect(
                page.locator('.currentDebateAdmin .speechTab .speechAdmin'),
            ).toBeVisible({ timeout: 8000 });
        });

        await test.step('keep the debated-tab voting label in sync when assigning then unassigning a voting', async () => {
            await page.locator(TAB_VOTING).click();
            await expect(
                page.locator('.currentDebateAdmin .votingTab .votingCreate'),
            ).toBeVisible({ timeout: 8000 });
            await page
                .locator('.votingTab #debateVotingSelectExisting')
                .selectOption({ label: 'Ä2 or Ä3' });
            await page.locator('.votingTab .votingCreate .votingAssignRow button').click();
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });

            // The debated tab now reflects the assigned voting
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toContainText(
                'Abstimmung verwalten',
                { timeout: 5000 },
            );

            // Unassign again (allowed: the debated motion is not itself a voting item of this block)
            await page.locator(TAB_VOTING).click();
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });
            await page.locator('.votingTab .votingCard .votingCardActions button').click();
            await expect(
                page.locator('.currentDebateAdmin .votingTab .votingCreate'),
            ).toBeVisible({ timeout: 8000 });

            // Back on the debated tab, the label must revert to "Create voting" rather than stay "Manage voting"
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toContainText(
                'Abstimmung anlegen',
                { timeout: 5000 },
            );
        });

        await test.step('create the voting directly from the debated tab', async () => {
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toBeVisible({
                timeout: 5000,
            });
            // no voting associated yet
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toContainText(
                'Abstimmung anlegen',
            );
            await page.locator('.currentDebateAdmin .manageVotingBtn').click();
            // created + switched to the tab
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });
            await expect(
                page.locator('.votingTab .votingCard .votingCardStatus'),
            ).toContainText('In Vorbereitung');
            // The debated motion is now its own voting item, so unassigning would not clear the card - the
            // "unassign" button is therefore hidden (it would otherwise be a no-op, leaving the voting box behind).
            await expect(
                page.locator('.votingTab .votingCard .votingCardActions button'),
            ).not.toBeVisible();
        });

        await test.step('see the voting button switch to "manage" once a voting is associated', async () => {
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toContainText(
                'Abstimmung verwalten',
                { timeout: 5000 },
            );
            // now just switches to the tab
            await page.locator('.currentDebateAdmin .manageVotingBtn').click();
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });
        });

        await test.step('see the button labels update when switching to another item without changing tabs', async () => {
            await page.locator(TAB_DEBATED).click();
            // the motion has a voting
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toContainText(
                'Abstimmung verwalten',
                { timeout: 5000 },
            );
            // Switch the debate to a free-text item (no voting) - this stays on the debated tab and only changes
            // the current item, so the label must update on its own, not just after a tab switch.
            await page.locator('#debateAdminFreeText').first().fill('Allgemeine Aussprache');
            await page
                .locator('.currentDebateAdmin .selectRow-free_text .rowButton button')
                .click();
            await expect(
                page.locator('.currentDebateAdmin .debatedItem .title'),
            ).toContainText('Allgemeine Aussprache', { timeout: 5000 });
            await expect(page.locator('.currentDebateAdmin .manageVotingBtn')).toContainText(
                'Abstimmung anlegen',
                { timeout: 5000 },
            );
        });
    });
});
