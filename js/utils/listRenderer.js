export class ListRenderer {
    constructor(container, renderFunction, list = [], key = x => x) {
        this.container = container;
        this.renderFunction = renderFunction;
        this.list = list;
        this.key = key;
        this.map = new WeakMap();
        this.render();
    }

    render() {
        let toRender = Array.from(this.list);
        let set = new Set();
        for (let item of toRender) {
            let key = this.key(item);
            let rendered=null;
            if (this.map.has(key)) {
                rendered = this.map.get(key);
            }
            rendered=this.renderFunction(item, rendered);
            this.map.set(key, rendered);
            set.add(rendered);
            this.container.appendChild(rendered);
        }
        Array.from(this.container.childNodes).filter(x=>!set.has(x)).forEach((x=>x.remove()));
    }
}