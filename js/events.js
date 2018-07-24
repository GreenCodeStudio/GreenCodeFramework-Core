export function setEvent(type, selector, callback) {
    if (!setEvent.events[type]) {
        setEvent.events[type] = [];
        document.addEventListener(type, e => {
            var elem = e.target;
            let callbacks = setEvent.events[type];
            if (!callbacks) return;
            let propagation = true;
            let inlevelPropagation = true;
            while (elem && elem != document && propagation) {
                for (var x of callbacks) {
                    if (!inlevelPropagation) break;
                    try {
                        if (elem.matches(x.selector)) {
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
    setEvent.events[type].push({selector, callback});
}

setEvent.events = {};