
export class Translator {
    constructor(languageHierarchy,node) {
        this.languageHierarchy=languageHierarchy;
        this.rootNode = node;
    }

    translate(q) {
        let path = q.split('.');
        let node = this.rootNode;
        for (let nodeName of path) {
            node = node.getChild(nodeName);
        }
        return node.getValue(this.languageHierarchy);
    }
}