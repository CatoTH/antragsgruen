import { Page, expect } from '@playwright/test';

/**
 * Follows a feed link and checks its body, standing in for the Cepts'
 * `$I->click('.feedAll'); $I->seeInPageSource(...)`.
 *
 * The feed is fetched rather than navigated to: seeInPageSource() inspects the raw response, and
 * clicking an RSS link in a real browser may hand the response to a download instead of rendering
 * it, at which point there is no page source left to assert on.
 */
export async function expectFeedContains(
    page: Page,
    selector: string,
    needles: string[],
): Promise<void> {
    const href = await page.locator(selector).first().getAttribute('href');
    expect(href, `feed link ${selector} has no href`).not.toBeNull();
    const response = await page.request.get(new URL(href as string, page.url()).toString());
    expect(response.ok(), `feed ${selector} returned ${response.status()}`).toBeTruthy();
    const body = await response.text();
    for (const needle of needles) {
        expect(body, `feed ${selector} does not contain ${needle}`).toContain(needle);
    }
}
