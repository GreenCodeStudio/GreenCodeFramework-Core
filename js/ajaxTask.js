import {Ajax} from "./ajax";
import "./domExtensions";

export class AjaxTask {
    constructor(controller, method, ...args) {
        this.html = document.create('div', {className: 'task'});
        this.html.add('div', {text: 'Zapis formularza'});
        this.statusHtml = this.html.add('div', {text: 'Rozpoczęto'});
        let tasks = document.querySelector('.tasks');
        tasks.insertBefore(this.html, tasks.firstChild);
        this.ajax = Ajax(controller, method, ...args);
        this.ajax.then(data => {
            this.statusHtml.textContent = 'Zakończono';
        });
        this.ajax.catch(ex => {
            this.statusHtml.textContent = 'Błąd';
        })
    }
}

function TaskFunction(controller, method, ...args) {
    return new AjaxTask(controller, method, ...args);
}

const ControllerHandler = {
    get(taskFunctionBinded, name) {
        return taskFunctionBinded.bind(window, name);
    }
};
const TaskHandler = {
    get(obj, name) {
        return new Proxy(TaskFunction.bind(window, name), ControllerHandler);
    }
};
export const AjaxTaskManager = new Proxy(TaskFunction, TaskHandler);
