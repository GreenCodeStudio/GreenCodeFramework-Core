import {Ajax} from "./ajax";
import {WebSocketReceiver} from "./webSocketReceiver";
import {TaskNotification} from "../../Notifications/js/TaskNotification";

export class DatasourceAjax {
    constructor(controller, method, webSocketPath = [], params = null, multiEditMethod = null) {
        this.controller = controller;
        this.method = method;
        this.params = params;
        this.multiEditChanges = {};
        this.multiEditMethod = multiEditMethod;
        if (webSocketPath.length > 0 && webSocketPath[0].toString().trim() !== "") {
            WebSocketReceiver.addListener(webSocketPath, () => {
                console.log('Datasource ajax updated');
                if (this.onchange) {
                    this.onchange();
                }
            })
        }
    }

    async get(options) {
        const ret = await Ajax(this.controller, this.method, {
            start: options.start,
            limit: options.limit,
            search: options.search,
            sort: options.sort,
            params: this.params
        });
        console.log('ssss')
        for (const row of ret.rows) {
            if (this.multiEditChanges[row.id]) {
                Object.assign(row, this.multiEditChanges[row.id]);
                row.__isMultirowEdited = true;
            }
        }
        return ret;
    }

    multiEditChanged(id, data, save = false) {
        console.log('ssssss')
        if (save) {

             TaskNotification.Create(async () => {
                 await Ajax(this.controller, this.multiEditMethod, [{id, data}]);
             }, "Zapisywanie", "Zapisano");
            delete this.multiEditChanges[id];
        } else {
            this.multiEditChanges[id] = data;
        }
    }
}