import "prototype-extensions";

export class TableManager {
    constructor(table, datasource) {
        this.table = table;
        this.datasource = datasource;
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
    calcSize(){}
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
    }
}