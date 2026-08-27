import { test, expect } from '../../fixtures';

import { MotionPage } from '../../pages/MotionPage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: VotingResults', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enter a voting result for a motion', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.adminMotionTable .motion2 .titleCol a')
            .first()
            .click();

        await expect(page.locator('.votingDataHolder').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.votingDataOpener');
        await expect(page.locator('.votingDataHolder').first()).toBeVisible();
        await page.locator('#votesYes').first().fill('15');
        await page.locator('#votesNo').first().fill('5');
        await page.locator('#votesAbstention').first().fill('2');
        await page.locator('#votesInvalid').first().fill('0');
        await page.locator('#votesComment').first().fill('Accepted by mayority');

        await page.locator('#motionUpdateForm [name="save"]').click();
        await expect(page.locator('.votingDataHolder').first()).toBeVisible();

        await new MotionPage(page).open({ motionSlug: 2 });

        await expect(page.locator('.votingResultRow')).toContainText('Accepted by mayority');
        await expect(page.locator('.votingResultRow')).toContainText(
            'Ja: 15, Nein: 5, Enthaltungen: 2, Ungültig: 0',
        );
    });

    test('enter a voting result for an amendment', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page
            .locator('.adminMotionTable .amendment273 .titleCol a')
            .first()
            .click();

        await expect(page.locator('.votingDataHolder').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.votingDataOpener');
        await expect(page.locator('.votingDataHolder').first()).toBeVisible();
        await page.locator('#votesYes').first().fill('5');
        await page.locator('#votesNo').first().fill('7');
        await page.locator('#votesAbstention').first().fill('');
        await page.locator('#votesInvalid').first().fill('1');
        await page.locator('#votesComment').first().fill('Rejected');

        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: 273,
        });

        await expect(page.locator('.votingResultRow')).toContainText('Rejected');
        await expect(page.locator('.votingResultRow')).toContainText(
            'Ja: 5, Nein: 7, Ungültig: 1',
        );
    });
});