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
        let data = await this.datasource.get(this);
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
            tr.oncontextmenu = this.contextMenu.bind(this, tr);
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
            const searchInput = this.searchForm.addChild('input', {name: 'search', type: 'search'});
            searchInput.oninput = e => this.refresh();
        }
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
        const buttons = tr.querySelectorAll('.button, button');
        const elements = Array.from(buttons).map(b => ({
            text: b.title || b.textContent,
            icon: (b.querySelector('.icon, [class^="icon-"], [class*=" icon-"]') || {}).className,
            onclick: e => b.click()
        }));
        ContextMenu.openContextMenu(event, elements);
    }
}