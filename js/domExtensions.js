HTMLDocument.prototype.create = function (name, attributes = {}) {
    let element = this.createElement(name);
    for (let attrName in attributes) {
        if (attrName === 'className') {
            element.className = attributes[attrName];
        }
        else if (attrName === 'classList') {
            for (let x of attributes.classList)
                element.classList.add(x);
        }
       else if (attrName === 'text') {
            element.textContent = attributes.text;
        } else {
            element.setAttribute(attrName, attributes[attrName]);
        }
    }
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