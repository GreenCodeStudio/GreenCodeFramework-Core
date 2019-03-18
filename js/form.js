import {AjaxTask} from './ajaxTask';
import {pageManager} from "../../Core/js/pageManager";

export const formManager = {
    load(form, data) {
        let formElements = form.elements;
        for (var elem of formElements) {
            let value = this.parseFormItemNameRead(elem, data);
            if (elem.type == 'checkbox') {
                elem.checked = value
            } else {
                if (value == undefined)
                    elem.value = '';
                else
                    elem.value = value;

            }
        }
    }, loadSelects(data) {
        let selects = document.querySelectorAll('select[data-foreign-key]');
        for (let select of selects) {
            if (data[select.dataset.foreignKey]) {
                select.children.removeAll();
                for (var option of data[select.dataset.foreignKey]) {
                    select.addChild('option', {value: option.id, text: option.title});
                }
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
        if (form.dataset.goto) {
            task.then(() => {
                pageManager.goto(form.dataset.goto)
            });
        }
        e.preventDefault();
        return false;
    }
};