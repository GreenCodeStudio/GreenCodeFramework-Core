import "prototype-extensions";
import {ContextMenu} from "./contextMenu";

export class TableManager {
    constructor(table, datasource) {
        this.table = table;
        this.datasource = datasource;
        this.datasource.onchange = () => this.refresh();
        this.page = 0;
        this.sort = this.readSort();
        this.limit = 100;
        this.selected = new Set();
        this.selectedMain = null;
        this.calcSize();
        this.initThead();
    }

    readSort() {
        let ordered = this.table.tHead.querySelector('[data-order]');
        if (ordered)
            return {col: ordered.dataset.value, desc: ordered.dataset.order == 'desc'};
        return null;
    }

    get start() {
        return this.page * this.limit;
    }

    async refresh() {
        const refreshSymbol = Symbol();
        this.lastRefreshSymbol = refreshSymbol;
        let data = await this.datasource.get(this);
        if (this.lastRefreshSymbol == refreshSymbol)
            this.loadData(data);
    }

    get search() {
        const searchForm = this.table.querySelector('.search');
        if (!searchForm) return '';
        return searchForm.search.value;
    }

    /**
     * virtual method
     */
    calcSize() {
    }

    loadData(data) {
        this.table.tHead.querySelectorAll('[data-order]').forEach(x => delete x.dataset.order);
        if (this.sort)
            this.table.tHead.querySelectorAll(`[data-value="${this.sort.col}"]`).forEach(x => x.dataset.order = this.sort.desc ? 'desc' : 'asc');

        let tbody = this.table.tBodies[0];
        if (!tbody) {
            tbody = this.table.addChild('tbody');
        }
        tbody.children.removeAll();

        for (let row of data.rows) {
            let tr = tbody.addChild('tr');
            tr.dataset.row = row.id;
            tr.oncontextmenu = this.contextMenu.bind(this, tr);
            tr.onclick = this.trOnClick.bind(this, row);
            tr.ondblclick = this.trOnDblClick.bind(this, row, tr);
            tr.onkeydown = this.trOnKeyDown.bind(this, row, tr);
            tr.oncopy = this.trOnCopy.bind(this, row, tr);
            for (let th of this.table.tHead.firstElementChild.children) {
                let td = tr.addChild('td');
                td.dataset.header = th.textContent + ': ';
                if (th.dataset.value)
                    td.textContent = row[th.dataset.value];
                else if (th.classList.contains('tableActions')) {
                    td.classList.add('tableActions-cell');
                    let tableCopy = th.querySelector('.tableCopy');
                    if (tableCopy) {
                        td.innerHTML = th.querySelector('.tableCopy').innerHTML;
                        let links = td.querySelectorAll('a');
                        links.forEach(a => a.href = a.href.replace(/\/$/, '') + '/' + row.id);
                    }
                }
            }
        }
        if (!this.table.tFoot) {
            this.table.tFoot = document.create('tfoot');
            this.rFootTd = this.table.tFoot.addChild('tr').addChild('td');
            this.rFootTd.colSpan = this.table.tHead.firstElementChild.children.length;
            this.paginationDiv = this.rFootTd.addChild('div', {className: 'pagination'});
            this.searchForm = this.rFootTd.addChild('form', {className: 'search'});
            this.searchForm.onsubmit = e => e.preventDefault();
            const searchInput = this.searchForm.addChild('input', {name: 'search', type: 'search'});
            searchInput.oninput = e => this.refresh();
        }
        this.currentRows = data.rows;
        this.renderPagination(data.total);
    }

    goToPage(pageNumber) {
        this.page = pageNumber;
        this.refresh();
    }

    renderPagination(totalRows) {
        let pagination = this.getPagination(totalRows);
        this.paginationDiv.children.removeAll();
        for (let pageNumber of pagination) {
            if (pageNumber == null) {
                this.paginationDiv.addChild('span', {text: '...'});
            } else {
                let pageButton = this.paginationDiv.addChild('button', {text: pageNumber + 1});
                pageButton.onclick = () => {
                    this.goToPage(pageNumber);
                };
            }
        }
    }

    getPagination(totalRows) {
        const boxesCount = 7;
        let pages = Math.ceil(totalRows / this.limit);
        let boxes = [];
        if (pages <= boxesCount) {
            for (let i = 0; i < pages; i++) {
                boxes.push(i);
            }
        } else if (this.page <= (boxesCount - 1) / 2 - 1) {
            for (let i = 0; i <= boxesCount - 3; i++) {
                boxes.push(i);
            }
            boxes.push(null);
            boxes.push(pages - 1);
        } else if (this.page >= pages - (boxesCount - 1) / 2 - 1) {
            boxes.push(0);
            boxes.push(null);
            for (
                let i = pages - Math.floor((boxesCount - 1) / 2) - 2;
                i < pages;
                i++
            ) {
                boxes.push(i);
            }
        } else {
            boxes.push(0);
            boxes.push(null);
            for (
                let i = this.page - Math.floor((boxesCount - 5) / 2);
                i <= this.page + Math.ceil((boxesCount - 5) / 2);
                i++
            ) {
                boxes.push(i);
            }
            boxes.push(null);
            boxes.push(pages - 1);
        }
        return boxes;
    }

    initThead() {
        let headers = this.table.tHead.querySelectorAll('[data-sortable]');
        console.log({headers});
        headers.forEach(x => {
            x.onclick = () => {
                let sortName = x.dataset.sortName || x.dataset.value;
                if (this.sort && this.sort.col === sortName) {
                    this.sort.desc = !this.sort.desc;
                } else {
                    this.sort = {col: sortName, desc: false};
                }
                this.refresh();
            }
        })
    }

    contextMenu(tr, event) {
        if (!this.selected.has(tr.dataset.row))
        {
            this.selected.clear();
            this.selected.add(tr.dataset.row)
            this.selectedMain = tr.dataset.row;
            this.refreshSelectedClasses();
        }
        let elements=[];
        if(this.selected.size==1) {
            const buttons = tr.querySelectorAll('.button, button');
            elements = Array.from(buttons).map(b => ({
                text: b.title || b.textContent,
                icon: (b.querySelector('.icon, [class^="icon-"], [class*=" icon-"]') || {}).className,
                onclick: e => b.click()
            }));
        }
        ContextMenu.openContextMenu(event, elements);
    }

    trOnClick(row, e) {
        const rowsIds = this.currentRows.map(x => x.id);
        console.log('click')
        if (!e.ctrlKey) {
            this.selected.clear();
        }

        if (e.shiftKey) {
            const mainIndex = rowsIds.indexOf(this.selectedMain);
            const clickedIndex = rowsIds.indexOf(row.id);
            if (clickedIndex >= 0 && mainIndex >= 0)
                rowsIds.slice(Math.min(mainIndex, clickedIndex), Math.max(mainIndex, clickedIndex) + 1).forEach(x => this.selected.add(x));
        } else {
            if (this.selected.has(row.id))
                this.selected.delete(row.id);
            else
                this.selected.add(row.id);
        }

        this.selectedMain = row.id;
        this.refreshSelectedClasses();
    }

    trOnDblClick(row, tr, e) {
        const defaultButton = tr.querySelector('button.default, .button.default');
        if (defaultButton)
            defaultButton.click();
    }

    trOnKeyDown(row, tr, e) {
        if (e.key === 'Enter') {
            const defaultButton = tr.querySelector('.button.default, .button.default');
            if (defaultButton)
                defaultButton.click();
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            const rowsIds = this.currentRows.map(x => x.id);
            let index = rowsIds.indexOf(this.selectedMain);
            if (e.key === 'ArrowDown') {
                if (index < rowsIds.length) index++;
                else index = rowsIds.length - 1;
            } else {
                if (index > 0) index--;
            }
            const id = rowsIds[index];
            if (e.ctrlKey) {
            } else {
                this.selected.clear();
                this.selected.add(id);
            }
            this.selectedMain = id;
            this.refreshSelectedClasses();
        } else if (e.key === ' ') {
            if (this.selected.has(this.selectedMain))
                this.selected.delete(this.selectedMain);
            else
                this.selected.add(this.selectedMain);

            this.refreshSelectedClasses();

        }
    }

    trOnCopy(row, oeyginalTr, e) {
        const trs = Array.from(this.table.tBodies).flatMap(tbody => Array.from(tbody.children)).filter(tr => this.selected.has(tr.dataset.row));
        e.clipboardData.setData('text/html', '<table>' + trs.map(tr => tr.outerHTML).join('') + '</table>');
        e.clipboardData.setData('text/plain', trs.map(tr => Array.from(tr.children).map(x => x.textContent.replace(/\r\n/gm, ' ')).join("\t")).join("\r\n"));
        e.preventDefault();
    }

    refreshSelectedClasses() {
        for (const tr of Array.from(this.table.tBodies).flatMap(x => Array.from(x.children))) {
            tr.classList.toggle('selected', this.selected.has(tr.dataset.row));
            tr.classList.toggle('selectedMain', this.selectedMain == tr.dataset.row);
            if (this.selectedMain == tr.dataset.row) {
                tr.tabIndex = 1;
                tr.focus();
                getSelection().selectAllChildren(tr)
            } else {
                tr.tabIndex = -1;
            }
        }
    }
}