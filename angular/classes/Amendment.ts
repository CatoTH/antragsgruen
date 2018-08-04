import {IMotion} from "./IMotion";

export class Amendment extends IMotion {
    public consultationId: number;
    public motionId: number;
    public motionSlug: string;
    public motionTitle: string;
    public motionTitlePrefix: string;
    public statusString: string;
    public statusFormatted: string;

    constructor(data) {
        super();
        Object.keys(data).forEach((key) => {
            this[key] = data[key];
        });
        this.type = 'amendment';
    }

    public getLink(linkKey: string, linkTemplates: { [key: string]: string }): string {
        let template = linkTemplates[linkKey];
        return template
            .replace(/0123456789/, this.id)
            .replace(/_MOTION_SLUG_/, this.motionSlug)
    }
}
