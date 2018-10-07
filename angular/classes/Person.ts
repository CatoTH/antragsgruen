export const PERSON_TYPE_NATURAL      = 0;
export const PERSON_TYPE_ORGANIZATION = 1;


export interface Person {
    type: number;
    name: string;
    organization: string;
    resolutionDate: string;
}
