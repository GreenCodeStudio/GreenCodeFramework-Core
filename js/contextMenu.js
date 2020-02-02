export class ContextMenu {
    constructor(elements, parent = null) {
        this.generateHtml(elements);
        this.bindGlobalEvents();
    }

    generateHtml(elements) {
        this.html = document.create('ul.contextMenu');
        for (const element of elements) {
            this.html.append(this.generateElementHtml(element));
        }
        this.html.onmousedown = e => e.stopPropagation();
    }

    generateElementHtml(element) {
        const elementHtml = document.create('li.element');
        if (element.icon)
            elementHtml.addChild('span.icon', {className: element.icon});
        else
            elementHtml.addChild('span.iconPlaceholder');

        elementHtml.addChild('span.content', {text: element.text || ''});
        if (element.onclick) {
            elementHtml.onclick = e => {
                element.onclick.call(elementHtml, e);
                this.destroy()
            };
        }
        return elementHtml;
    }

    bindGlobalEvents() {
        this.bindedDestroyEvent = this.destroy.bind(this);
        addEventListener('mousedown', this.bindedDestroyEvent);
        addEventListener('blur', this.bindedDestroyEvent);
    }

    setPositionToPointer(event) {
        const placeHorizontal = innerWidth - event.clientX;
        const placeVertical = innerHeight - event.clientY;
        const isRight = this.html.offsetWidth <= placeHorizontal;
        const isBottom = this.html.offsetHeight <= placeVertical;

        this.html.style.left = 'auto';
        this.html.style.right = 'auto';
        this.html.style.top = 'auto';
        this.html.style.bottom = 'auto';

        if (isRight)
            this.html.style.left = `${event.clientX}px`;
        else
            this.html.style.right = `${Math.min(innerWidth - event.clientX + 1, innerWidth - this.html.offsetWidth)}px`;

        if (isBottom)
            this.html.style.top = `${event.clientY}px`;
        else
            this.html.style.botom = `${Math.min(innerHeight - event.clientY + 1, innerHeight - this.html.offsetHeight)}px`;
    }

    destroy() {
        this.html.remove();
        removeEventListener('mousedown', this.bindedDestroyEvent);
        removeEventListener('blur', this.bindedDestroyEvent);
    }

    static openContextMenu(event, elements) {
        const menu = new ContextMenu(elements);
        document.body.appendChild(menu.html);
        menu.setPositionToPointer(event);
        event.preventDefault();
        return menu;
    }
}