export class WorkerCaller {
    static createInstance(module, className, ...args) {
        const worker = new Worker('/dist/workers.js');
        worker.postMessage({action: 'createInstance', module, className, args}, this._extractTransferableObjects(args));
        return new WorkerCaller(worker);
    }

    constructor(worker) {
        this.worker = worker;
        this.requestId = 0;
        this.callbacks = {};
        this.worker.onmessage = (event) => {
            const {requestId, result, error} = event.data;
            const callback = this.callbacks[requestId];
            if (callback) {
                if (error) {
                    callback.reject(error);
                } else {
                    callback.resolve(result);
                }
                delete this.callbacks[requestId];
            }
        };
    }

    callMethod(methodName, ...args) {
        const requestId = this.requestId++;
        return new Promise((resolve, reject) => {
            this.callbacks[requestId] = {resolve, reject};
            this.worker.postMessage({
                action: 'callMethod',
                requestId,
                methodName,
                args
            }, WorkerCaller._extractTransferableObjects(args));
        });
    }

    static _extractTransferableObjects(args) {
        return args.filter(arg => arg instanceof ArrayBuffer || arg instanceof OffscreenCanvas);
    }
}
