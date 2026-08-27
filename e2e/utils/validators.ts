import { spawn } from 'node:child_process';
import { Page, expect, test } from '@playwright/test';
import * as fs from 'node:fs';
import * as os from 'node:os';

let vnuJarPath: string | null = null;
let pa11yPath: string | null = null;

function findVnuJar(): string | null {
    if (vnuJarPath) return vnuJarPath;
    const candidates = [
        process.env.VNU_JAR_PATH,
        '/usr/local/bin/vnu.jar',
        '/usr/bin/vnu.jar',
    ].filter((p): p is string => !!p);
    for (const c of candidates) {
        if (fs.existsSync(c)) {
            vnuJarPath = c;
            return c;
        }
    }
    return null;
}

function findPa11y(): string | null {
    if (pa11yPath) return pa11yPath;
    const candidates = [
        process.env.PA11Y_PATH,
        'node_modules/.bin/pa11y',
        '/usr/local/bin/pa11y',
    ].filter((p): p is string => !!p);
    for (const c of candidates) {
        if (fs.existsSync(c) || (c === 'pa11y' && which('pa11y'))) {
            pa11yPath = c;
            return c;
        }
    }
    return null;
}

function which(cmd: string): string | null {
    const path = process.env.PATH || '';
    for (const dir of path.split(':')) {
        const full = `${dir}/${cmd}`;
        if (fs.existsSync(full)) return full;
    }
    return null;
}

function execFile(
    cmd: string,
    args: string[],
    timeoutMs: number = 60_000,
): Promise<{ stdout: string; stderr: string }> {
    return new Promise((resolve, reject) => {
        const proc = spawn(cmd, args, { stdio: ['ignore', 'pipe', 'pipe'] });
        let stdout = '';
        let stderr = '';
        proc.stdout.on('data', (d) => (stdout += d.toString()));
        proc.stderr.on('data', (d) => (stderr += d.toString()));
        const timer = setTimeout(() => proc.kill('SIGKILL'), timeoutMs);
        proc.on('close', (code) => {
            clearTimeout(timer);
            if (code === 0) resolve({ stdout, stderr });
            else reject(new Error(`${cmd} exited ${code}: ${stderr}`));
        });
        proc.on('error', (err) => {
            clearTimeout(timer);
            reject(err);
        });
    });
}

export async function validateHTML(
    page: Page,
    ignoreMessages: string[] = [],
): Promise<void> {
    const vnu = findVnuJar();
    if (!vnu) {
        test.skip(true, 'vnu.jar not available — skipping HTML validation');
        return;
    }

    const html = await page.content();
    const filename = `${os.tmpdir()}/html-validate-${process.pid}-${Date.now()}.html`;
    fs.writeFileSync(filename, html, 'utf-8');
    try {
        const { stdout } = await execFile('java', [
            '-Xss1024k',
            '-jar',
            vnu,
            '--format',
            'json',
            filename,
        ]);
        const data = JSON.parse(stdout);
        const messages: any[] = data?.messages ?? [];
        const errors: string[] = [];
        const lines = html.split('\n');
        for (const msg of messages) {
            if (msg.type !== 'error') continue;
            const formatted =
                `- Line ${msg.lastLine}, column ${msg.lastColumn}: ${msg.message}\n` +
                `  > ${lines[msg.lastLine - 1] ?? ''}`;
            const ignored = ignoreMessages.some((s) =>
                formatted.toLowerCase().includes(s.toLowerCase()),
            );
            if (!ignored) errors.push(formatted);
        }
        if (errors.length > 0) {
            throw new Error(`Invalid HTML:\n${errors.join('\n')}`);
        }
    } finally {
        try {
            fs.unlinkSync(filename);
        } catch {
        }
    }
}

export async function validatePa11y(
    page: Page,
    standard: 'WCAG2A' | 'WCAG2AA' | 'WCAG2AAA' | 'Section508' = 'WCAG2AA',
    ignoreMessages: string[] = [],
): Promise<void> {
    const pa11y = findPa11y();
    if (!pa11y) {
        test.skip(true, 'pa11y not available — skipping accessibility validation');
        return;
    }

    const url = page.url();
    const { stdout } = await execFile(pa11y, ['-s', standard, '-r', 'json', url]);
    let data: any[];
    try {
        data = JSON.parse(stdout);
    } catch {
        throw new Error(`Invalid pa11y output: ${stdout}`);
    }
    const errors: string[] = [];
    for (const msg of data) {
        if (msg.type !== 'error') continue;
        const text =
            `${msg.code}\n${msg.selector}: ${msg.context}\n${msg.message}`;
        const ignored = ignoreMessages.some((s) =>
            text.toLowerCase().includes(s.toLowerCase()),
        );
        if (!ignored) errors.push(text);
    }
    if (errors.length > 0) {
        throw new Error(`Failed ${standard} check:\n\n${errors.join('\n\n')}`);
    }
}

export { expect };