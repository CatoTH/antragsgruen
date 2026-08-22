import { test, expect, Page } from '../../fixtures';
import { setCkEditorContent, dispatchClick, dispatchChange } from '../../utils/dom';
import { setUserVoted } from '../../utils/test-api';
import { logout, loginAsStdAdmin, loginAsYfjUser } from '../../utils/auth';

async function loginAsDbwvTestUser(page: Page, username: string): Promise<void> {
    await page.locator('#loginLink').click();
    await page.locator('h1').filter({ hasText: /Login/i }).waitFor();
    await page.locator('#username').fill(`${username}@example.org`);
    await page.locator('#passwordInput').fill('Test');
    await page
        .locator('#usernamePasswordForm [name="loginusernamepassword"]')
        .click();
    await page.locator('#logoutLink').waitFor({ state: 'visible' });
}

async function clickJS(page: Page, selector: string): Promise<void> {
    await dispatchClick(page, selector);
}

async function trigerChangeJS(page: Page, selector: string): Promise<void> {
    await dispatchChange(page, selector);
}

test.describe('DbwvStdFlow', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata-dbwv');
    });

    test('full DBWV motion flow through all roles', async ({ page, request }) => {
        await page.goto('/std/lv-sued');
        await loginAsDbwvTestUser(page, 'lv-sued-antragsberechtigt-0');
        await expect(page.locator('#dbwvUserLoginPanel')).toContainText('Antragsberechtigte');
        await expect(page.locator('.btnCreateMotion')).toContainText('Antrag stellen');
        await expect(page.locator('.myMotionList')).toHaveCount(0);
        await page.locator('.btnCreateMotion').click();

        await page.locator("[name='sections[1]']").fill('Testantrag');
        await setCkEditorContent(
            page,
            'sections_2_wysiwyg',
            '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.</p><p>Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>',
        );
        await setCkEditorContent(
            page,
            'sections_3_wysiwyg',
            '<p>Es führt kein Weg an Lorem ipsum vorbei</p>',
        );
        await page.locator('#resolutionDate').fill('23.05.2020');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText(
            'Organisation-0 (dort beschlossen am: 23.05.2020)',
        );
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText('Sie haben den Antrag eingereicht.');
        await page.locator('#motionConfirmedForm [type="submit"]').click();
        await expect(page.locator('.myMotionList')).toContainText('Testantrag');
        await logout(page);

        await loginAsDbwvTestUser(page, 'al-recht');
        await expect(page.locator('#dbwvUserLoginPanel')).toContainText('AL Recht');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('#motionListLink').click();
        await expect(page.locator('.adminMotionListActions')).toHaveCount(0);
        await expect(page.locator('.actionCol')).toHaveCount(0);
        await page.locator('.motion1 .prefixCol a').click();
        await expect(page.locator('#dbwv_main_tagSelect')).toBeVisible();
        await page.evaluate(() => {
            const el = document.getElementById('dbwv_main_tagSelect') as HTMLSelectElement;
            el.value = '47';
        });
        await trigerChangeJS(page, '#dbwv_main_tagSelect');
        await expect(page.locator('.alert-success')).toContainText('Gespeichert');
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await logout(page);

        await loginAsDbwvTestUser(page, 'lv-sued-referat-iii');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await expect(page.locator('#dbwv_step1_subtagSelect')).toBeVisible();
        await expect(page.locator('#dbwv_step1_subtagNew')).toHaveCount(0);
        await page.evaluate(() => {
            const el = document.getElementById('dbwv_step1_subtagSelect') as HTMLSelectElement;
            el.value = 'new';
        });
        await trigerChangeJS(page, '#dbwv_step1_subtagSelect');
        await expect(page.locator('#dbwv_step1_subtagNew')).toBeVisible();
        await page.locator('#dbwv_step1_subtagNew').fill('Gehalt');
        await expect(page.locator('#dbwv_step1_prefix')).toHaveValue('III/01');
        await page.locator('#dbwv_step1_textchanges').check();
        await page.locator('#dbwv_step1_assign_number [type="submit"]').click();

        await expect(page.locator('h1')).toContainText('Antrag bearbeiten');
        await page.evaluate(() => {
            const w = window as any;
            const ed = w.CKEDITOR.instances['sections_2_wysiwyg'];
            ed.setData(ed.getData() + '<p>Third paragraph</p>');
        });
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [type="submit"]').click();
        await expect(page.locator('#dbwv_step1_assign_number')).toBeVisible();
        await expect(page.locator('#dbwv_assign_main_tag')).toBeVisible();
        await expect(page.locator('.motionHistory .currentVersion')).toContainText('V2');
        await clickJS(page, '.motionHistory .historyOpener button');
        await expect(page.locator('.motionHistory .otherVersion')).toContainText('V1');
        await page.locator('.motionHistory .currentVersion .changesLink a').click();
        await expect(page.locator('.inserted')).toContainText('Third paragraph');
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await logout(page);

        await page.goto('/std/lv-sued');
        await loginAsDbwvTestUser(page, 'lv-sued-antragsberechtigt-0');
        await expect(page.locator('.myMotionList')).toContainText('III/01: Testantrag');
        await page.locator('.myMotionList .motion1').click();
        await expect(page.locator('body')).toContainText(
            'Es führt kein Weg an Lorem ipsum vorbei',
        );
        await expect(page.locator('body')).not.toContainText('Third paragraph');
        await expect(page.locator('.motionHistory')).toHaveCount(0);
        await logout(page);

        await loginAsDbwvTestUser(page, 'lv-sued-bueroleitung');
        await page.goto('/std/lv-sued');
        await expect(page.locator('body')).not.toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await expect(page.locator('body')).not.toContainText('Testantrag');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('#adminTodo').click();
        await expect(page.locator('.motionScreen2')).toContainText('Antrag freischalten');
        await page.locator('.motionScreen2 a').click();
        await expect(page.locator('.motion2')).toContainText(
            'V2, Eingereicht (geprüft, unveröffentlicht)',
        );
        await expect(page.locator('.motion2')).toContainText('To Do: Antrag freischalten');
        await clickJS(page, '.adminMotionListActions .markAll');
        await page.locator('.motionListForm [name="screen"]').click();
        await expect(page.locator('.motion2')).toContainText('V2, Eingereicht');
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await expect(page.locator('body')).toContainText('Testantrag');

        await page.goto('/std/lv-sued');
        await expect(page.locator('.tagLink47')).toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await page.locator('.tagLink47').click();
        await expect(page.locator('body')).toContainText('Testantrag');
        await expect(page.locator('h1')).toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await logout(page);

        await loginAsDbwvTestUser(page, 'lv-sued-delegiert-0');
        await expect(page.locator('body')).toContainText('Testantrag');
        await expect(page.locator('h1')).toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await page.locator('.motionLink2').click();
        await expect(page.locator('body')).toContainText('Third paragraph');
        await expect(page.locator('.motionHistory')).toHaveCount(0);
        await logout(page);
        await page.goto('/std/lv-sued');

        await loginAsDbwvTestUser(page, 'lv-sued-antragsberechtigt-1');
        await expect(page.locator('.tagLink47')).toHaveCount(0);
        await expect(page.locator('body')).not.toContainText('Testantrag');
        await expect(page.locator('body')).not.toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await logout(page);

        await loginAsDbwvTestUser(page, 'lv-sued-antragsberechtigt-0');
        await expect(page.locator('body')).toContainText('Testantrag');
        await expect(page.locator('.tagLink47')).toHaveCount(0);
        await expect(page.locator('body')).not.toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await page.locator('.motion2').click();
        await expect(page.locator('body')).toContainText('Third paragraph');
        await expect(page.locator('.motionHistory .currentVersion')).toContainText('V2');
        await clickJS(page, '.motionHistory .historyOpener button');
        await expect(page.locator('.motionHistory .otherVersion')).toContainText('V1');
        await page.locator('.motionHistory .currentVersion .changesLink a').click();
        await expect(page.locator('.inserted')).toContainText('Third paragraph');
        await logout(page);
        await page.goto('/std/lv-sued');

        await loginAsDbwvTestUser(page, 'lv-sued-vorstand');
        await page.locator('.tagLink47').click();
        await page.locator('.motionLink2').click();
        await expect(page.locator('body')).toContainText('Third paragraph');
        await expect(page.locator('.motionHistory .currentVersion')).toContainText('V2');
        await clickJS(page, '.motionHistory .historyOpener button');
        await expect(page.locator('.motionHistory .otherVersion')).toContainText('V1');
        await logout(page);
        await page.goto('/std/lv-sued');

        await loginAsDbwvTestUser(page, 'lv-sued-referat-iii');
        await page.locator('.tagLink47').click();
        await page.locator('.motionLink2').click();
        await expect(page.locator('#dbwv_step1_assign_number')).toHaveCount(0);
        await logout(page);

        await loginAsDbwvTestUser(page, 'lv-sued-ausschuss-iii');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await expect(page.locator('#proposedChanges')).toHaveCount(0);
        await expect(page.locator('.motionHistory')).toHaveCount(0);
        await clickJS(page, '.proposedChangesOpener button');
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await page.waitForTimeout(300);
        await clickJS(page, '#proposedChanges .proposalStatus6 input');
        await clickJS(page, '#proposedChanges .saving button');
        await page.waitForTimeout(300);
        await page.evaluate(() => {
            const w = window as any;
            const ed = w.CKEDITOR.instances['sections_2_wysiwyg'];
            ed.setData(ed.getData().replace(/Third paragraph/, 'Dritter Absatz'));
        });
        await page.locator('#proposedChangeTextForm [name="save"]').click();
        await expect(page.locator('#pp_section_2')).not.toContainText('Lorem ipsum dolor');
        await expect(page.locator('#pp_section_2 .inserted')).toContainText('Dritter Absatz');
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges .proposalStatus6 input',
                    )?.disabled === false,
            ),
        ).toBe(true);
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.evaluate(() => {
            const el = document.querySelector<HTMLInputElement>(
                '#proposedChanges input[name="setPublicExplanation"]',
            );
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.evaluate(() => {
            const el = document.querySelector<HTMLTextAreaElement>(
                '#proposedChanges textarea[name="proposalExplanation"]',
            );
            if (el) el.value = 'Eine Erklärung';
        });
        await clickJS(page, '#proposedChanges .saving button');
        await page.waitForTimeout(500);
        await page.evaluate(() => {
            const el = document.querySelector<HTMLInputElement>(
                '#proposedChanges input[name="proposalVisible"]',
            );
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await clickJS(page, '#proposedChanges .saving button');
        await page.waitForTimeout(500);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges .proposalStatus6 input',
                    )?.disabled === true,
            ),
        ).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges input[name="proposalVisible"]',
                    )?.disabled === true,
            ),
        ).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLTextAreaElement>(
                        '#proposedChanges textarea[name="proposalExplanation"]',
                    )?.disabled === true,
            ),
        ).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges .notificationSettings .notifyProposer',
                    )?.disabled === false,
            ),
        ).toBe(true);
        await clickJS(page, '#proposedChanges .notificationSettings .notifyProposer');
        await expect(
            page.locator('#proposedChanges .notifyProposerSection textarea'),
        ).toBeVisible();
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await expect(page.locator('#pp_section_2')).toContainText('Lorem ipsum dolor');
        await expect(page.locator('.motionData .proposedStatusRow')).toContainText(
            'Eine Erklärung',
        );
        await logout(page);

        await loginAsDbwvTestUser(page, 'lv-sued-redaktion');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await clickJS(
            page,
            '#dbwv_step3_decide input[name="followproposal"][value="yes"]',
        );
        await clickJS(page, '#dbwv_step3_decide input[name="protocol_public"][value="1"]');
        await page.evaluate(() => {
            const w = window as any;
            w.CKEDITOR.instances['dbwv_step3_protocol_wysiwyg'].setData('<p>Wortprotokoll</p>');
        });
        await page.locator('#dbwv_step3_decide [type="submit"]').click();
        await expect(page.locator('.motionHistory')).toContainText('V4');
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await expect(page.locator('#pp_section_2')).toHaveCount(0);
        await expect(page.locator('#section_2_2')).toContainText('Dritter Absatz');
        await expect(page.locator('#section_2_2')).not.toContainText('Third paragraph');

        await page.goto('/std/lv-sued');
        await page.locator('.tagLink47').click();
        await expect(page.locator('.resolutionList .motionLink4')).toHaveCount(0);

        await page.goto('/std/lv-sued');
        await page.locator('#sidebarResolutions').click();
        await page.locator('.tagLink47').click();
        await expect(page.locator('.prefixCol')).toContainText('Beschlussnummer');
        await expect(page.locator('.motionLink4')).toContainText('Testantrag');
        await logout(page);

        await loginAsDbwvTestUser(page, 'koordinierungsausschuss');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('#adminTodo').click();
        await expect(page.locator('.todoDbwvMoveToMain4')).toContainText(
            'In die Hauptversammlung übernehmen',
        );
        await page.locator('.todoDbwvMoveToMain4 a').click();
        await clickJS(page, '.motionHistory .historyOpener button');
        await expect(page.locator('.motionHistory .otherVersion')).not.toContainText('V1');
        await expect(page.locator('.motionHistory .otherVersion')).toContainText('V2');
        await page.locator('#dbwv_step4_next [type="submit"]').click();
        await expect(page.locator('.motionHistory')).toContainText('V5');
        await expect(page.locator('.motionData .statusRow')).toContainText(
            'Eingereicht (ungeprüft)',
        );
        await page.locator('#motionListLink').click();
        await expect(page.locator('.motion5 .tagsCol')).toContainText('Gehalt (intern)');
        await expect(page.locator('.motion5 .tagsCol')).toContainText('Sachgebiet III');
        await logout(page);

        await loginAsDbwvTestUser(page, 'hv-arbeitsgruppe-iii');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('.motion5 .prefixCol a').click();
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await clickJS(page, '#proposedChanges .proposalStatus6 input');
        await clickJS(page, '#proposedChanges .saving button');
        await page.waitForTimeout(300);
        await page.evaluate(() => {
            const w = window as any;
            const ed = w.CKEDITOR.instances['sections_5_wysiwyg'];
            ed.setData(ed.getData().replace(/Dritter Absatz/, 'Vierter Absatz'));
        });
        await page.locator('#proposedChangeTextForm [name="save"]').click();
        await expect(page.locator('#pp_section_5')).not.toContainText('Lorem ipsum dolor');
        await expect(page.locator('#pp_section_5 ins')).toContainText('Vierter');
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges .proposalStatus6 input',
                    )?.disabled === false,
            ),
        ).toBe(true);
        await expect(page.locator('#adminTodo')).toHaveCount(0);
        await logout(page);

        await loginAsDbwvTestUser(page, 'hv-arbeitsgruppe-leitung');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('#adminTodo').click();
        await expect(page.locator('body')).toContainText('Verfahrensvorschlag veröffentlichen');
        await page.locator('.todoDbwvSetPp6 a').click();
        await page.evaluate(() => {
            const el = document.querySelector<HTMLInputElement>(
                '#proposedChanges input[name="proposalVisible"]',
            );
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await clickJS(page, '#proposedChanges .saving button');
        await page.waitForTimeout(500);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges .proposalStatus6 input',
                    )?.disabled === true,
            ),
        ).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges input[name="proposalVisible"]',
                    )?.disabled === true,
            ),
        ).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLTextAreaElement>(
                        '#proposedChanges textarea[name="proposalExplanation"]',
                    )?.disabled === true,
            ),
        ).toBe(true);
        expect(
            await page.evaluate(
                () =>
                    document.querySelector<HTMLInputElement>(
                        '#proposedChanges .notificationSettings .notifyProposer',
                    )?.disabled === false,
            ),
        ).toBe(true);
        await clickJS(page, '#proposedChanges .notificationSettings .notifyProposer');
        await expect(
            page.locator('#proposedChanges .notifyProposerSection textarea'),
        ).toBeVisible();
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await expect(page.locator('#pp_section_5')).toContainText('Lorem ipsum dolor');
        await logout(page);

        await loginAsDbwvTestUser(page, 'hv-bueroleitung');
        await page.goto('/std/hv');
        await expect(page.locator('body')).not.toContainText(
            'Sachgebiet III - Dienst- und Laufbahnrecht',
        );
        await expect(page.locator('body')).not.toContainText('Testantrag');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('#adminTodo').click();
        await expect(page.locator('.motionScreen6')).toContainText('Antrag freischalten');
        await page.locator('.motionScreen6 a').click();
        await expect(page.locator('.motion6')).toContainText('V6, Eingereicht (ungeprüft)');
        await expect(page.locator('.motion6')).toContainText('To Do: Antrag freischalten');
        await clickJS(page, '.adminMotionListActions .markAll');
        await page.locator('.motionListForm [name="screen"]').click();
        await expect(page.locator('.motion6')).toContainText('V6, Eingereicht');
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');

        await page.goto('/std/hv');
        await page.locator('.tagLink55').click();
        await expect(page.locator('.titleCol')).toContainText('Testantrag');
        await expect(page.locator('.initiatorCol')).toContainText(
            'Landesversammlung Süddeutschland',
        );
        await page.locator('.motionLink6').click();
        await logout(page);

        await loginAsDbwvTestUser(page, 'hv-redaktion');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await clickJS(
            page,
            '#dbwv_step6_decide input[name="followproposal"][value="yes"]',
        );
        await clickJS(page, '#dbwv_step6_decide input[name="protocol_public"][value="1"]');
        await page.evaluate(() => {
            const w = window as any;
            w.CKEDITOR.instances['dbwv_step6_protocol_wysiwyg'].setData('<p>Wortprotokoll</p>');
        });
        await page.locator('#dbwv_step6_decide [type="submit"]').click();
        await expect(page.locator('.motionHistory')).toContainText('V7');
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await expect(page.locator('#pp_section_5')).toHaveCount(0);
        await expect(page.locator('#section_5_2')).toContainText('Vierter Absatz');
        await expect(page.locator('#section_5_2')).not.toContainText('Dritter Absatz');

        await page.goto('/std/hv');
        await page.locator('.tagLink55').click();
        await expect(page.locator('.motionListTags .motionLink7')).toContainText('Testantrag');
        await page.locator('.motionLink7').click();
        await logout(page);

        await loginAsDbwvTestUser(page, 'hv-beschlussfassung');
        await expect(page.locator('#adminTodo')).toContainText('To Do (1)');
        await page.locator('#adminTodo').click();
        await expect(page.locator('body')).toContainText('Beschluss veröffentlichen');
        await page.locator('.todoDbwvPublishResolution7 a').click();
        await expect(page.locator('#dbwv_step7_prefix')).toHaveValue('III/01');
        await page.locator('#dbwv_step7_publish_resolution [type="submit"]').click();
        await expect(page.locator('#adminTodo')).not.toContainText('To Do (1)');
        await expect(page.locator('body')).not.toContainText('Begründung');
        await expect(page.locator('.motionHistory .currentVersion')).toContainText(
            'V8: Beschluss im Beschlussumdruck',
        );

        await page.goto('/std/hv');
        await page.locator('.tagLink55').click();
        await expect(page.locator('.motionListTags .motionLink7')).toContainText('Testantrag');

        await page.goto('/std/hv');
        await page.locator('#sidebarResolutions').click();
        await page.locator('.tagLink55').click();
        await expect(page.locator('.motionLink8')).toContainText('Testantrag');
    });
});