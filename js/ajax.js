function AjaxFunction(controller, method, ...args) {
    return new Promise((resolve, reject) => {
        var xhr = new XMLHttpRequest();
        xhr.open('post', '/ajax/' + controller + '/' + method);
        var postData = '';
        for (var arg of args) {
            postData += '&args[]=' + encodeURIComponent(JSON.stringify(arg));
        }
        xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = (a) => {

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