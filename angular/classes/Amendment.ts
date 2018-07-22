import {Person} from "./Person";

export class Amendment {
    public id: string;
    public consultationId: number;
    public motionId: number;
    public motionSlug: number;
    public motionTitle: string;
    public motionTitlePrefix: string;
    public titlePrefix: string;
    public status: number;
    public statusString: string;
    public statusFormatted: string;
    public initiators: Person[];
    public dateCreation: string;

    constructor(data) {
        Object.keys(data).forEach((key) => {
            this[key] = data[key];
        })
    }
}
