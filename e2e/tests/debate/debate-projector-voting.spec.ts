import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';
import { gotoConsultationHome, gotoStdAdminPage } from '../../utils/navigation';

const VOTING_ID = `#voting${FIRST_FREE_VOTING_BLOCK_ID}`;

// The std-parteitag fixture ships with an ongoing debate on motion A2 ("O’zapft is!").
// Give that motion a voting and open it, so that the debate widget embeds it.

test.describe('Debate: DebateProjectorVoting', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('project the voting without anything that only concerns the person at the browser', async ({
        page,
    }) => {
        await gotoConsultationHome(page);
        await loginAsStdAdmin(page);
        await gotoConsultationHome(page);
        await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
            timeout: 5000,
        });
        // creates the voting for the debated motion
        await page.locator('.currentDebateAdmin .manageVotingBtn').click();
        await expect(page.locator('.currentDebateAdmin .votingTab .votingCard')).toBeVisible({
            timeout: 8000,
        });

        const admin = await gotoStdAdminPage(page);
        await admin.gotoVotingPage();

        // Give it a vote limit, so that the widget knows how many votes each account still has - the number
        // that must not end up on the projection. Has to happen before opening: settings are frozen then.
        await dispatchClick(page, `${VOTING_ID} .settingsToggleGroup .btn`);
        await dispatchClick(page, `${VOTING_ID} .votesMaxVotes .maxVotesAll input`);
        await expect(page.locator(`${VOTING_ID} .votesMaxVotesAll`).first()).toBeVisible();
        // Addressed by id rather than by position: the widgets are not in id order on the page
        await page.evaluate((votingBlockId) => {
            const w = window as any;
            w.votingAdminWidget.$refs['voting-admin-widget']
                .find((widget: any) => widget.voting.id === votingBlockId)
                .setMaxVotesRestrictionAll('2');
        }, String(FIRST_FREE_VOTING_BLOCK_ID));
        await dispatchClick(page, `${VOTING_ID} .votingSettings .btnSave`);

        await dispatchClick(page, `${VOTING_ID} .btnOpen`);

        await test.step('project the voting without anything that only concerns the person at the browser', async () => {
            await gotoConsultationHome(page);
            await expect(page.locator('.currentDebateAdmin .debatedItem')).toBeVisible({
                timeout: 5000,
            });
            await page.locator('.currentDebateAdmin .btnFullscreen').click();
            const projected = '.fullscreenMainHolder .currentDebateVoting';
            await expect(page.locator(`${projected} .voting`)).toBeVisible({ timeout: 8000 });

            // How many votes were cast is about the vote and stays...
            await expect(page.locator(`${projected} .votedCounter`)).toContainText('Status');
            // ...but how many votes *this* account has left, and its own vote weight, do not belong on a wall
            await expect(page.locator(projected).getByText('Du hast').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator(`${projected} .votingWeight`).filter({ visible: true })).toHaveCount(0);
            await expect(page.locator(`${projected} .votingOptions`).filter({ visible: true })).toHaveCount(0);
            // Neither do the links out of the projection: into the voting administration, or to the motion
            await expect(page.locator(`${projected} .votingsAdminLink`).filter({ visible: true })).toHaveCount(0);
            await expect(page.locator(`${projected} .glyphicon-new-window`).filter({ visible: true })).toHaveCount(0);

            // Navigating away tears the projector down; closing it via its button is covered by DebateWidgetsCept
            await gotoConsultationHome(page);
        });

        await test.step('still see those elements in the regular widget, which is about the person reading it', async () => {
            await logout(page);
            await loginAsStdUser(page);
            await gotoConsultationHome(page);
            const inline = '.currentDebateInline .currentDebateVoting';
            await expect(page.locator(`${inline} .voting`)).toBeVisible({ timeout: 8000 });
            await expect(page.locator(`${inline} .votingOptions`).first()).toBeVisible();
            await expect(page.locator(`${inline} .glyphicon-new-window`).first()).toBeVisible();
            await expect(page.locator(`${inline} .votedCounter`)).toContainText(
                'Du hast noch 2 Stimmen zu vergeben.',
            );
        });
    });
});
