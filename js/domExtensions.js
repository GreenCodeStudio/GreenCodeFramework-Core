HTMLDocument.prototype.create = function (name, attributes = {}) {
    let element = this.createElement(name);
    return element;
};
HTMLElement.prototype.add = function (name, attributes = {}) {
    let element = this.ownerDocument.create(name, attributes);
    this.appendChild(element);
    return element;
};
HTMLCollection.prototype.removeAll = function () {
    for (let element of this) {
        element.remove();
    }
};