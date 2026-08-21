import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout, loginAsYfjUser } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';
import { setUserVoted } from '../../utils/test-api';

async function clickJS(page: import('@playwright/test').Page, selector: string): Promise<void> {
    await dispatchClick(page, selector);
}

test.describe('YfjVoting', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata-yfj');
    });

    test('create and open a Roll Call, mark presences, set up an actual voting', async ({ page, request }) => {
        await page.goto('/std/yfj-test');

        await loginAsStdAdmin(page);
        await page.locator('#votingsLink').click();
        await expect(page.locator('body')).toContainText('No votings are open');
        await page.locator('#sidebar .admin a').click();
        await page.waitForTimeout(200);

        await clickJS(page, '.createRollCall');
        await page.locator('#roll_call_number').fill('1');
        await page.locator('#roll_call_name').fill('Friday evening');
        await expect(page.locator('#roll_call_create_groups')).toBeChecked();
        await page
            .locator('.createRollCallForm [type="submit"]')
            .click();
        await page.waitForTimeout(200);

        await expect(page.locator('#voting1')).toContainText('Roll Call 1 (Friday evening)');
        await expect(page.locator('#voting1')).toContainText(
            'Simple majority (15 out of 29 users)',
        );
        await expect(page.locator('#voting1')).toContainText(
            'Voting IS set up as YFJ Roll Call',
        );

        await clickJS(page, '#voting1 .btnOpen');
        await page.waitForTimeout(200);

        await logout(page);

        await loginAsYfjUser(page, 'ingyo-full', 0);
        await page.waitForTimeout(200);
        await clickJS(page, '.voting_question_1 .btnPresent');
        await page.waitForTimeout(200);
        await expect(page.locator('.voting')).toContainText('1 user has marked their presence');

        const present: Array<[string, number]> = [
            ['ingyo-full', 7],
            ['nyc-full', 7],
            ['ingyo-full-nov', 1],
            ['nyc-full-nov', 0],
            ['ingyo-ob', 1],
            ['nyc-ob', 1],
            ['ingyo-can', 1],
            ['nyc-can', 1],
        ];
        for (const [orgaName, number] of present) {
            for (let i = 1; i <= number; i++) {
                await setUserVoted(request, {
                    email: `${orgaName}-${i}@example.org`,
                    votingBlock: 1,
                    itemId: 1,
                    answer: 'present',
                });
            }
        }

        await logout(page);
        await loginAsStdAdmin(page);
        await page.locator('#votingsLink').click();
        await expect(page.locator('body')).toContainText('20 presences have been marked');
        await page.locator('#sidebar .admin a').click();
        await page.waitForTimeout(200);

        await expect(page.locator('body')).toContainText('16 out of 15 necessary votes');

        await clickJS(page, '#voting1 .btnClose');
        await page.waitForTimeout(200);
        await expect(page.locator('body')).toContainText('Quorum reached');

        await clickJS(page, '#voting1 .btnShowVotes');
        await clickJS(
            page,
            '#voting1 .voteListHolder49 .userGroupSetter .userGroupSetterOpener',
        );
        await page
            .locator('#voting1 .voteListHolder49 .userGroupSetter select')
            .selectOption('64');
        await clickJS(page, '#voting1 .voteListHolder49 .userGroupSetterDo');
        await clickJS(
            page,
            '#voting1 .voteListHolder48 .userGroupSetter .userGroupSetterOpener',
        );
        await page
            .locator('#voting1 .voteListHolder48 .userGroupSetter select')
            .selectOption('65');
        await clickJS(page, '#voting1 .voteListHolder48 .userGroupSetterDo');

        await clickJS(page, '.votingOperations .createYfjVoting');
        await page.locator('#voting_number').fill('1');
        await page.locator('#voting_title').fill('Should we agree?');
        await page.locator('.createYfjVotingForm [type="submit"]').click();

        await page.waitForTimeout(200);
        await expect(page.locator('#voting2')).toContainText('Should we agree?');
        await expect(page.locator('#voting2')).toContainText(
            'Who may vote: Voting 1: NYC, Voting 1: INGYO',
        );
        await clickJS(page, '#voting2 .btnOpen');
        await page.waitForTimeout(200);

        const voters: Array<[string, string]> = [
            ['ingyo-full-1', 'yes'],
            ['ingyo-full-2', 'yes'],
            ['ingyo-full-3', 'no'],
            ['nyc-full-1', 'yes'],
            ['nyc-full-2', 'yes'],
            ['nyc-full-3', 'no'],
        ];
        for (const [email, answer] of voters) {
            await setUserVoted(request, {
                email: `${email}@example.org`,
                votingBlock: 2,
                itemId: 2,
                answer,
            });
        }

        await page.reload();
        await page.waitForTimeout(200);
    });
});