import {Person} from "./Person";
import {MotionTag} from "./MotionTag";

export class Motion {
    public id: string;
    public consultationId: number;
    public titlePrefix: string;
    public title: string;
    public status: number;
    public statusString: string;
    public statusFormatted: string;
    public initiators: Person[];
    public tags: MotionTag[];
    public dateCreation: string;

    constructor(data) {
        Object.keys(data).forEach((key) => {
            this[key] = data[key];
        })
    }
}
