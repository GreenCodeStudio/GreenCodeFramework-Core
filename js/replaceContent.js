export function ReplaceContentHtml(oldElement, newHtml) {
    ReplaceContent(oldElement, document.create('', {html: newHtml}));
}

function AdaptNode(candidate, node) {
    if (candidate.nodeType == document.ELEMENT_NODE) {
        for (let attribute of Array.from(candidate.attributes)) {
            if (!node.attributes[attribute.name]) {
                candidate.removeAttributeNode(attribute);
            }
        }
        for (let attribute of node.attributes) {
            let candidateAttribute = candidate[attribute.name];
            if (!candidateAttribute || candidateAttribute.value != attribute.value) {
                candidate.setAttribute(attribute.name, attribute.value);
            }
        }
    } else if (candidate.nodeType == document.TEXT_NODE) {
        candidate.textContent = node.textContent;
    }
    ReplaceContent(candidate, node)
}

export function ReplaceContent(oldElement, newElement) {
    let readyNodes = [];
    let oldNodes = Array.from(oldElement.childNodes);
    let unusedNodes = oldNodes.slice();
    for (let node of newElement.childNodes) {
        let candidate = unusedNodes.find(n => {
            if (node.nodeType == document.ELEMENT_NODE)
                return n.nodeType == document.ELEMENT_NODE && n.tagName == node.tagName;
            else
                return n.nodeType == node.nodeType;
        });
        if (candidate) {
            unusedNodes.splice(unusedNodes.indexOf(candidate), 1);
            AdaptNode(candidate, node);
            readyNodes.push(candidate);
        } else {
            readyNodes.push(node);
        }
    }
    unusedNodes.forEach(n => n.remove());
    for (let i = 0; i < readyNodes.length; i++) {
        if (oldElement.childNodes[i] != readyNodes[i])
            oldElement.append(readyNodes[i]);
    }
}