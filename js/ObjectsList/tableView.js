import {ContextMenu} from "../contextMenu";
import {pageManager} from "../pageManager";

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
            if (column.sortName) {
                node.classList.add('ableToSort');
                node.dataset.sortName = column.sortName
                node.onclick = () => {
                    let sortName = column.sortName || x.dataset.value;
                    if (this.objectsList.sort && this.objectsList.sort.col === column.sortName) {
                        this.objectsList.sort.desc = !this.objectsList.sort.desc;
                    } else {
                        this.objectsList.sort = {col: column.sortName, desc: false};
                    }
                    this.objectsList.refresh();
                }
            }
        }

        this.body = this.addChild('.bodyContainer').addChild('table').addChild('tbody');
        this.setColumnsWidths();
        addEventListener('resize', this.setColumnsWidths.bind(this))
    }

    loadData(data) {
        this.body.children.removeAll();

        this.refreshSortIndicators();

        for (let row of data.rows) {
            this.body.append(this.generateRow(row));
        }
        this.setColumnsWidths();
    }

    generateRow(data) {
        const tr = document.createElement('tr');
        tr.draggable=true;
        tr.addChild('td.icon');
        for (let column of this.objectsList.columns) {
            let td = tr.addChild('td');
            td.append(column.content(data));
        }
        let actionsTd = tr.addChild('td.actions');
        let actions = this.objectsList.generateActions([data], 'row');
        for (let action of actions) {
            let actionButton = actionsTd.addChild(action.href ? 'a.button' : 'button', {
                href: action.href,
                title: action.name
            });
            if (action.icon) {
                actionButton.addChild('span', {classList: [action.icon]});
            } else {
                actionButton.textContent = action.name;
            }
        }


        tr.dataset.row = data.id;
        tr.oncontextmenu = this.contextMenu.bind(this, tr);
        tr.onclick = this.trOnClick.bind(this, data);
        tr.ondblclick = this.trOnDblClick.bind(this, data, tr);
        tr.onkeydown = this.trOnKeyDown.bind(this, data, tr);
        tr.oncopy = this.trOnCopy.bind(this, data, tr);
        tr.ondragstart = this.trOnDragStart.bind(this, data, tr);

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

    refreshSortIndicators() {
        this.head.querySelectorAll('[data-order]').forEach(x => delete x.dataset.order);
        if (this.objectsList.sort)
            this.head.querySelectorAll(`[data-sort-name="${this.objectsList.sort.col}"]`).forEach(x => x.dataset.order = this.objectsList.sort.desc ? 'desc' : 'asc');
    }

    contextMenu(tr, event) {
        event.stopPropagation();
        if (!this.objectsList.selected.has(tr.dataset.row)) {
            this.objectsList.selected.clear();
            this.objectsList.selected.add(tr.dataset.row)
            this.objectsList.selectedMain = tr.dataset.row;
            this.refreshSelectedClasses();
        }
        let actions = this.objectsList.generateActions(this.objectsList.getSelectedData(), 'contextMenu');
        let elements = actions.map(action => ({
            text: action.name,
            icon: action.icon,
            onclick: action.command || (() => pageManager.goto(action.href))
        }));
        ContextMenu.openContextMenu(event, elements);
    }

    refreshSelectedClasses() {
        for (const tr of this.body.children) {
            tr.classList.toggle('selected', this.objectsList.selected.has(tr.dataset.row));
            tr.classList.toggle('selectedMain', this.objectsList.selectedMain == tr.dataset.row);
            if (this.objectsList.selectedMain == tr.dataset.row) {
                tr.tabIndex = 1;
                tr.focus();
                getSelection().selectAllChildren(tr)
            } else {
                tr.tabIndex = -1;
            }
        }
    }


    trOnClick(row, e) {
        const rowsIds = this.objectsList.currentRows.map(x => x.id);
        console.log('click')
        if (!e.ctrlKey) {
            this.objectsList.selected.clear();
        }

        if (e.shiftKey) {
            const mainIndex = rowsIds.indexOf(this.objectsList.selectedMain);
            const clickedIndex = rowsIds.indexOf(row.id);
            if (clickedIndex >= 0 && mainIndex >= 0)
                rowsIds.slice(Math.min(mainIndex, clickedIndex), Math.max(mainIndex, clickedIndex) + 1).forEach(x => this.objectsList.selected.add(x));
        } else {
            if (this.objectsList.selected.has(row.id))
                this.objectsList.selected.delete(row.id);
            else
                this.objectsList.selected.add(row.id);
        }

        this.objectsList.selectedMain = row.id;
        this.refreshSelectedClasses();
    }

    trOnDblClick(row, tr, e) {
        if (!this.objectsList.selected.has(tr.dataset.row)) {
            this.objectsList.selected.clear();
            this.objectsList.selected.add(tr.dataset.row)
            this.objectsList.selectedMain = tr.dataset.row;
            this.refreshSelectedClasses();
        }
        let action = this.objectsList.generateActions(this.objectsList.getSelectedData(), 'dblClick').find(x => x.main);
        if (action) {
            if (action.command) {
                action.command();
            } else if (action.href) {
                pageManager.goto(action.href)
            }
        }
    }

    selectRange(start, end) {
        const rowsIds = this.objectsList.currentRows.map(x => x.id);
        let startIndex = rowsIds.indexOf(start);
        let endIndex = rowsIds.indexOf(end);
        if (endIndex < startIndex) {
            let tmp = startIndex;
            startIndex = endIndex;
            endIndex = tmp;
        }
        for (let i = startIndex; i <= endIndex; i++) {
            this.objectsList.selected.add(rowsIds[i]);
        }
    }

    trOnKeyDown(row, tr, e) {
        console.log('trOnKeyDown')
        if (e.key === 'Enter') {
            const defaultButton = tr.querySelector('.button.default, .button.default');
            if (defaultButton)
                defaultButton.click();
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            const rowsIds = this.objectsList.currentRows.map(x => x.id);
            let index = rowsIds.indexOf(this.objectsList.selectedMain);
            if (e.key === 'ArrowDown') {
                if (index < rowsIds.length) index++;
                else index = rowsIds.length - 1;
            } else {
                if (index > 0) index--;
            }
            const id = rowsIds[index];
            if (!e.ctrlKey) {
                this.objectsList.selected.clear();
            }
            if (e.shiftKey) {
                if (!this.objectsList.selectedShiftStart) {
                    this.objectsList.selectedShiftStart = this.objectsList.selectedMain;
                }
                this.selectRange(this.objectsList.selectedShiftStart, id)
            } else {
                this.objectsList.selectedShiftStart = null;
                if (!e.ctrlKey) {
                    this.objectsList.selected.add(id);
                }
            }
            this.objectsList.selectedMain = id;
            this.refreshSelectedClasses();
            e.preventDefault();
        } else if (e.key === ' ') {
            if (this.objectsList.selected.has(this.objectsList.selectedMain))
                this.objectsList.selected.delete(this.objectsList.selectedMain);
            else
                this.objectsList.selected.add(this.objectsList.selectedMain);

            this.refreshSelectedClasses();
            e.preventDefault();
        }
    }

    trOnCopy(row, oryginalTr, e) {
        this.fillDataTransfer(e.clipboardData);
        e.preventDefault();
    }

    trOnDragStart(row, oryginalTr, e) {
        this.fillDataTransfer(e.dataTransfer);
    }

    fillDataTransfer(dataTransfer) {
        const trs = Array.from(this.body.children).filter(tr => this.objectsList.selected.has(tr.dataset.row));
        dataTransfer.setData('text/html', '<table>' + trs.map(tr => tr.outerHTML).join('') + '</table>');
        dataTransfer.setData('text/plain', trs.map(tr => Array.from(tr.children).map(x => x.textContent.replace(/\r\n/gm, ' ')).join("\t")).join("\r\n"));

    }

    calcMaxVisibleItems(height) {
        return Math.floor((height - this.head.clientHeight) / 41);
    }
}

customElements.define('table-view', TableView);