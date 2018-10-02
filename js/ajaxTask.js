import {Ajax} from "./ajax";
import "./domExtensions";

export class AjaxTask {
    constructor() {


    }

    newTask(controller, method, ...args) {
        this.controller = controller;
        this.method = method;
        this.args = args;
        this.identificator = 'task-' + (new Date * 1) + '-' + Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
        AjaxTask.tasks[this.identificator] = this;
        localStorage[this.identificator] = JSON.stringify({controller, method, args});
        localStorage[this.identificator + '-state'] = 'notstarted';
        this.generateHtml();
    }

    restore(identificator) {
        let obj = JSON.parse(localStorage[identificator]);
        this.controller = obj.controller;
        this.method = obj.method;
        this.args = obj.args;
        this.identificator = identificator;
        AjaxTask.tasks[this.identificator] = this;
        this.generateHtml();
    }

    get state() {
        return localStorage[this.identificator + '-state'];
    }

    get stateText() {
        let states = {notstarted: 'Nie rozpoczeto', started: 'Rozpoczeto', error: 'Błąd'};
        return states[this.state] || 'Zakończono';
    }

    generateHtml() {
        this.html = document.create('div', {className: 'task'});
        this.html.add('div', {text: 'Zapis formularza'});
        this.statusHtml = this.html.add('div', {text: this.stateText});
        let tasksList = document.querySelector('.tasksList');
        tasksList.insertBefore(this.html, tasksList.firstChild);
    }

    refreshHtml() {
        this.statusHtml.textContent = this.stateText;
if(!this.state){
setTimeout(()=>{this.html.remove()},1000);
}
    }

    start() {
        localStorage[this.identificator + '-state'] = 'started';
        this.ajax = Ajax(this.controller, this.method, ...this.args);
        this.refreshHtml();
        this.ajax.then(data => {
            delete localStorage[this.identificator];
            delete localStorage[this.identificator + '-state'];
            delete AjaxTask.tasks[this.identificator];
            this.refreshHtml();
        });
        this.ajax.catch(ex => {
            this.statusHtml.textContent = 'Błąd';
            localStorage[this.identificator + '-state'] = 'error';
            this.refreshHtml();
        });
    }

    static refresh() {
        if (!document.querySelector('.tasks'))
            return;
        for (let identificator in AjaxTask.tasks) {
            let task = AjaxTask.tasks[identificator];
            task.refreshHtml();
        }
        for (let identificator in localStorage) {
            if (/^task-[0-9]+-[0-9a-fA-F]+$/.exec(identificator)) {
                if (!AjaxTask.tasks[identificator]) {
                    let task = new AjaxTask();
                    task.restore(identificator);
                }
            }
        }
    }
}

AjaxTask.tasks = {};

function TaskFunction(controller, method, ...args) {
    let task = new AjaxTask(controller, method, ...args);
    task.newtask(controller, method, ...args);
    task.start();
    return task;
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
