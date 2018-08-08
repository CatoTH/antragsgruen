import {CollectionItem} from "./CollectionItem";
import {Person} from "./Person";
import {STATUS} from "./Status";

export abstract class IMotion extends CollectionItem {
    public titlePrefix: string;
    public status: number;
    public initiators: Person[];
    public dateCreation: string;

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
}
