import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { gotoConsultationHome, gotoStdAdminPage } from '../../utils/navigation';

// Tabs of the moderation widget: Debated Motion / Speaking List / Ongoing Voting / Protocol
const TAB_DEBATED = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(1)';
const TAB_SPEECH = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(2)';
const TAB_VOTING = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(3)';

// The std-parteitag fixture ships with the feature enabled and an ongoing debate on motion A2 ("O’zapft is!").
// Admins see the moderation widget (.currentDebateAdmin); regular users/guests see the inline widget.

test.describe('Debate: DebateWidgets', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('open the speaking list of the debated motion', async ({ page }) => {
        await gotoConsultationHome(page);
        await loginAsStdAdmin(page);
        await gotoConsultationHome(page);
        await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
            timeout: 5000,
        });
        await expect(page.locator('.currentDebateAdmin .debatedItem .title')).toContainText(
            'O’zapft is!',
            { timeout: 5000 },
        );

        await test.step('open the speaking list of the debated motion', async () => {
            await page.locator(TAB_SPEECH).click();
            await expect(
                page.locator('.currentDebateAdmin .speechTab .speechAdmin'),
            ).toBeVisible({ timeout: 8000 });
            await expect(
                page.locator('.currentDebateAdmin .speechTab .toolbarBelowTitle'),
            ).toBeVisible();
        });

        await test.step('assign an existing voting block to the debated motion, then unassign it again', async () => {
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
            await expect(page.locator('.votingTab .votingCard .votingCardTitle')).toContainText(
                'Ä2 or Ä3',
            );
            // link into the full voting administration
            await expect(page.locator('.votingTab .votingCard')).toContainText(
                'Abstimmung verwalten',
            );
            // The motion is not itself a voting item, so unassigning returns to the create/assign UI
            await page.locator('.votingTab .votingCard .votingCardActions button').click();
            await expect(
                page.locator('.currentDebateAdmin .votingTab .votingCreate'),
            ).toBeVisible({ timeout: 8000 });
        });

        await test.step('create a fresh voting for the debated motion', async () => {
            await page.locator('.votingTab .votingCreate > button').click();
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });
            await expect(page.locator('.votingTab .votingCard .votingCardTitle')).toContainText(
                'O’zapft is!',
            );
            // created in preparing state, not opened
            await expect(page.locator('.votingTab .votingCard .votingCardStatus')).toContainText(
                'In Vorbereitung',
            );
        });

        await test.step('debate an amendment: both a speaking list and a voting can be created for it', async () => {
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('#debateAdminSelect-amendment')).toBeVisible({
                timeout: 5000,
            });
            // Ä1 zu A2
            await page.locator('#debateAdminSelect-amendment').first().selectOption('1');
            await page
                .locator('.currentDebateAdmin .selectRow-amendment .rowButton button')
                .click();
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });

            await page.locator(TAB_SPEECH).click();
            await expect(
                page.locator('.currentDebateAdmin .speechTab .speechAdmin'),
            ).toBeVisible({ timeout: 8000 });

            await page.locator(TAB_VOTING).click();
            await expect(
                page.locator('.currentDebateAdmin .votingTab .votingCreate'),
            ).toBeVisible({ timeout: 8000 });
            await page.locator('.votingTab .votingCreate > button').click();
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });
            await expect(page.locator('.votingTab .votingCard .votingCardTitle')).toContainText(
                'Ä1',
            );
            await expect(page.locator('.votingTab .votingCard .votingCardStatus')).toContainText(
                'In Vorbereitung',
            );
        });

        await test.step('debate free text, which uses the generic fallback speaking list', async () => {
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('#debateAdminFreeText')).toBeVisible({ timeout: 5000 });
            await page
                .locator('#debateAdminFreeText')
                .fill('Allgemeine Aussprache zum Haushalt');
            await page
                .locator('.currentDebateAdmin .selectRow-free_text .rowButton button')
                .click();
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateAdmin .debatedItem .title'),
            ).toContainText('Allgemeine Aussprache zum Haushalt', { timeout: 5000 });

            await page.locator(TAB_SPEECH).click();
            await expect(
                page.locator('.currentDebateAdmin .speechTab .speechAdmin'),
            ).toBeVisible({ timeout: 8000 });
        });

        await test.step('switch the debate back to a motion and check the user-facing widget', async () => {
            await page.locator(TAB_DEBATED).click();
            await expect(page.locator('#debateAdminSelect-motion')).toBeVisible({ timeout: 5000 });
            // A2: O’zapft is!
            await page.locator('#debateAdminSelect-motion').first().selectOption('2');
            await page.locator('.currentDebateAdmin .selectRow-motion .rowButton button').click();
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateAdmin .debatedItem .title'),
            ).toContainText('O’zapft is!', { timeout: 5000 });

            await logout(page);
            await loginAsStdUser(page);
            await gotoConsultationHome(page);
            // regular users do not get the moderation widget
            await expect(page.locator('.currentDebateAdmin').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.currentDebateInline .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateInline .debatedItem .title'),
            ).toContainText('O’zapft is!', { timeout: 5000 });
        });

        await test.step('open the debate in the fullscreen projector, showing the read-only user view', async () => {
            await page.locator('.currentDebateInline .btnFullscreen').click();
            await expect(
                page.locator('.fullscreenMainHolder .currentDebateContent .debatedItem'),
            ).toBeVisible({ timeout: 8000 });
            await expect(
                page.locator('.fullscreenMainHolder .currentDebateContent .debatedItem .title'),
            ).toContainText('O’zapft is!', { timeout: 5000 });
            // dropdown option is translated, not "UNKNOWN TRANSLATION"
            await expect(page.locator('.fullscreenMainHolder .imotionSelector')).toContainText(
                'Aktuell debattiert',
                { timeout: 5000 },
            );
            // read-only projection: the interactive apply UI is not rendered
            await expect(page.locator('.fullscreenMainHolder .speechUser').filter({ visible: true })).toHaveCount(0);
            // The speaking list of the debated motion was created above, but never activated - so the projector
            // hides it, just like the inline widget does
            await expect(page.locator('.fullscreenMainHolder .speechLists').filter({ visible: true })).toHaveCount(0);
            await page.locator('.fullscreenMainHolder .closeBtn').click();
            await expect(page.locator('.fullscreenMainHolder')).not.toBeVisible({
                timeout: 5000,
            });
        });

        await test.step('debate an agenda item on a consultation that has an agenda', async () => {
            // std-parteitag has no agenda, so the agenda case is exercised on the "parteitag" consultation.
            // Enabling the feature here only affects this test's database (it is reset per test).
            await logout(page);
            await loginAsStdAdmin(page);
            const admin = await gotoStdAdminPage(page, 'parteitag', 'parteitag');
            const appearance = await admin.gotoAppearance();
            await page.locator('#hasCurrentlyDebated').first().check();
            await appearance.saveForm();

            await gotoConsultationHome(page, true, 'parteitag', 'parteitag');
            await expect(page.locator('#debateAdminSelect-agenda_item')).toBeVisible({
                timeout: 8000,
            });
            // "Sonstiges"
            await page.locator('#debateAdminSelect-agenda_item').first().selectOption('7');
            await page
                .locator('.currentDebateAdmin .selectRow-agenda_item .rowButton button')
                .click();
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateAdmin .debatedItem .title'),
            ).toContainText('Sonstiges', { timeout: 5000 });
        });

        await test.step('open the speaking list for the debated agenda item', async () => {
            await page.locator(TAB_SPEECH).click();
            await expect(
                page.locator('.currentDebateAdmin .speechTab .speechAdmin'),
            ).toBeVisible({ timeout: 8000 });
        });

        await test.step('create a voting for the agenda item via the free-text question form', async () => {
            await page.locator(TAB_VOTING).click();
            await expect(page.locator('.votingTab #debateVotingQuestion')).toBeVisible({
                timeout: 8000,
            });
            await page
                .locator('.votingTab #debateVotingQuestion')
                .fill('Sollen wir die Sitzung vertagen?');
            await page.locator('.votingTab .votingCreate .input-group button').click();
            await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
                timeout: 8000,
            });
            await expect(page.locator('.votingTab .votingCard .votingCardTitle')).toContainText(
                'Sollen wir die Sitzung vertagen?',
            );
            await expect(page.locator('.votingTab .votingCard .votingCardStatus')).toContainText(
                'In Vorbereitung',
            );
        });

        await test.step('confirm the debated agenda item is visible to a regular user', async () => {
            await logout(page);
            await loginAsStdUser(page);
            await gotoConsultationHome(page, true, 'parteitag', 'parteitag');
            await expect(page.locator('.currentDebateAdmin').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.currentDebateInline .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await expect(
                page.locator('.currentDebateInline .debatedItem .title'),
            ).toContainText('Sonstiges', { timeout: 5000 });
        });
    });
});
