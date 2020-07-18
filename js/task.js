import {ListRenderer} from "./utils/listRenderer";

export class Task {
    constructor(name, method) {
        this._status = new TaskStatus();
        this.name = name;
        this._method = method;
        this.start();
    }

    changed(callback) {
        this._status.changed(callback);
    }

    async start() {
        if (this._status.state != 'notStarted')
            throw new Error('task already started');

        this._status.setState('pending');
        try {
            let data = await this._method(this._status);
            this._status.setResult(data);
        } catch (ex) {
            this._status.setException(ex);
        }
    }

    get state() {
        return this._status.state;
    }

    get stateText() {
        return this._status.stateText;
    }

    static Register(task) {
        Task.all.push(task);
        Task.renderer.render();
    }
}

Task.all = [];
Task.renderers = new WeakMap();
Task.renderer = new ListRenderer(document.body.addChild('.tasksList'), (task) => {
    if (!Task.renderers.has(task))
        Task.renderers.set(task, new TaskRenderer(task));
    return Task.renderers.get(task).html;
}, Task.all)

class TaskRenderer {
    constructor(task) {
        this.task = task;
        this.html = document.create('div.task');
        this.status = this.html.addChild('.status');
        let rest = this.html.addChild('.rest');
        this.name = rest.addChild('.name');
        this.statusText = rest.addChild('.statusText');
        this.render();
        task.changed(() => this.render());
    }

    render() {
        this.name.textContent = this.task.name;
        this.statusText.textContent = this.task.statusText;
        this.status.dataset.value = this.task.status;
    }
}

export class TaskStatus {
    constructor() {
        this.state = 'notStarted';
        this.stateText = '';
        this.data = null;
        this.error = null;
        this._changed = [];
    }

    changed(callback) {
        this._changed.push(callback);
    }

    _notifyChange() {
        for (let callback of this._changed) {
            try {
                callback();
            } catch (ex) {
                console.error(ex);
            }
        }
    }

    setState(state, stateText = '') {
        this.state = state;
        this.stateText = stateText;
        this._notifyChange();
    }

    setException(ex) {
        this.state = 'error';
        this.stateText = '';
        this.error = ex;
        this._notifyChange();
    }

    setResult(data) {
        this.state = 'success';
        this.stateText = '';
        this.data = data;
        this._notifyChange();
    }
}