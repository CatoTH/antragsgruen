import { Page } from '@playwright/test';

export interface UrlResponse {
    url: string;
    ok: boolean;
    error?: string;
}

export abstract class BasePage {
    protected abstract route: string | string[];

    constructor(protected readonly page: Page) {}

    async getUrl(params: Record<string, any> = {}): Promise<string> {
        const response = await this.page.request.post('/test/url-builder', {
            form: {
                route: this.route as string,
                params: JSON.stringify(params),
            },
        });
        if (!response.ok()) {
            throw new Error(
                `URL builder failed: ${response.status()} ${await response.text()}`,
            );
        }
        const data: UrlResponse = await response.json();
        if (!data.ok) {
            throw new Error(`URL builder error: ${data.error}`);
        }
        return data.url;
    }

    async open(params: Record<string, any> = {}): Promise<void> {
        const url = await this.getUrl(params);
        await this.page.goto(url);
    }
}
