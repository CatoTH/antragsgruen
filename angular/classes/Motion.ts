import {IMotion} from "./IMotion";

export class Motion extends IMotion {
    public consultationId: number;
    public title: string;
    public slug: string;
    public statusString: string;
    public statusFormatted: string;

    constructor(data) {
        super();
        Object.keys(data).forEach((key) => {
            this[key] = data[key];
        });
        this.type = 'motion';
    }

    public getLink(linkKey: string, linkTemplates: { [key: string]: string }): string {
        const template = linkTemplates[linkKey];
        if (!template) {
            console.warn('Unknown link key:', linkKey);
            return '';
        }
        const slug = (this.slug ? this.slug : this.id);
        return template
            .replace(/0123456789/, this.id)
            .replace(/_SLUG_/, slug)
    }

    public getTitle(): string {
        return this.title;
    }
}
