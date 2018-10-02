import {AjaxTask} from './ajaxTask';

export const formManager = {
    load(form, data) {
        let formElements = form.elements;
        for (var elem of formElements) {
            let value = this.parseFormItemNameRead(elem, data);
            if (elem.type == 'checkbox') {
                elem.checked = value
            } else {
                elem.value = value;

            }
        }
    },
    initEvents() {
        document.querySelectorAll('form.dataForm').forEach(form => form.addEventListener('submit', event => this.formSubmitted(event, form)));
    },
    parseFormItemNameWrite: function (elem, data, value) {
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
    },
    parseFormItemNameRead: function (elem, data, value) {
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
    }, formSubmitted(e, form) {
        var data = {};
        let formElements = form.elements;
        for (var elem of formElements) {
            if (elem.type == 'checkbox') {
                if (!elem.checked)
                    continue;
            }
            this.parseFormItemNameWrite(elem, data, elem.value);
        }
        let task = new AjaxTask();
        task.newTask(form.dataset.controller, form.dataset.method, data);
        task.start();
        e.preventDefault();
        return false;
    }
};