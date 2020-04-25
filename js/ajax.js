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

class ConnectionError extends Error {
}

class HttpErrorCode extends Error {
    constructor(code, text) {
        super();
        this.code = code;
        this.text = text;
    }
}

function sleep(time) {
    return new Promise(resolve => setTimeout(resolve, time));
}

function onlinePromise() {
    if (navigator.onLine)
        return Promise.resolve();
    else
        return new Promise(resolve => {
            let handler = () => {
                resolve();
                removeEventListener('online', handler);
            }
            addEventListener('online', handler)
        })
}

async function AjaxFunction(controller, method, ...args) {
    const maxTime = 10 * 60 * 1000;
    let start = new Date();
    if (!navigator.onLine)
        await Promise.race([onlinePromise(), sleep(maxTime)])
    for (let i = 0; i < 10; i++) {
        try {
            return await TryOnceAjaxFunction(controller, method, ...args);
        } catch (ex) {
            if (new Date() - start > maxTime)
                throw ex;

            if (i > 1) {
                let sleepPromise = sleep(500 * Math.pow(2, i - 1));
                if (navigator.onLine)
                    await sleepPromise;
                else
                    await Promise.race([onlinePromise(), sleepPromise]);
            }
        }
    }
}

function TryOnceAjaxFunction(controller, method, ...args) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('post', '/ajax/' + controller + '/' + method);
        const body = generatePostBody(args);
        xhr.setRequestHeader('x-js-origin', 'true');
        xhr.setRequestHeader('x-idempotency-key', generateIdempotencyKey());
        xhr.onerror = (er) => console.log(er)
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
                } else if (xhr.status == 0) {
                    reject(new ConnectionError());
                } else {
                    try {
                        let decoded = JSON.parse(xhr.responseText);
                        showServerDebug(decoded);
                        ConsoleCheating.eval("console.error.apply(null,data)", "", decoded.error.stack[0].file, decoded.error.stack[0].line, [decoded.error.message + '%o', decoded.error]);
                        reject(decoded.error)
                    } catch (ex) {
                        reject(new HttpErrorCode(xhr.status, xhr.statusText));
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

function generateIdempotencyKey() {
    const uniq = /uniq=([0-9a-f]+)/.exec(document.cookie)[1];
    if (!uniq) return null;

    let requestCounter = localStorage.requestCounter;
    requestCounter++;
    if (isNaN(requestCounter))
        requestCounter = 0;
    localStorage.requestCounter = requestCounter;

    let time = (+new Date()).toString(16);
    let random = ("0" + (Math.random() * 255).toString(16)).substr(-2);

    return `${uniq}_${requestCounter}_${time}_${random}`;
}

export const Ajax = new Proxy(AjaxFunction, AjaxHandler);