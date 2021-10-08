export class ListRenderer {
    constructor(container, renderFunction, list = [], key = x => x, config = {}) {
        this.config = Object.assign(this.getDefaultConfig(), config);
        this.container = container;
        this.renderFunction = renderFunction;
        this.list = list;
        this.key = key;
        this.map = this.config.useWeakMap ? new WeakMap() : new Map();
        this.render();
    }

    getDefaultConfig() {
        return {
            reverse: false,
            useWeakMap: true
        }
    }

    render() {
        let toRender = Array.from(this.list);
        let wantedChildren = [];
        let i = 0;
        for (let item of toRender) {
            let key = this.key(item);
            let rendered = null;
            if (this.map.has(key)) {
                rendered = this.map.get(key);
            }
            rendered = this.renderFunction(item, rendered);
            this.map.set(key, rendered);
            wantedChildren.push(rendered);
            rendered.style.setProperty('--list-order', i++);
            rendered.style.setProperty('--list-count', toRender.length);
        }
        this.changeChildrenFor(wantedChildren)
    }

    changeChildrenFor(wantedChildren) {
        let set = new Set(wantedChildren);
        let currentIndex = 0;
        let wantedIndex = 0;
        while (currentIndex < this.container.childNodes.length) {
            let current = this.container.childNodes[currentIndex];
            if (!set.has(current)) {
                current.remove();
            } else if (current == wantedChildren[wantedIndex]) {
                wantedIndex++;
                currentIndex++;
            } else {
                this.container.insertBefore(wantedChildren[wantedIndex], current);
                wantedIndex++;
                currentIndex++;
            }
        }
        for (; wantedIndex < wantedChildren.length; wantedIndex++) {
            this.container.appendChild(wantedChildren[wantedIndex]);
        }
        this.clearMap();
    }

    clearMap() {
        if (this.map instanceof Map) {
            let arr = Array.from(this.map);
            for (let [key, value] of arr) {
                if (value.parentNode == null) {
                    this.map.delete(key);
                }
            }
        }
    }
}