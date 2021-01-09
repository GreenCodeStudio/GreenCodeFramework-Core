import {TableView} from "./tableView";
import {t} from "../../i18n.xml";
import {PaginationButtons} from "./paginationButtons";
import {ContextMenu} from "../contextMenu";

export class ObjectsList extends HTMLElement {
    constructor(datasource) {
        super();
        this.columns = [];
        this.generateActions = () => {
        };
        this.datasource = datasource;
        this.datasource.onchange = () => this.refresh();
        this.start = 0;
        this.limit = 10
        this.total = 0;
        this.selected = new Set();
        this.selectedMain = null;
        this.dataById = new Map();
        this.initFoot();
        this.addEventListener('contextmenu', e => this.showGlobalContextMenu(e));
        addEventListener('resize', e => this.resize());
        this.infiniteScrollEnabled = false;
    }

    refreshLimit() {
        this.limit = this.insideView.calcMaxVisibleItems(this.clientHeight - this.foot.clientHeight - 2);
        if (this.limit < 1) this.limit = 1;
        if (this.infiniteScrollEnabled) {
            this.limit = Math.ceil(this.limit / 20) * 20 + 40;
        } else {
            this.start = Math.floor(Math.min(this.total, this.start) / this.limit) * this.limit;
        }
    }

    async refresh() {
        this.classList.toggle('infiniteScrollEnabled', this.infiniteScrollEnabled);

        if (!this.insideView)
            this.initInsideView();

        this.refreshLimit();
        const refreshSymbol = Symbol();
        this.lastRefreshSymbol = refreshSymbol;
        let data = await this.datasource.get(this);
        if (this.lastRefreshSymbol == refreshSymbol) {
            this.currentRows = data.rows;
            this.fillDataById(data.rows);
            this.total = data.total;
            this.insideView.loadData(data, this.start, this.limit, this.infiniteScrollEnabled);
            this.pagination.currentPage = Math.floor(this.start / this.limit);
            this.pagination.totalPages = Math.ceil(this.total / this.limit);
            this.pagination.render();
        }
    }

    initInsideView() {
        this.insideView = new TableView(this);
        this.insertBefore(this.insideView, this.foot);
        this.insideView.onPaginationChanged = (start, limit) => {
            if (this.start != start) {
                this.start = start;
                //this.limit=limit;
                this.refresh();
            }
        }
    }

    fillDataById(rows) {
        for (let row of rows) {
            this.dataById.set(parseInt(row.id), row);
        }
    }

    getSelectedData() {
        let ids = [];
        if (this.selectedMain)
            ids.push(this.selectedMain);
        ids = [...ids, ...Array.from(this.selected).filter(id => id != this.selectedMain)];
        return ids.map(id => this.dataById.get(parseInt(id)));
    }

    initFoot() {
        this.foot = this.addChild('.foot');
        let menuButton = this.foot.addChild('button.menuButton span.icon-settings');
        menuButton.onclick = e => this.showGlobalContextMenu(e);
        this.pagination = new PaginationButtons();
        this.pagination.onpageclick = (page) => {
            this.start = page * this.limit;
            this.refresh();
        }
        this.foot.append(this.pagination);
        this.searchForm = this.foot.addChild('form', {className: 'search'});
        this.searchForm.onsubmit = e => e.preventDefault();
        const searchInput = this.searchForm.addChild('input', {
            name: 'search',
            type: 'search',
            placeholder: t('objectList.search')
        });
        searchInput.oninput = e => {
            this.start = 0;
            this.refresh();
        }

    }

    get search() {
        if (!this.foot) return '';
        const searchForm = this.foot.querySelector('.search');
        if (!searchForm) return '';
        return searchForm.search.value;
    }

    showGlobalContextMenu(e) {
        let elements = [{
            text: t('objectList.paginationMode'),
            icon: 'icon-pagination',
            onclick: () => {
                this.infiniteScrollEnabled = false;
                this.refresh();
            }
        }, {
            text: t('objectList.scrollMode'),
            icon: 'icon-scroll',
            onclick: () => {
                this.infiniteScrollEnabled = true;
                this.refresh();
            }
        }];
        ContextMenu.openContextMenu(e, elements);
    }

    resize() {
        let limit = this.limit;
        this.refreshLimit();
        if (limit != this.limit) {
            this.refresh();
        }
    }
}

customElements.define('data-view', ObjectsList);