import { Page, expect, Locator } from '@playwright/test';

const CKEDITOR_READY_TIMEOUT = 10_000;

async function waitForCkEditorInstance(
    page: Page,
    instanceName: string,
): Promise<void> {
    await page.waitForFunction(
        (name) => {
            const w = window as any;
            return (
                typeof w.CKEDITOR !== 'undefined' &&
                w.CKEDITOR.instances &&
                w.CKEDITOR.instances[name] &&
                w.CKEDITOR.instances[name].status === 'ready'
            );
        },
        instanceName,
        { timeout: CKEDITOR_READY_TIMEOUT },
    );
}

export async function setCkEditorContent(
    page: Page,
    instanceName: string,
    html: string,
): Promise<void> {
    await waitForCkEditorInstance(page, instanceName);
    await page.evaluate(
        ({ name, content }) => {
            const w = window as any;
            const editor = w.CKEDITOR.instances[name];
            editor.setData(content, () => {
                const el = resolve(editor);
                if (el) {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            function resolve(ed: any): HTMLElement | null {
                const c = ed.element;
                if (!c) return null;
                if (typeof c.dispatchEvent === 'function') return c as HTMLElement;
                if (c.$ && typeof c.$.dispatchEvent === 'function') return c.$ as HTMLElement;
                return null;
            }
        },
        { name: instanceName, content: html },
    );
}

export async function appendCkEditorContent(
    page: Page,
    instanceName: string,
    html: string,
): Promise<void> {
    await waitForCkEditorInstance(page, instanceName);
    await page.evaluate(
        ({ name, content }) => {
            const w = window as any;
            const editor = w.CKEDITOR.instances[name];
            const current = editor.getData();
            editor.setData(current + content, () => {
                const el = resolve(editor);
                if (el) {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            function resolve(ed: any): HTMLElement | null {
                const c = ed.element;
                if (!c) return null;
                if (typeof c.dispatchEvent === 'function') return c as HTMLElement;
                if (c.$ && typeof c.$.dispatchEvent === 'function') return c.$ as HTMLElement;
                return null;
            }
        },
        { name: instanceName, content: html },
    );
}

export async function getCkEditorContent(
    page: Page,
    instanceName: string,
): Promise<string> {
    await waitForCkEditorInstance(page, instanceName);
    return page.evaluate((name) => {
        const w = window as any;
        return w.CKEDITOR.instances[name].getData();
    }, instanceName);
}

export async function replaceInCkEditor(
    page: Page,
    instanceName: string,
    find: string | RegExp,
    replacement: string,
): Promise<void> {
    await waitForCkEditorInstance(page, instanceName);
    await page.evaluate(
        ({ name, findStr, findFlags, replacement }) => {
            const w = window as any;
            const editor = w.CKEDITOR.instances[name];
            const re =
                findFlags !== null
                    ? new RegExp(findStr, findFlags)
                    : new RegExp(findStr.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
            const next = editor.getData().replace(re, replacement);
            editor.setData(next, () => {
                const el = resolve(editor);
                if (el) {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            function resolve(ed: any): HTMLElement | null {
                const c = ed.element;
                if (!c) return null;
                if (typeof c.dispatchEvent === 'function') return c as HTMLElement;
                if (c.$ && typeof c.$.dispatchEvent === 'function') return c.$ as HTMLElement;
                return null;
            }
        },
        {
            name: instanceName,
            findStr: typeof find === 'string' ? find : find.source,
            findFlags: typeof find === 'string' ? null : find.flags,
            replacement,
        },
    );
}

export async function focusCkEditor(
    page: Page,
    instanceName: string,
): Promise<void> {
    await waitForCkEditorInstance(page, instanceName);
    await page.evaluate((name) => {
        const w = window as any;
        w.CKEDITOR.instances[name].focus();
    }, instanceName);
}

export async function dispatchClick(
    page: Page,
    selector: string,
): Promise<void> {
    await page.evaluate((sel) => {
        const el = document.querySelector(sel);
        if (el) {
            el.dispatchEvent(
                new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
            );
        }
    }, selector);
}

export async function dispatchChange(
    page: Page,
    selector: string,
): Promise<void> {
    await page.evaluate((sel) => {
        const el = document.querySelector(sel);
        if (el) {
            el.dispatchEvent(
                new Event('change', { bubbles: true, cancelable: true }),
            );
        }
    }, selector);
}

export async function expectBootboxDialog(
    page: Page,
    text: string | RegExp,
    selector: string = '.bootbox',
): Promise<Locator> {
    const locator = page.locator(selector).filter({ hasText: text as any });
    await expect(locator).toBeVisible({ timeout: 5_000 });
    return locator;
}

export async function acceptBootbox(
    page: Page,
    selector: string = '.bootbox .btn-primary',
): Promise<void> {
    await page.locator(selector).first().click();
    await page.locator('.bootbox').waitFor({ state: 'detached', timeout: 5_000 }).catch(() => {
    });
}

export async function cancelBootbox(
    page: Page,
    selector: string = '.bootbox .btn-default, .bootbox .btn-secondary, .bootbox .btn-cancel',
): Promise<void> {
    await page.locator(selector).first().click();
}