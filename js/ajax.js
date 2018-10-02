function showServerDebug(decoded) {
    if (decoded.debug) {
        for (let dump of decoded.debug) {
            let params=[...dump.vars, dump.backtrace];
            console.log.apply(null,params);
        }
    }
}

function AjaxFunction(controller, method, ...args) {
    return new Promise((resolve, reject) => {
        var xhr = new XMLHttpRequest();
        xhr.open('post', '/ajax/' + controller + '/' + method);
        var postData = '';
        for (var arg of args) {
            postData += '&args[]=' + encodeURIComponent(JSON.stringify(arg));
        }
        xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = e => {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    try {
                        let decoded = JSON.parse(xhr.responseText);
                        showServerDebug(decoded);
                        if (!decoded.error)
                            resolve(decoded.data);
                        else {
                            decoded.error.data = decoded.data;
                            reject(decoded.error)
                        }
                    } catch (ex) {
                        reject(ex);
                    }
                } else
                    reject(new Error('Http status:' + xhr.status + ' ' + xhr.statusText));
            }
        };
        xhr.send(postData);
    });
}

const ControllerHandler = {
    get(ajaxFunctionBinded, name) {
        return ajaxFunctionBinded.bind(window, name);
    }
};
const AjaxHandler = {
    get(obj, name) {
        return new Proxy(AjaxFunction.bind(window, name), ControllerHandler);
    }
};
export const Ajax = new Proxy(AjaxFunction, AjaxHandler);