export class PaginationButtons extends HTMLElement {
    constructor() {
        super();
        this.currentPage = 0;
        this.totalPages = 0;
        this.onpageclick = null;
    }

    render() {
        let pagination = this.getPagination();
        this.children.removeAll();
        for (let pageNumber of pagination) {
            if (pageNumber == null) {
                this.addChild('span', {text: '...'});
            } else {
                let pageButton = this.addChild('button', {
                    text: pageNumber + 1,
                    className: pageNumber == this.currentPage ? 'active' : ''
                });
                pageButton.onclick = () => {
                    if (this.onpageclick) {
                        this.onpageclick(pageNumber);
                    }
                    this.currentPage = pageNumber;
                    this.render();
                };
            }
        }
    }

    getPagination() {
        const boxesCount = 7;
        let boxes = [];
        if (this.totalPages <= boxesCount) {
            for (let i = 0; i < this.totalPages; i++) {
                boxes.push(i);
            }
        } else if (this.currentPage <= (boxesCount - 1) / 2 - 1) {
            for (let i = 0; i <= boxesCount - 3; i++) {
                boxes.push(i);
            }
            boxes.push(null);
            boxes.push(this.totalPages - 1);
        } else if (this.currentPage >= this.totalPages - (boxesCount - 1) / 2 - 1) {
            boxes.push(0);
            boxes.push(null);
            for (
                let i = pages - Math.floor((boxesCount - 1) / 2) - 2;
                i < this.totalPages;
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
            boxes.push(this.totalPages - 1);
        }
        return boxes;
    }
}

customElements.define('pagination-buttons', PaginationButtons);