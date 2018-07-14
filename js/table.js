import './domExtensions';
export class tableManager {
    constructor(table, datasource) {
        this.table = table;
        this.datasource = datasource;
        this.start=0;
        this.limit=50;
        this.search='';
        this.sort='';
    }

    async refresh() {
        let data = await this.datasource.get(this);
        this.loadData(data);
    }

    loadData(data) {
        let tbody = this.form.tBodies[0];
        if (!tbody) {
            tbody = this.form.add('tbody');
        }
        tbody.children.removeAll();

        for(let row of data.rows){
            let tr=tbody.add('tr');
            for(let th in this.form.thead.firstElementChild.children){
                let td=tr.add('td');
            }
        }
    }
}