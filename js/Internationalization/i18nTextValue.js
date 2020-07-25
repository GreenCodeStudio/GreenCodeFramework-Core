import {I18nValue} from "./i18nValue";

export class I18nTextValue extends I18nValue {
    constructor(value) {
        super();
        this.value = value;
    }

    toString() {
        return this.value;
    }
}