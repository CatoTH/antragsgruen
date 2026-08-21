import { test, expect } from '../../fixtures';
import { RestAuth } from '../../utils/auth';
import { setApiEnabled } from '../../utils/test-api';
import * as fs from 'fs';
import * as path from 'path';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';
const FIXTURE_PATH = path.join(__dirname, '..', '..', 'fixtures', 'api', 'api-motion-types-default.json');

test.describe('API: Motion creation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('admin can update motion type and create motion via REST', async ({ request }) => {
        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);
        const auth = new RestAuth(request);
        const adminToken = await auth.asStdAdmin();

        const response1 = await request.get(`/${SUBDOMAIN}/rest/${CONSULTATION}/motion-types`, {
            headers: { Authorization: `Bearer ${adminToken}` },
        });
        expect(response1.status()).toBe(200);

        const expectedMotionTypeData = fs.readFileSync(FIXTURE_PATH, 'utf-8');
        const actualBody = JSON.stringify(await response1.json());
        expect(actualBody).toBe(expectedMotionTypeData.trim());

        const typeUpdateData = {
            policies: {
                motions: { id: 'all' },
                amendments: { id: 'all' },
                comments: { id: 'all' },
                support_motions: { id: 'logged_in' },
                support_amendments: { id: 'nobody' },
            },
            motion_support_types: ['support'],
            motion_initiator_settings: {
                type: 'collecting_supporters',
                initiator_can_be_person: true,
                initiator_can_be_organization: true,
                person_policy: { id: 'all' },
                organization_policy: { id: 'all' },
                min_supporters: 1,
                allow_more_supporters: true,
                allow_supporting_after_publication: false,
                offer_non_public_supports: false,
                has_organizations: true,
                contact_name: 'none',
                contact_email: 'required',
                contact_phone: 'optional',
                contact_gender: 'none',
                has_resolution_date: 'required',
            },
        };

        const response2 = await request.patch(
            `/${SUBDOMAIN}/rest/${CONSULTATION}/motion-types/1`,
            {
                data: typeUpdateData,
                headers: {
                    Authorization: `Bearer ${adminToken}`,
                    'Content-Type': 'application/json',
                },
            },
        );
        expect(response2.status()).toBe(200);

        const updatedType = await response2.json();
        expect(updatedType.policies.support_motions.id).toBe('logged_in');

        const userToken = await new RestAuth(request).asStdUser();

        const motionTitle = 'Testing REST motion creation';
        const motionText = '<p>This is the motion text, submitted via the REST API.</p>';
        const motionReason = '<p>This is the reason for the motion.</p>';

        const createData = {
            motion_type_id: 1,
            agenda_item_id: null,
            sections: [
                { section_id: 1, data: motionTitle },
                { section_id: 2, data: motionText },
                { section_id: 3, data: motionReason },
            ],
            initiators: [
                {
                    person_type: 'person',
                    name: 'Rest Testuser',
                    organization: 'Test Organization',
                    contact_email: 'rest-testuser@example.org',
                    contact_phone: '+49 30 12345678',
                },
            ],
        };

        const response3 = await request.post(`/${SUBDOMAIN}/rest/${CONSULTATION}/motion`, {
            data: createData,
            headers: {
                Authorization: `Bearer ${userToken}`,
                'Content-Type': 'application/json',
            },
        });
        expect(response3.status()).toBe(201);

        const created = await response3.json();
        expect(created.type).toBe('motion');
        expect(typeof created.id).toBe('number');
        expect(created.id).toBeGreaterThan(0);
        expect(created.status_id).toBe(15);
        expect(created.title).toBe(motionTitle);
        expect(created.url_json).toBeTruthy();

        const response4 = await request.get(created.url_json, {
            headers: { Authorization: `Bearer ${userToken}` },
        });
        expect(response4.status()).toBe(200);
        const fetched = await response4.json();

        expect(fetched.id).toBe(created.id);
        expect(fetched.title).toBe(motionTitle);
        expect(fetched.status_id).toBe(15);

        const sectionsByTitle: Record<string, any> = {};
        for (const section of fetched.sections) {
            sectionsByTitle[section.title] = section;
        }
        expect(sectionsByTitle['Antragstext'].type).toBe('TextSimple');
        expect(sectionsByTitle['Antragstext'].html).toBe(
            `<div class="text motionTextFormattings textOrig">${motionText}</div>`,
        );
        expect(sectionsByTitle['Begründung'].html).toBe(
            `<div class="text motionTextFormattings textOrig">${motionReason}</div>`,
        );

        expect(fetched.initiators).toHaveLength(1);
        expect(fetched.initiators[0].type).toBe('person');
        expect(fetched.initiators[0].name).toBe('Rest Testuser');
        expect(fetched.initiators[0].organization).toBe('Test Organization');
        expect(fetched.supporters).toEqual([]);
        expect(fetched.amendment_links).toEqual([]);

        const createdCopy = { ...created };
        const fetchedCopy = { ...fetched };
        delete createdCopy.pagination;
        delete fetchedCopy.pagination;
        expect(createdCopy).toEqual(fetchedCopy);

        const selfSupportResponse = await request.post(
            `${created.url_json}/support`,
            {
                data: { name: 'Testuser', organization: 'Test Organization' },
                headers: {
                    Authorization: `Bearer ${userToken}`,
                    'Content-Type': 'application/json',
                },
            },
        );
        expect(selfSupportResponse.status()).toBe(403);
        const selfSupportError = await selfSupportResponse.json();
        expect(selfSupportError.success).toBe(false);

        const fixedDataToken = await new RestAuth(request).asFixedDataUser();
        const supportResponse = await request.post(
            `${created.url_json}/support`,
            {
                data: { name: 'Fixed Data', organization: 'MotionTools' },
                headers: {
                    Authorization: `Bearer ${fixedDataToken}`,
                    'Content-Type': 'application/json',
                },
            },
        );
        expect(supportResponse.status()).toBe(200);

        const supported = await supportResponse.json();
        expect(supported.supporters).toHaveLength(1);
        expect(supported.supporters[0].name).toBe('Fixed Data');
    });
});
