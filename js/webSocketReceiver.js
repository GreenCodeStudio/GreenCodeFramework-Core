let listeners = [];

let connection;
if(document.location.host=='localhost'){
    connection = new WebSocket('ws://localhost:'+webSocketPort);
}else {
    connection = new WebSocket(((location.protocol == "https:") ? 'wss://' : 'ws://') + location.host + '/websocket');
}
connection.onmessage = msg => WebSocketReceiver.messageReceived(msg);



export class WebSocketReceiver {
    static addListener(path, callback) {
        listeners.push(({path, callback}))
        return callback;
    }

    static removeListener(callback) {
        let finded = listeners.filter(x => x.callback === callback);
        finded.forEach(x => listeners.splice(listeners.indexOf(x), 1));
    }

    static messageReceived(msg) {
        let data = JSON.parse(msg.data);
        listeners.filter(x => this.containsPath(data.path, x.path)).forEach(x => x.callback());
    }

    static containsPath(whole, pattern) {
        for (let i = 0; i < pattern.length; i++) {
            if (whole[i] != pattern[i]) return false;
        }
        return true;
    }
}