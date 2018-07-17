import {Ajax} from "./ajax";

function TaskFunction(controller, method, ...args) {
    this.ajax= Ajax(controller, method, ...args);
    return this;
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
export const AjaxTask = new Proxy(TaskFunction, TaskHandler);