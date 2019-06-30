import "prototype-extensions";

export class TableManager {
    constructor(table, datasource) {
        this.table = table;
        this.datasource = datasource;
        this.datasource.onchange = () => this.refresh();
        this.page = 0;
        this.search = '';
        this.sort = '';
        this.limit = 100;
        this.calcSize();
    }

    get start() {
        return this.page * this.limit;
    }

    async refresh() {
        let data = await this.datasource.get(this);
        this.loadData(data);
    }

    /**
     * virtual method
     */
    calcSize() {
    }

    loadData(data) {
        let tbody = this.table.tBodies[0];
        if (!tbody) {
            tbody = this.table.addChild('tbody');
        }
        tbody.children.removeAll();

        for (let row of data.rows) {
            let tr = tbody.addChild('tr');
            for (let th of this.table.tHead.firstElementChild.children) {
                let td = tr.addChild('td');
                if (th.dataset.value)
                    td.textContent = row[th.dataset.value];
                else if (th.classList.contains('tableActions')) {
                    let tableCopy = th.querySelector('.tableCopy');
                    if (tableCopy) {
                        td.innerHTML = th.querySelector('.tableCopy').innerHTML;
                        var links = td.querySelectorAll('a');
                        links.forEach(a => a.href = a.href.replace(/\/$/, '') + '/' + row.id);
                    }
                }
            }
        }
        if (!this.table.tFoot)
            this.table.tFoot = document.create('tfoot');
        this.renderPagination(data.total);
    }

    goToPage(pageNumber) {
        this.page = pageNumber;
        this.refresh();
    }

    renderPagination(totalRows) {
        let pagination = this.getPagination(totalRows);
        console.log({pagination});
        if (!this.paginationRow) {
            this.paginationRow = this.table.tFoot.addChild('tr').addChild('td');
        }
        this.paginationRow.colSpan = this.table.tHead.firstElementChild.children.length;
        this.paginationRow.children.removeAll();
        for (let pageNumber of pagination) {
            if (pageNumber == null) {
                this.paginationRow.addChild('span', {text: '...'});
            } else {
                let pageButton = this.paginationRow.addChild('button', {text: pageNumber + 1});
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
}