export const pageManager = {
    initPage(initInfo) {
        console.log(initInfo);
        let page = document.querySelector('.page');
        this._loadedEvent(page, initInfo.data, initInfo.controllerName, initInfo.methodName);
    },
    _onLoad: {},
    onLoad(callback, controller = null, method = null) {
        if (!this._onLoad[controller]) {
            this._onLoad[controller] = {};
        }
        if (!this._onLoad[controller][method]) {
            this._onLoad[controller][method] = [];
        }
        this._onLoad[controller][method].push(callback);
    },
    _loadedEvent(page, data, controller = null, method = null) {
        if (this._onLoad[controller] && this._onLoad[controller][method])
            for (let callback of this._onLoad[controller][method])
                callback(page, data);
        if (this._onLoad[controller] && this._onLoad[controller][null])
            for (let callback of this._onLoad[controller][null])
                callback(page, data);
        if (this._onLoad[null] && this._onLoad[null][null])
            for (let callback of this._onLoad[null][null])
                callback(page, data);
    },
    goto(url) {
        return new Promise((resolve, reject) => {
            let xhr = new XMLHttpRequest();
            xhr.open('get', url);
            xhr.setRequestHeader('x-json', 1);
            xhr.send();
        });
    }
};