export function getFixtureBaseURL(value) {
    const match = typeof value === 'string' && /^http:\/\/127\.0\.0\.1:([0-9]{1,5})\/?$/.exec(value);
    const port = Number(match?.[1]);

    if (!port || port > 65535) {
        throw new Error('Set ANTRAGSGRUEN_PLAYWRIGHT_URL to a local fixture origin such as http://127.0.0.1:43123.');
    }

    return new URL(`http://127.0.0.1:${port}`).origin;
}
