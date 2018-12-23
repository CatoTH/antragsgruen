import {CollectionItem} from "./CollectionItem";
import {Person, PERSON_TYPE_NATURAL, PERSON_TYPE_ORGANIZATION} from "./Person";
import {STATUS} from "./Status";
import {MotionTag} from "./MotionTag";

export abstract class IMotion extends CollectionItem {
    public titlePrefix: string;
    public status: number;
    public initiators: Person[];
    public supporters: Person[];
    public dateCreation: string;
    public tags: MotionTag[];

    public static compareTitlePrefix(imotion1: IMotion, imotion2: IMotion) {
        if (imotion1.titlePrefix < imotion2.titlePrefix) {
            return -1;
        } else if (imotion1.titlePrefix > imotion2.titlePrefix) {
            return 1;
        } else {
            return 0;
        }
    }

    public isScreenable(): boolean {
        return ([
            STATUS.DRAFT,
            STATUS.DRAFT_ADMIN,
            STATUS.SUBMITTED_UNSCREENED,
            STATUS.SUBMITTED_UNSCREENED_CHECKED
        ].indexOf(this.status) !== -1)
    }

    abstract getTitle(): string;

    public getInitiatorName(): string {
        if (this.initiators.length === 0) {
            return '';
        }
        return this.initiators.map(person => {
            if (person.type === PERSON_TYPE_NATURAL) {
                return person.name;
            }
            if (person.type === PERSON_TYPE_ORGANIZATION) {
                return person.organization;
            }
        }).join(', ');
    }
}
