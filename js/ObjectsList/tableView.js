export class TableView extends HTMLElement {
    constructor(objectsList) {
        super();
        this.objectsList = objectsList;
        this.init();
    }

    init() {
        this.head = this.addChild('.head');
        for (let column of this.objectsList.columns) {
            let node = this.head.addChild('.column')
            node.addChild('span.name', {text: column.name});
        }

        this.body = this.addChild('.bodyContainer').addChild('table').addChild('tbody');
        this.setColumnsWidths();
        addEventListener('resize', this.setColumnsWidths.bind(this))
    }

    loadData(data) {
        this.body.children.removeAll();

        for (let row of data.rows) {
            this.body.append(this.generateRow(row));
        }
        this.setColumnsWidths();
    }

    generateRow(data) {
        const tr = document.createElement('tr');
        tr.addChild('td.icon');
        for (let column of this.objectsList.columns) {
            let td = tr.addChild('td');
            td.append(column.content(data));
        }
        let actionsTd = tr.addChild('td.actions');
        let actions = this.objectsList.generateActions([data]);
        for (let action of actions) {
            let actionButton = actionsTd.addChild(action.href ? 'a.button' : 'button', {
                href: action.href,
                title: action.name
            });
            if (action.icon) {
                actionButton.addChild('span.icon-' + action.icon);
            } else {
                actionButton.textContent = action.name;
            }
        }
        return tr;
    }

    setColumnsWidths() {
        const widths = this.calculateColumnsWidths();
        if (this.body.firstChild) {
            for (let i = 0; i < widths.length; i++) {
                this.body.firstChild.children[i].style.width = widths[i] + 'px';
            }
        }
        let sum = 0;
        for (let i = 0; i < widths.length; i++) {
            let node = this.head.children[i - 1];
            if (node) {
                node.style.width = widths[i] + 'px';
                node.style.left = sum + 'px';
            }
            sum += widths[i];
        }
    }

    calculateColumnsWidths() {
        let needed = [{base: 30, grow: 0}];

        for (let column of this.objectsList.columns) {
            needed.push({base: column.width || 10, grow: column.widthGrow || 1});
        }
        let actionWidth = Math.ceil(Array.from(this.querySelectorAll('td.actions')).map(x => {
            return x.lastElementChild.getBoundingClientRect().right - x.getBoundingClientRect().left + parseFloat(getComputedStyle(x).paddingRight);
        }).max());
        needed.push({base: actionWidth, grow: 0});

        let availableToGrow = this.clientWidth - needed.sum(x => x.base);
        let sumGrow = needed.sum(x => x.grow);
        if (availableToGrow > 0 && sumGrow > 0) {
            return needed.map(x => x.base + x.grow / sumGrow * availableToGrow);
        } else {
            return needed.map(x => x.base);
        }
    }

}

customElements.define('table-view', TableView);