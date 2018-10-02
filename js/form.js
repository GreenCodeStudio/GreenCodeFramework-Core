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
            if(elem.type=='checkbox'){
                if(!elem.checked)
                    continue;
            }
            let nameParsed = /^([^\]]+)\[/.exec(elem.name);
            if (nameParsed) {
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
            else
                data[elem.name] = elem.value;
        }
        let task = new AjaxTask();
        task.newTask(form.dataset.controller, form.dataset.method, data);
        task.start();
        e.preventDefault();
        return false;
    }
};