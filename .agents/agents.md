---
kind: agents
---

# Project Guidelines

## folder structure
in main folder there is folder called modules. Each module has its own folder. Each module has the same structure and files inside are combined (for example all js/index.js are imported, all package.json are merged).

## fontello icons
in each module can be file called fontello.json. It contains definitions of vector single-color icons. Path contains svg path (content of <path d="..."/> tag). It contains only one path that will have single-color fill and no stroke. While compilation all icons from all modules are merged into one .woff file and modules/Common/scss/fontello.scss file is generated, containing classes for each icon. Class name is .icon-<css> where <css> is value of "css" attribute in fontello.json.
