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

    public getLink(linkKey: string, linkTemplates: { [key: string]: string }): string {
        let template = linkTemplates[linkKey];
        return template
            .replace(/0123456789/, this.id)
            .replace(/_SLUG_/, this.slug)
    }
}
