import {STATUS} from "./Status";

export class Translations {
    public static get(category: string, key: string): string {
        if (!window['ANTRAGSGRUEN_TRANSLATIONS'] || !window['ANTRAGSGRUEN_TRANSLATIONS'][category]) {
            console.warn('Translations not correctly loaded', category);
        }
        if (window['ANTRAGSGRUEN_TRANSLATIONS'][category][key] === undefined) {
            console.warn('Unknown translation key: ', category, key);
        }
        return window['ANTRAGSGRUEN_TRANSLATIONS'][category][key];
    }

    public static getStatusName(status: number): string {
        switch (status) {
            case STATUS.DELETED:
                return this.get('structure', 'STATUS_DELETED');
            case STATUS.WITHDRAWN:
                return this.get('structure', 'STATUS_WITHDRAWN');
            case STATUS.WITHDRAWN_INVISIBLE:
                return this.get('structure', 'STATUS_WITHDRAWN_INVISIBLE');
            case STATUS.DRAFT:
                return this.get('structure', 'STATUS_DRAFT');
            case STATUS.SUBMITTED_UNSCREENED:
                return this.get('structure', 'STATUS_SUBMITTED_UNSCREENED');
            case STATUS.SUBMITTED_UNSCREENED_CHECKED:
                return this.get('structure', 'STATUS_SUBMITTED_UNSCREENED_CHECKED');
            case STATUS.SUBMITTED_SCREENED:
                return this.get('structure', 'STATUS_SUBMITTED_SCREENED');
            case STATUS.ACCEPTED:
                return this.get('structure', 'STATUS_ACCEPTED');
            case STATUS.REJECTED:
                return this.get('structure', 'STATUS_REJECTED');
            case STATUS.MODIFIED_ACCEPTED:
                return this.get('structure', 'STATUS_MODIFIED_ACCEPTED');
            case STATUS.PROCESSED:
                return this.get('structure', 'STATUS_PROCESSED');
            case STATUS.INLINE_REPLY:
                return this.get('structure', 'STATUS_INLINE_REPLY');
            case STATUS.COLLECTING_SUPPORTERS:
                return this.get('structure', 'STATUS_COLLECTING_SUPPORTERS');
            case STATUS.DRAFT_ADMIN:
                return this.get('structure', 'STATUS_DRAFT_ADMIN');
            case STATUS.MERGING_DRAFT_PUBLIC:
                return this.get('structure', 'STATUS_MERGING_DRAFT_PUBLIC');
            case STATUS.MERGING_DRAFT_PRIVATE:
                return this.get('structure', 'STATUS_MERGING_DRAFT_PRIVATE');
            case STATUS.PROPOSED_MODIFIED_AMENDMENT:
                return this.get('structure', 'STATUS_PROPOSED_MODIFIED_AMENDMENT');
            case STATUS.REFERRED:
                return this.get('structure', 'STATUS_REFERRED');
            case STATUS.OBSOLETED_BY:
                return this.get('structure', 'STATUS_OBSOLETED_BY');
            case STATUS.CUSTOM_STRING:
                return this.get('structure', 'STATUS_CUSTOM_STRING');
            case STATUS.MODIFIED:
                return this.get('structure', 'STATUS_MODIFIED');
            case STATUS.ADOPTED:
                return this.get('structure', 'STATUS_ADOPTED');
            case STATUS.COMPLETED:
                return this.get('structure', 'STATUS_COMPLETED');
            case STATUS.VOTE:
                return this.get('structure', 'STATUS_VOTE');
            case STATUS.PAUSED:
                return this.get('structure', 'STATUS_PAUSED');
            case STATUS.MISSING_INFORMATION:
                return this.get('structure', 'STATUS_MISSING_INFORMATION');
            case STATUS.DISMISSED:
                return this.get('structure', 'STATUS_DISMISSED');
            default:
                console.warn('Unknown status: ', status);
                return '@UNKNOWN@';
        }
    }

    public static getTagName(id: string): string {
        const name = this.get('tags', id);
        if (name === undefined) {
            return '@UNKNOWN@';
        } else {
            return name;
        }
    }
}
