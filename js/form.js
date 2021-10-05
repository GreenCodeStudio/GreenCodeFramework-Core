export class FormManager {
    constructor(form) {
        this.form = form;
        form.addEventListener('submit', event => this.formSubmitted(event));
    }

    load(data) {
        const formElements = this.form.elements;
        for (const elem of formElements) {
            let value = this.parseFormItemNameRead(elem, data);
            if (elem.type == 'checkbox') {
                elem.checked = value && value != '0'
            } else if (elem.type == 'datetime-local') {
                if (value)
                    elem.value = value.replace(' ', 'T');
                else
                    elem.value = '';
            } else {
                if (value == undefined)
                    elem.value = '';
                else
                    elem.value = value;
            }
        }
    }

    loadSelects(data) {
        const selects = this.form.querySelectorAll('select[data-foreign-key]');
        for (const select of selects) {
            if (data[select.dataset.foreignKey]) {
                select.children.removeAll();
                for (const option of data[select.dataset.foreignKey]) {
                    select.addChild('option', {value: option.id, text: option.title});
                }
            }
        }
    }

    parseFormItemNameWrite(elem, data, value) {
        let nameParsed = /^([^\[]+)\[?/.exec(elem.name);
        if (!nameParsed)
            return null;
        let obj = data;
        let nameLeft = elem.name.replace(/^[^\]]+\[/, '[');
        while (/^\[[^\]]*\]/.test(nameLeft)) {
            if (!(obj[nameParsed[1]] instanceof Object)) {
                obj[nameParsed[1]] = {};
            }
            obj = obj[nameParsed[1]];
            nameParsed = /^\[([^\]]*)\]/.exec(nameLeft);
            nameLeft = nameLeft.replace(/^\[[^\]]*\]/, '');
        }
        obj[nameParsed[1]] = elem.value;
    }

    parseFormItemNameRead(elem, data, value) {
        let nameParsed = /^([^\[]+)\[?/.exec(elem.name);
        if (!nameParsed)
            return null;
        let obj = data;
        let nameLeft = elem.name.replace(/^[^\]]+\[/, '[');
        while (/^\[[^\]]*\]/.test(nameLeft)) {
            if (!(obj[nameParsed[1]] instanceof Object)) {
                return null
            }
            obj = obj[nameParsed[1]];
            nameParsed = /^\[([^\]]*)\]/.exec(nameLeft);
            nameLeft = nameLeft.replace(/^\[[^\]]*\]/, '');
        }
        return obj[nameParsed[1]];
    }

    getData() {
        let data = {};
        let formElements = this.form.elements;
        for (var elem of formElements) {
            if (elem.type == 'checkbox' || elem.type == 'radio') {
                if (!elem.checked)
                    continue;
            }
            this.parseFormItemNameWrite(elem, data, elem.value);
        }
        return data;
    }

    formSubmitted(e) {
        e.preventDefault();
        this.submit(this.getData());
    }

    /**
     * @abstract
     * @param data
     */
    submit(data) {
    }

    reset() {
        this.form.reset();
    }
}