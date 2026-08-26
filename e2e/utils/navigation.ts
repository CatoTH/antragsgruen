import { Page, expect } from '@playwright/test';
import { DEFAULT_CONSULTATION_PATH, DEFAULT_SUBDOMAIN } from './constants';
import { clearLocalStorage, loginAsStdAdmin } from './auth';
import { DBFixture } from './test-api';
import { AdminIndexPage } from '../pages/AdminIndexPage';
import { AdminMotionListPage } from '../pages/AdminMotionListPage';
import { AmendmentPage } from '../pages/AmendmentPage';
import { ConsultationHomePage } from '../pages/ConsultationHomePage';
import { ContentPage } from '../pages/ContentPage';
import { MotionCreatePage } from '../pages/MotionCreatePage';
import { MotionPage } from '../pages/MotionPage';

/**
 * Navigation helpers mirroring the methods Tests\Support\AcceptanceTester provides
 * for the Codeception suite, so specs read the same way as the Cepts they were ported from.
 */

export async function gotoConsultationHome(
    page: Page,
    check: boolean = true,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<ConsultationHomePage> {
    const home = new ConsultationHomePage(page);
    await home.open({ subdomain, consultationPath: path });
    if (check && subdomain === DEFAULT_SUBDOMAIN && path === DEFAULT_CONSULTATION_PATH) {
        await expect(page.locator('h1')).toContainText('Test2');
    }
    return home;
}

export async function initializeAndGoHome(
    page: Page,
    db: DBFixture,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<ConsultationHomePage> {
    await db.populate('dbdata1');
    const home = await gotoConsultationHome(page, true, subdomain, path);
    await clearLocalStorage(page);
    return home;
}

export async function gotoMotion(
    page: Page,
    check: boolean = true,
    motionSlug: string = '321-o-zapft-is',
): Promise<MotionPage> {
    const motion = new MotionPage(page);
    await motion.open({ motionSlug });
    if (check) {
        await expect(motion.dataContainer).toBeVisible();
    }
    return motion;
}

export async function gotoAmendment(
    page: Page,
    check: boolean = true,
    motionSlug: string = '321-o-zapft-is',
    amendmentId: number = 1,
): Promise<AmendmentPage> {
    const amendment = new AmendmentPage(page);
    await amendment.open({ motionSlug, amendmentId });
    if (check) {
        await expect(amendment.dataContainer).toBeVisible();
    }
    return amendment;
}

export async function gotoMotionCreatePage(
    page: Page,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
    motionTypeId: number = 1,
): Promise<MotionCreatePage> {
    const create = new MotionCreatePage(page);
    await create.open({ subdomain, consultationPath: path, motionTypeId });
    return create;
}

export async function gotoContentPage(
    page: Page,
    pageSlug: string,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<ContentPage> {
    const content = new ContentPage(page);
    await content.open({ subdomain, consultationPath: path, pageSlug });
    return content;
}

export async function gotoStdAdminPage(
    page: Page,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<AdminIndexPage> {
    const admin = new AdminIndexPage(page);
    await admin.open({ subdomain, consultationPath: path });
    return admin;
}

export async function gotoMotionList(page: Page): Promise<AdminMotionListPage> {
    await page.locator('#motionListLink').click();
    await expect(page.locator('h1')).toContainText(/Liste: Anträge, Änderungsanträge/i);
    return new AdminMotionListPage(page);
}

export async function loginAndGotoStdAdminPage(
    page: Page,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<AdminIndexPage> {
    await gotoConsultationHome(page, false, subdomain, path);
    await loginAsStdAdmin(page);
    return gotoStdAdminPage(page, subdomain, path);
}

export async function loginAndGotoMotionList(
    page: Page,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<AdminMotionListPage> {
    await gotoConsultationHome(page, false, subdomain, path);
    await loginAsStdAdmin(page);
    return gotoMotionList(page);
}

/**
 * The dbdata1 fixture ships std-parteitag with the "Currently debated" module switched on, and that
 * module takes the place of the separate speaking list and voting sections on the home page. Tests
 * covering those separate sections switch it off first. Requires an admin session.
 */
export async function disableCurrentlyDebated(
    page: Page,
    subdomain: string = DEFAULT_SUBDOMAIN,
    path: string = DEFAULT_CONSULTATION_PATH,
): Promise<void> {
    // Reached by clicking through the admin index rather than by opening the URL directly: right
    // after a login, a blind navigation can still race the login response and end up submitting
    // the form with a CSRF token from the previous session.
    const admin = await gotoStdAdminPage(page, subdomain, path);
    const appearance = await admin.gotoAppearance();
    await page.locator('#hasCurrentlyDebated').uncheck();
    await appearance.saveForm();
}
