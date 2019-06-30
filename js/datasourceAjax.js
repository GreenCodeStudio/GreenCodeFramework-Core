import {Ajax} from "./ajax";
import {WebSocketReceiver} from "./webSocketReceiver";

export class datasourceAjax {
    constructor(controller, method, webSocketPath = []) {
        this.controller = controller;
        this.method = method;
        if (webSocketPath.length > 0 && webSocketPath[0].toString().trim() !== "") {
            WebSocketReceiver.addListener(webSocketPath, () => {
                console.log('updated');
                if (this.onchange) {
                    this.onchange();
                }
            })
        }
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