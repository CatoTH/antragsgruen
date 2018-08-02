import {MotionTag} from "./MotionTag";
import {IMotion} from "./IMotion";

export class Motion extends IMotion {
    public consultationId: number;
    public title: string;
    public slug: string;
    public statusString: string;
    public statusFormatted: string;
    public tags: MotionTag[];

    constructor(data) {
        super();
        Object.keys(data).forEach((key) => {
            this[key] = data[key];
        });
        this.type = 'motion';
    }
}
