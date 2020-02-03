import {Ajax} from "./ajax";
import "prototype-extensions";

export class AjaxTask {
    constructor() {
        this._onCompleted = [];
    }

    newTask(controller, method, ...args) {
        this.controller = controller;
        this.method = method;
        this.args = args;
        this.identificator = 'task-' + (new Date * 1) + '-' + Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
        AjaxTask.tasks[this.identificator] = this;
        sessionStorage[this.identificator] = JSON.stringify({controller, method, args});
        sessionStorage[this.identificator + '-state'] = 'notstarted';
        this.generateHtml();
    }

    restore(identificator) {
        let obj = JSON.parse(sessionStorage[identificator]);
        this.controller = obj.controller;
        this.method = obj.method;
        this.args = obj.args;
        this.identificator = identificator;
        AjaxTask.tasks[this.identificator] = this;
        this.generateHtml();
    }

    get state() {
        return sessionStorage[this.identificator + '-state'];
    }

    get stateText() {
        let states = {notstarted: 'Nie rozpoczeto', started: 'Rozpoczeto', offline: 'Offline', error: 'Błąd'};
        return states[this.state] || 'Zakończono';
    }

    generateHtml() {
        this.html = document.create('div.task', {data: {status: this.state}});
        this.html.addChild('div', {text: 'Zapis formularza'});
        this.statusHtml = this.html.addChild('div.status', {text: this.stateText});
        this.buttonsHtml = this.html.addChild('div.buttons');
        this._htmlButtons();
        let tasksList = document.querySelector('.tasksList');
        tasksList.insertBefore(this.html, tasksList.firstChild);
        AjaxTask.refreshMainState();
    }

    _htmlButtons() {
        this.buttonsHtml.children.removeAll();
        if (this.state == 'offline') {
            let btnDelete = this.buttonsHtml.addChild('div.button', {text: 'Anuluj'});
            btnDelete.onclick = () => {
                this._delete(true);
            };
            let btnRefresh = this.buttonsHtml.addChild('div.button', {text: 'Powtórz'});
            btnRefresh.onclick = () => {
                this.start();
            };
        }
    }

    refreshHtml() {
        this.html.dataset.status = this.state;
        this.statusHtml.textContent = this.stateText;
        this._htmlButtons();
        AjaxTask.refreshMainState();
        if (!this.state) {
            setTimeout(() => {
                this.html.remove()
            }, 1000);
        }
    }

    _delete(force = false) {
        delete sessionStorage[this.identificator];
        delete sessionStorage[this.identificator + '-state'];
        delete AjaxTask.tasks[this.identificator];
        if (force)
            this.html.remove();
        else
            this.refreshHtml();
        AjaxTask.refreshMainState();
    }

    start() {
        if (!navigator.onLine) {
            sessionStorage[this.identificator + '-state'] = 'offline';
            AjaxTask.refreshMainState();
            this.refreshHtml();
            return;
        }
        sessionStorage[this.identificator + '-state'] = 'started';
        this.ajax = Ajax(this.controller, this.method, ...this.args);
        this.refreshHtml();
        this.ajax.then(data => {
            this._onCompleted.forEach(x => x());
            this._delete();
        });
        this.ajax.catch(ex => {
            console.log(ex);
            this.statusHtml.textContent = 'Błąd';
            sessionStorage[this.identificator + '-state'] = 'error';
            this.refreshHtml();
        });
        AjaxTask.refreshMainState();
    }

    static refresh() {
        if (!document.querySelector('.tasks'))
            return;
        for (let identificator in AjaxTask.tasks) {
            let task = AjaxTask.tasks[identificator];
            task.refreshHtml();
        }
        for (let identificator in sessionStorage) {
            if (/^task-[0-9]+-[0-9a-fA-F]+$/.exec(identificator)) {
                if (!AjaxTask.tasks[identificator]) {
                    let task = new AjaxTask();
                    task.restore(identificator);
                }
            }
        }
        this.refreshMainState();
    }

    static refreshMainState() {
        let hasSomething = false;
        for (let identificator in AjaxTask.tasks) {
            let task = AjaxTask.tasks[identificator];
            if (task.state) hasSomething = true;
        }
        if (hasSomething)
            document.querySelector('.tasks').classList.add('open');
        else
            document.querySelector('.tasks').classList.remove('open');
    }

    then(fun) {
        if (!this.state) {
            fun();
        } else {
            this._onCompleted.push(fun);
        }
    }

    static startNewTask(controller, method, ...args) {
        let obj = new this();
        obj.newTask(controller, method, ...args);
        obj.start();
        return obj;
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
