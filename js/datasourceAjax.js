import {Ajax} from "./ajax";

export class datasourceAjax {
    constructor(controller, method) {
        this.controller = controller;
        this.method = method;
    }

    async get(options) {
        return await Ajax(this.controller, this.method, {
            start: options.start,
            limit: options.limit,
            search: options.search,
            sort: options.sort
        });
    }
}