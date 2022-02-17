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
                    if (!this.objectsList.infiniteScrollEnabled) {
                        this.objectsList.start = 0;
                    }
                    this.objectsList.refresh();
                }
            }
        }
        this.head.addChild('.column.actions')

        this.body = this.addChild('.bodyContainer').addChild('.body');
        this.setColumnsWidths();
        addEventListener('resize', this.setColumnsWidths.bind(this))
        addEventListener('copy', this.onCopy.bind(this));
        this.addEventListener('scroll', this.onScroll.bind(this));
    }

    loadData(data, start, limit, infiniteScrollEnabled) {
        this.style.setProperty('--height', (data.total * 41) + 'px');
        this.refreshSortIndicators();
        let top = 0;
        let isOdd = true;
        if (infiniteScrollEnabled) {
            top = 41 * start;
            isOdd = start % 2 == 0;
        }
        let newChildren = [];
        for (let row of data.rows) {
            let tr = this.generateRow(row);
            tr.style.top = `${top}px`;
            tr.classList.toggle('odd', isOdd)
            tr.classList.toggle('even', !isOdd)
            newChildren.push(tr);
            top += 41;
            isOdd = !isOdd;
        }
        let oldChildren = Array.from(this.body.children);
        for (let tr of oldChildren.filter(tr => !newChildren.includes(tr))) {
            tr.remove();
        }

        for (let tr of newChildren.filter(tr => !oldChildren.includes(tr))) {
            this.body.appendChild(tr);
        }
        this.setColumnsWidths();
    }

    generateRow(data) {
        let tr = this.body.querySelector(`.tr[data-row="${data.id}"]`);
        if (!tr) {
            tr = document.create('.tr');
            tr.draggable = true;
        }
        this.fillRowContent(tr, data);
        return tr;
    }

    fillRowContent(tr, data) {
        tr.children.removeAll();
        tr.addChild('.td.icon');
        for (let column of this.objectsList.columns) {
            let td = tr.addChild('.td');
            td.append(column.content(data));
        }
        let actionsTd = tr.addChild('.td.actions');
        let actions = this.objectsList.generateActions([data], 'row');
        for (let action of actions) {
            let actionButton = actionsTd.addChild(action.href ? 'a.button' : 'button', {
                title: action.name
            });
            if (action.href) {
                actionButton.href = action.href;
            }
            if (action.command) {
                actionButton.onclick = action.command;
            }
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
        tr.ondragstart = this.trOnDragStart.bind(this, data, tr);
    }

    setColumnsWidths() {
        const widths = this.calculateColumnsWidths();
        for (let tr of this.body.children) {
            for (let i = 0; i < widths.length; i++) {
                tr.children[i].style.width = widths[i] + 'px';
            }
        }
        let sum = 0;
        for (let i = 0; i < widths.length; i++) {
            let node = this.head.children[i - 1];
            if (node) {
                if (i + 1 < widths.length)
                    node.style.width = widths[i] + 1 + 'px';
                else
                    node.style.width = widths[i] + 'px';
                node.style.left = sum + 'px';
            }
            sum += widths[i];
        }
    }

    calculateColumnsWidths() {
        let needed = [{base: 30, grow: 0}];

        for (let column of this.objectsList.columns) {
            needed.push({base: column.width || 10, grow: typeof (column.widthGrow) == "number" ? column.widthGrow : 1});
        }
        let actionWidth = Math.ceil(Array.from(this.querySelectorAll('.td.actions')).map(x => {
            if (x.lastElementChild)
                return x.lastElementChild.getBoundingClientRect().right - x.getBoundingClientRect().left + parseFloat(getComputedStyle(x).paddingRight);
            else
                return 0;
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
        const rows = this.objectsList.getSelectedData();
        let actions = this.objectsList.generateActions(rows, 'contextMenu');
        let elements = actions.map(action => ({
            text: action.name,
            icon: action.icon,
            onclick: action.command || (() => pageManager.goto(action.href))
        }));
        elements.push({
            text: 'copy',
            icon: 'icon-copy',
            onclick: () => {
                this.forceCopy(rows);
            }
        })
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
            let action = this.objectsList.generateActions(this.objectsList.getSelectedData(), 'enter').find(x => x.main);
            if (action) {
                if (action.command) {
                    action.command();
                } else if (action.href) {
                    pageManager.goto(action.href)
                }
            }
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

    onCopy(e) {
        if (this.copyForced) {
            this.fillDataTransfer(e.clipboardData, this.copyForced);
            e.preventDefault();
        } else if (document.querySelector(':focus')?.findParent(x => x === this)) {
            this.fillDataTransfer(e.clipboardData, this.objectsList.selected);
            e.preventDefault();
        }
    }

    trOnDragStart(row, oryginalTr, e) {
        if (!this.objectsList.selected.has(row.id)) {
            this.objectsList.selected.clear()
            this.objectsList.selected.add(row.id)
            this.refreshSelectedClasses();
        }
        this.fillDataTransfer(e.dataTransfer, this.objectsList.selected);
    }

    fillDataTransfer(dataTransfer, ids) {
        const trs = Array.from(this.body.children).filter(tr => ids.has(tr.dataset.row));
        let action = this.objectsList.generateActions(this.objectsList.getSelectedData(), 'dataTransfer').find(x => x.main);
        if (action && action.href) {
            dataTransfer.setData('text/uri-list', new URL(action.href, document.baseURI));
        }
        dataTransfer.setData('text/html', this.generateTableHtml(trs));
        dataTransfer.setData('text/plain', this.generateTableTextPlain(trs));

    }

    generateTableHtml(trs) {
        const thead = '<thead><tr>' + Array.from(this.head.querySelectorAll('.column')).map(x => '<th>' + x.innerHTML + '</th>').join('') + '</tr></thead>';
        const tbody = '<tbody>' + trs.map(tr => {
            return '<tr>' + Array.from(tr.children).slice(1, -1).map(td => {
                return '<td>' + td.innerHTML + '</td>';
            }).join('') + '</tr>';
        }).join('') + '</tbody>'
        return '<table>' + thead + tbody + '</table>';
    }

    generateTableTextPlain(trs) {
        const head = Array.from(this.head.querySelectorAll('.column')).map(x => x.textContent.replace(/\r\n/gm, ' ')).join("\t")
        const body = trs.map(tr => Array.from(tr.children).slice(1).map(x => x.textContent.replace(/\r\n/gm, ' ')).join("\t")).join("\r\n")
        return head + "\r\n" + body;
    }

    calcMaxVisibleItems(height) {
        return Math.floor((height - this.head.clientHeight) / 41);
    }

    forceCopy(rows) {
        this.copyForced = new Set(rows.map(x => x.id));
        document.execCommand("copy");
        setTimeout(() => this.copyForced = null, 100);
    }

    onScroll(e) {
        if (this.onPaginationChanged) {
            let start = Math.round(this.scrollTop / 41);
            let passedStart = Math.floor(start / 20) * 20 - 20;
            if (passedStart < 0)
                passedStart = 0;
            this.onPaginationChanged(passedStart);
        }
    }
}

customElements.define('table-view', TableView);