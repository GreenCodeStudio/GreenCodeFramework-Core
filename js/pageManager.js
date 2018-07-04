export const pageManager = {
    initPage(initInfo) {
        console.log(initInfo);
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
    _loadedEvent(controller = null, method = null,) {
        if (this._onLoad[controller][method])
            for (var callback of this._onLoad[controller][method])
                callback();
        if (this._onLoad[controller][method])
            for (var callback of this._onLoad[controller][null])
                callback();
        if (this._onLoad[controller][method])
            for (var callback of this._onLoad[null][null])
                callback();
    }
};