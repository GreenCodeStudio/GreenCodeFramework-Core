import ConsoleCheating from 'console-cheating';

function showServerDebug(decoded) {
    if (decoded.debug) {
        for (let dump of decoded.debug) {
            ConsoleCheating.eval("console.log.apply(null,data)", "", dump.backtrace[0].file, dump.backtrace[0].line, dump.vars);
        }
    }
}

function generatePostBody(args) {
    const body = new FormData();
    for (let i = 0; i < args.length; i++) {
        const argument = args[i];
        if (argument instanceof File || argument instanceof Blob)
            body.append(`args[${i}]`, argument);
        else
            body.append(`args[${i}]`, JSON.stringify(argument));
    }
    return body;
}

function AjaxFunction(controller, method, ...args) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('post', '/ajax/' + controller + '/' + method);
        const body = generatePostBody(args);
        xhr.setRequestHeader('x-js-origin', 'true');
        xhr.onreadystatechange = e => {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
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
        xhr.send(body);
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