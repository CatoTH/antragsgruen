import {IMotion} from "./IMotion";

export class Amendment extends IMotion {
    public consultationId: number;
    public motionId: number;
    public motionSlug: string;
    public motionTitle: string;
    public motionTitlePrefix: string;
    public statusString: string;
    public statusFormatted: string;

    constructor(data: object) {
        super();
        Object.keys(data).forEach((key: string) => {
            this[key] = data[key];
        });
        this.type = 'amendment';
        this.tags = []; // @TODO
    }

    public getLink(linkKey: string, linkTemplates: { [key: string]: string }): string {
        const template = linkTemplates[linkKey];
        if (!template) {
            console.warn('Unknown link key:', linkKey);
            return '';
        }
        const slug = (this.motionSlug ? this.motionSlug : this.motionId.toString(10));
        return template
            .replace(/0123456789/, this.id)
            .replace(/_MOTION_SLUG_/, slug)
    }

    public getTitle(): string {
        return this.motionTitle;
    }
}
