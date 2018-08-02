import {IMotion} from "./IMotion";

export class Amendment extends IMotion {
    public consultationId: number;
    public motionId: number;
    public motionSlug: number;
    public motionTitle: string;
    public motionTitlePrefix: string;
    public statusString: string;
    public statusFormatted: string;

    constructor(data) {
        super();
        Object.keys(data).forEach((key) => {
            this[key] = data[key];
        });
        this.type = 'motion';
    }
}
