import { Page, expect, Locator } from '@playwright/test';
import { BasePage } from './BasePage';
import { setCkEditorContent } from '../utils/dom';

export class MotionCreatePage extends BasePage {
    protected route = 'motion/create';

    get heading(): Locator {
        return this.page.locator('h1').filter({ hasText: /antrag stellen/i });
    }

    async createMotion(
        title: string = 'Testantrag 1',
        screeningNeeded: boolean = false,
    ): Promise<void> {
        await this.fillInValidSampleData(title);
        await this.saveForm();
        await expect(
            this.page.locator('h1').filter({ hasText: /antrag bestätigen/i }),
        ).toBeVisible();
        await this.page
            .locator('#motionConfirmForm [name="confirm"]')
            .click();
        if (screeningNeeded) {
            await expect(
                this.page.locator('h1').filter({ hasText: /antrag eingereicht/i }),
            ).toBeVisible();
        } else {
            await expect(
                this.page.locator('h1').filter({ hasText: /antrag veröffentlicht/i }),
            ).toBeVisible();
        }
    }

    async fillInValidSampleData(
        title: string = 'Testantrag 1',
        selectTag: boolean = true,
    ): Promise<void> {
        if (selectTag) {
            await this.page
                .locator("input[name='tags[]'][value='1']")
                .check();
        }
        await this.page.locator("[name='sections[1]']").fill(title);
        await setCkEditorContent(
            this.page,
            'sections_2_wysiwyg',
            '<p><strong>Test</strong></p>',
        );
        await setCkEditorContent(
            this.page,
            'sections_3_wysiwyg',
            '<p><strong>Test 2</strong></p>',
        );
        await this.page.locator('#initiatorPrimaryName').fill('Mein Name');
        await this.page.locator('#initiatorEmail').fill('test@example.org');
    }

    async saveForm(): Promise<void> {
        await this.page.locator('#motionEditForm [name="save"]').click();
    }
}