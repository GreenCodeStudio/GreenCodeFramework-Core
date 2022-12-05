To run project you need
* powershell (built-in on windows, need to be installed on mac & linux)
* git (available from command line)
* php
* composer (php package manager)
* nodejs (be carefull, because too new node can make problems with node sass)
* yarn (node package manager)
* redis
* rabbitMQ (optional)
* mysql (or MariaDB, but prefered mysql)

in main folder of project you need to have .env file, contains environmental data, like mysql connections

from Powershell run command
```powershell
. ./script.ps1
```
it will load scripts. You need to run it each time you open new powershell window

After loading scripts you can run:
```powershell
Start-Project
```

in case it don't work, you can run separatelly in 2 other powershell windows 
```powershell
Build-Project
```

```powershell
cd public_html
php -S 0.0.0.0:80
```

Project will run as http://localhost/