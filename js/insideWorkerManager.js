export const InsideWorkerManager = {
    modules: {},
    currentInstance: null,
    addModule(moduleName, constructorsObject) {
        console.log(`Module ${moduleName} added to InsideWorkerManager with constructors:`, constructorsObject);
        this.modules[moduleName] = constructorsObject;
    },
    listen() {
        addEventListener("message",e => {
            const {action} = e.data;
            if (action === 'createInstance') {
                console.log('InsideWorkerManager received createInstance message:', e.data);
                this.currentInstance = new this.modules[e.data.module][e.data.className](...e.data.args);
            }else if (action === 'callMethod') {
                console.log('InsideWorkerManager received callMethod message:', e.data);
                const result = this.currentInstance[e.data.methodName](...e.data.args);
                postMessage({requestId: e.data.requestId, result});
            }
        })
    }
}

InsideWorkerManager.listen()
