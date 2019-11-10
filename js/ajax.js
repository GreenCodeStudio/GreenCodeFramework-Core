import ConsoleCheating from 'console-cheating';

function showServerDebug(decoded) {
    if (decoded.debug) {
        for (let dump of decoded.debug) {
            ConsoleCheating.eval("console.log.apply(null,data)", "", dump.backtrace[0].file, dump.backtrace[0].line, dump.vars);
        }
    }
}

function AjaxFunction(controller, method, ...args) {
    return new Promise((resolve, reject) => {
        var xhr = new XMLHttpRequest();
        xhr.open('post', '/ajax/' + controller + '/' + method);

        const postData = new FormData();
        let argCounter=0;
        for (const arg of args) {
            if (arg instanceof File)
                postData.append(`args[${argCounter}]`, arg);
            else
                postData.append(`args[${argCounter}]`, JSON.stringify(arg));

            argCounter++;
        }
        xhr.setRequestHeader('x-js-origin', 'true');
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
                } else {
                    try {
                        let decoded = JSON.parse(xhr.responseText);
                        showServerDebug(decoded);
                        ConsoleCheating.eval("console.error.apply(null,data)", "", decoded.error.stack[0].file, decoded.error.stack[0].line, [decoded.error.message + '%o', decoded.error]);
                        reject(decoded.error)
                    } catch (ex) {
                        reject(new Error('Http status:' + xhr.status + ' ' + xhr.statusText));
                    }
                }
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