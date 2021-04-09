export class ContextMenu {
    constructor(elements, parent = null) {
        this.generateHtml(elements);
        this.bindGlobalEvents();
        this.subMenu = null;
    }

    generateHtml(elements) {
        this.html = document.create('ul.contextMenu');
        for (const element of elements) {
            this.html.append(this.generateElementHtml(element));
        }
        this.html.onmousedown = e => e.stopPropagation();
    }

    generateElementHtml(element) {
        const elementHtml = document.create('li.element', {tabIndex: 0});
        if (element.submenu) {
            elementHtml.classList.add('hasSubmenu');
        }
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
            elementHtml.onkeydown = e => {
                if (e.key == 'Enter') {
                    element.onclick.call(elementHtml, e);
                    this.destroy()
                } else if (e.key == 'ArrowDown') {
                    (elementHtml.nextElementSibling ?? elementHtml.parentNode.firstElementChild).focus();
                } else if (e.key == 'ArrowUp') {
                    (elementHtml.previousElementSibling ?? elementHtml.parentNode.lastElementChild).focus();
                }
            };
        }
        elementHtml.onmouseenter = e => {
            elementHtml.focus();
            if (this.submenu) {
                this.submenu.destroy();
                this.submenu = null;
            }
            if (element.submenu) {
                this.submenu = new ContextMenu(element.submenu);
                document.body.appendChild(this.submenu.html);
                this.submenu.setPositionToParent(elementHtml);
            }
        };
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
            this.html.style.bottom = `${Math.min(innerHeight - event.clientY + 1, innerHeight - this.html.offsetHeight)}px`;
    }

    setPositionToParent(parent) {
        const parentBoundingBox = parent.getBoundingClientRect();
        const placeRight = innerWidth - parentBoundingBox.right;
        const placeLeft = parentBoundingBox.left;
        const placeBottom = innerHeight - parentBoundingBox.top;
        const isRight = this.html.offsetWidth <= placeRight;
        const isBottom = this.html.offsetHeight <= placeBottom;

        this.html.style.left = 'auto';
        this.html.style.right = 'auto';
        this.html.style.top = 'auto';
        this.html.style.bottom = 'auto';

        if (isRight)
            this.html.style.left = `${parentBoundingBox.right}px`;
        else
            this.html.style.right = `${Math.min(innerWidth - parentBoundingBox.left, innerWidth - this.html.offsetWidth)}px`;

        if (isBottom)
            this.html.style.top = `${parentBoundingBox.top}px`;
        else
            this.html.style.bottom = `${Math.min(innerHeight - parentBoundingBox.bottom, innerHeight - this.html.offsetHeight)}px`;
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
        if (menu.html.firstChild)
            menu.html.firstChild.focus();

        return menu;
    }
}