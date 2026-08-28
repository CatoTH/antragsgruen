import { test, expect } from '../../fixtures';

test.describe('Amendments: DeleteAsAdmin', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test.skip(true, 'placeholder legacy test - not implemented yet');
});