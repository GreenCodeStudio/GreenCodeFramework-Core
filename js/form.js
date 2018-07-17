import {AjaxTask} from './ajaxTask';

export const formManager = {
    load(form, data) {
        let formElements = form.elements;
        for (var elem of formElements) {
            if (data[elem.name]) {
                elem.value = data[elem.name];
            } else {
                elem.value = '';
            }
        }
    },
    initEvents() {
        document.querySelectorAll('form.dataForm').forEach(form => form.addEventListener('submit', event => this.formSubmitted(event, form)));
    },
    formSubmitted(e, form) {
        var data = {};
        let formElements = form.elements;
        for (var elem of formElements) {
            data[elem.name] = elem.value;
        }
        new AjaxTask(form.dataset.controller, form.dataset.method, data);
        e.preventDefault();
        return false;
    }
};