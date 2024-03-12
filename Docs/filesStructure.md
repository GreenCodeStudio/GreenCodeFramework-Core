# in main directory
(not all files/directories are allways present)

### ./tests/ApprovedScrenshots
used in automated tests on selenium. Tests will run app, make screnshots and compare with these in this folder.

### ./cache
Auto generated files, meant for better performance. You can remove in anytime, but keep empty folder

### ./js
deprecated, not used now

### ./modules
each subfolder is a module. Can be normal folder or separate git repository

### ./node_modules
Auto generated. Contains packages downloaded by yarn

### ./public_html
Contains files, that will be visible by http. This folder should be main folder for http server (like Apache or nginx)

Contains also index.php file, that is main entry to app 

**Do not put any sensitive informations inside this folder, becouse all files can be accesed by any user, even without login)**

### ./scss
deprecated, not used now

### ./tmp
temporary files, like logs. You can remove in anytime, but keep empty folder

### ./UploadedFiles
Files, that was uploaded by users using app.

### ./vendor
Auto generated. Similar to node_modules, but contains packages for php 

### ./.env
environmental config, like database username and password. This file is meant to be different on each machine, so it is not commited to git, but is required to run

### ./.env.example
template how .env file should look like. Example file is not read by app, it is only for developers to look on

### ./.gitignore
definition of files, that shouldn't be commited to git (like auto generated files)

### ./.gitlab-ci.yml
Configuration of CI/CD on gitlab

### ./composer.json
Auto generated file. Do not edit manually, in case of problems you can delete it and run build-project or start-project to recreate it. 

### ./composer.lock
Contains information about loaded packages by composer to vendor directory. In case of problems delete this file.

### ./config.json
Config of project, not contains too much usefull information

### ./jsBuild.js
Auto generated file. Do not edit manually, in case of problems you can delete it and run build-project or start-project to recreate it. 

### ./package.json
Auto generated file. Do not edit manually, in case of problems you can delete it and run build-project or start-project to recreate it. 

### ./script.ps1
main powershell script. It mostly load ./modules/Core/script.ps1

### ./scssBuild.json
Auto generated file. Do not edit manually, in case of problems you can delete it and run build-project or start-project to recreate it. 

### ./webpack.config.js
Configuration of webpack

### ./yarn.lock
Contains information about loaded packages by yarn to node_modules directory. In case of problems delete this file.


# in each module
this files/directories are in each module. All of it are optional.


### ./modules/*{name}*/Ajax
Contains ajax controllers. More in [GeneralArchitecture](GeneralArchitecture.md)

### ./modules/*{name}*/Controllers
Contains standard page controllers. More in [GeneralArchitecture](GeneralArchitecture.md)

### ./modules/*{name}*/Dist
Contains files, that will be available by http (like images, fonts etc.). Will by copied (ar available by symlink) to ./public_html/dist/*{name}*/
### ./modules/*{name}*/js
Contains javascript. index.js file will be imported automatically, other files need to be imported in index.js

### ./modules/*{name}*/scss
Contains styles, in form os SCSS. index.scss file will be imported automatically, other files need to be imported in index.scss

### ./modules/*{name}*/Repository
Contains repository classes. More in [GeneralArchitecture](GeneralArchitecture.md)
### ./modules/*{name}*/Views
Contains views. Can be php or mpts files. More in [GeneralArchitecture](GeneralArchitecture.md)
### ./modules/*{name}*/composer.json
Contains packages, that should be loaded by composer to ./vendor
### ./modules/*{name}*/db.xml
Contains definition of database tables. Using Upgrade-Migration command thes table will be created
### ./modules/*{name}*/i18n.xml
Translations for apps, that supports multiple languages.

### ./modules/*{name}*/package.json
Contains packages, that should be loaded by yarn to ./node_modules
### ./modules/*{name}*/menu.xml
Not supported in all projects.

Contains links, that should be visible in main menu.

### ./modules/*{name}*/permissions.xml
Not supported in all projects.

Contains list of permissions, that can be granted to user.
