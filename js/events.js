const eventsSymbol = Symbol('events');

export function setEvent(type, selector, callback, root = document) {
    if (!root[eventsSymbol])
        root[eventsSymbol] = {};
    if (!root[eventsSymbol][type]) {
        root[eventsSymbol][type] = [];
        root.addEventListener(type, e => {
            let elem = e.target;
            let callbacks = root[eventsSymbol][type];
            if (!callbacks) return;
            let propagation = true;
            let inlevelPropagation = true;
            while (elem && elem != root && propagation) {
                for (var x of callbacks) {
                    if (!inlevelPropagation) break;
                    try {
                        if (elem.matches && elem.matches(x.selector)) {
                            x.callback.call(elem, {
                                target: elem, originalEvent: e, preventDefault: () => {
                                    e.preventDefault()
                                }, stopPropagation: () => {
                                    propagation = false;
                                    e.stopPropagation();
                                }, stopImmediatePropagation: () => {
                                    propagation = false;
                                    inlevelPropagation = false;
                                    e.stopImmediatePropagation();
                                }
                            });
                        }
                    } catch (ex) {
                        console.error(ex);
                    }
                }
                elem = elem.parentNode;
            }
        })
    }
    root[eventsSymbol][type].push({selector, callback});
}