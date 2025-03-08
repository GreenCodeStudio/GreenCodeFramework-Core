function Init-Project
{
    Test-Requirements
    Push-Location (Find-ProjectDir).Fullname

    git submodule sync --recursive
    git submodule update --init --recursive

    $dirs = "js", "scss", "modules", "cache", "public_html", "public_html/dist"
    $dirs | %{
        if (!(test-path $_))
        {
            md $_
        }
    }
    if (!(test-path config.json))
    {
        $config = @{ packages = @{ Core = "https://gitlab.com/MateuszKrawczyk/framework_core.git" } }
        $config | convertto-json | Out-FileUtf8NoBom "config.json"
    }

    if (!(test-path public_html/index.php))
    {
        echo "<?php include_once __DIR__.'/../modules/Core/Init.php';" | Out-FileUtf8NoBom "public_html/index.php"
    }

    if (!(test-path .env))
    {
        echo "cached_code=0
db='mysql:dbname=example;host=localhost'
dbUser=root
dbSchema=example
dbPass=pass1234
dbDialect=mysql
debug=true
websocketPort=81
UploadedFiles=UploadedFiles
host=localhost_example
" | Out-FileUtf8NoBom ".env"
    }

    if (!(test-path modules/Common/PageStandardController.php))
    {
        echo "<?php

namespace Common;

class PageStandardController extends \Core\StandardController
{}
" | Out-FileUtf8NoBom "modules/Common/PageStandardController.php"
    }
    if (!(test-path modules/Common/PageConsoleController.php))
    {
        echo "<?php

namespace Common;

class PageConsoleController extends \Core\ConsoleController
{}
" | Out-FileUtf8NoBom "modules/Common/PageConsoleController.php"
    }

    if (!(test-path modules/Common/PageAjaxController.php))
    {
        echo "<?php

namespace Common;

class PageAjaxController extends \Core\AjaxController
{}
" | Out-FileUtf8NoBom "modules/Common/PageAjaxController.php"
    }
    if (!(test-path modules/Common/Controllers))
    {
        mkdir modules/Common/Controllers
    }

    if (!(test-path modules/Common/Controllers/StartController.php))
    {
        echo "<?php
namespace Common\Controllers;
use Common\PageStandardController;

class StartController extends PageStandardController
{
    public function index()
    {

    }
}
" | Out-FileUtf8NoBom "modules/Common/Controllers/StartController.php"
    }

    if (!(test-path public_html/.htaccess))
    {
        echo "RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]" | Out-FileUtf8NoBom public_html/.htaccess
    }
    Download-ProjectModules
    Pop-Location
}
function Repair-Build
{
    rm "node_modules" -Recurse
    rm "public_html/dist" -Recurse
    rm "package.json"
    rm "yarn.lock"
    rm "composer.json"
    rm "composer.lock"
    rm "cache" -Recurse
    Build-Project
}
function Prepare-Build
{
    if (!(Test-Path "tmp"))
    {
        mkdir "tmp"
    }
    if (!(test-path "webpack.config.js"))
    {
        copy "modules/Core/default.webpack.config.js" "./webpack.config.js"
    }
    if (!(test-path "package.json"))
    {
        copy "modules/Core/package.json" "./package.json"
        yarn
    }
    if (!(test-path "composer.json"))
    {
        copy "modules/Core/composer.json" "./composer.json"
        composer install
    }

    node "modules/Core/js/build.js"

    Get-ChildItem modules | ? { Test-Path "modules/$( $_.Name )/dist" } | %{ New-SymLink "./public_html/dist/$( $_.Name )" "../../modules/$( $_.Name )/dist" }
   if(-not (Test-Path "./public_html/dist/serviceWorker.js")){
       New-SymLink "./public_html/serviceWorker.js" "./public_html/dist/serviceWorker.js"
   }

    $file = ""
    Get-ChildItem modules | ? { Test-Path "modules/$( $_.Name )/scss/mixins.scss" } | % { $file += "@import ""./modules/" + $_.Name + "/scss/mixins.scss"";`r`n" }
    Get-ChildItem modules | ? { Test-Path "modules/$( $_.Name )/scss/index.scss" } | % { $file += "@import ""./modules/" + $_.Name + "/scss/index.scss"";`r`n" }
    $file | Out-FileUtf8NoBom "scssBuild.scss"

    $file = "import ""./scssBuild.scss"";`r`n"
    Get-ChildItem modules | ? { Test-Path "modules/$( $_.Name )/js/index.js" } | % { $file += "import  ""./modules/" + $_.Name + "/js/index"";`r`n" }
    $file | Out-FileUtf8NoBom "jsBuild.js"

    $composerIncludes = [System.Collections.ArrayList]::new();
    Get-ChildItem modules | ? { Test-Path "modules/$( $_.Name )/composer.json" } | %{ $composerIncludes.Add("modules/$( $_.Name )/composer.json") }
    $composer = @{ require = @{ "wikimedia/composer-merge-plugin" = "dev-master" }; config = @{ "allow-plugins" = @{ "wikimedia/composer-merge-plugin" = $true } }; extra = @{ "merge-plugin" = @{ include = $composerIncludes } } }
    $composer | convertto-json -Depth 10 | Out-FileUtf8NoBom "composer.json"
}
function Generate-Htaccess
{
    $timestamp = [int][double]::Parse((Get-Date -UFormat %s))
    $content = @"
    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule . index.php?oryginal_request_uri=%{REQUEST_URI}&%{QUERY_STRING} [L]

    <IfModule mod_headers.c>
        <If "%{QUERY_STRING} =~ /File.get/">
        </If>
        <Else>
            Header set Cache-Control "no-cache, no-store, must-revalidate"
            Header set Pragma "no-cache"
            Header set Expires 0
        </Else>
        Header set Service-Worker-Allowed "/"
        Header set x-sw-version "${timestamp}"
        <FilesMatch "/dist/.*">
            Header set x-sw-cache "1"
        </FilesMatch>
    </IfModule>
"@

    echo $content | Out-FileUtf8NoBom ./public_html/.htaccess
}
function Build-Project([switch]$production)
{
    Push-Location (Find-ProjectDir).Fullname
    Init-Project

    Prepare-Build

    Generate-Htaccess

    composer install
    yarn
    if ($production)
    {
        yarn buildProd
    }
    else
    {
        yarn build
    }
    Pop-Location
}
function Start-Project
{
    Push-Location (Find-ProjectDir).Fullname
    Init-Project

    Prepare-Build

    Generate-Htaccess

    composer install
    yarn
    Start-Job -scriptBlock {
        param($dir)
        cd $dir
        php ./modules/Core/Schedule.php
    } -ArgumentList (Find-ProjectDir).Fullname
    yarn start
    Pop-Location
}
function Find-ProjectDir()
{
    $dir = get-item .;
    while ($dir)
    {
        if ( $dir.GetFiles("config.json"))
        {
            return $dir;
        }
        $dir = $dir.Parent;
    }
    return get-item .;
}
function Load-AvaibleMethods
{
    Get-AvaibleMethods | ? Name | % {
        $params = ($_.parameters | % {
            if ($_.isOptional){
                $mandatory='$false'
            }else{
                $mandatory='$true'
            }
            if ($_.pipelineInput){
                $valueFromPipeline='$true'
            }else{
                $valueFromPipeline='$false'
            }
            "[Parameter(Mandatory=$mandatory, ValueFromPipeline=$valueFromPipeline)]`$$( $_.name )"

        })
        $paramsString = $params | & { $ofs = ','; "$input" }
        $paramsUse = ($_.parameters | % { "`$$( $_.name )" } | & { $ofs = ','; "$( $input )" })
        iex  "Set-Item -Path function:global:$( $_.name )-$( $_.controllerName ) -Value {Param ($paramsString) return Run-Command $( $_.controllerName ) $( $_.name ) @($paramsUse) }"
    }
}
function Get-AvaibleMethods
{
    return Run-Command System GetMethods;
}
function RunVerbose-Command([Parameter(Mandatory = $true)][String]$controller, [Parameter(Mandatory = $true)][String]$action, [Object[]]$params = @())
{
    $obj = @{ controller = $controller; action = $action; args = $params; verbose = $true }
    $obj | convertto-json | php ./modules/Core/Console.php
}
function Run-Command([Parameter(Mandatory = $true)][String]$controller, [Parameter(Mandatory = $true)][String]$action, [Parameter(ValueFromPipeline = $true)][Object[]]$params = @())
{

    $obj = @{ controller = $controller; action = $action; args = $params }
    $ret = ($obj | convertto-json | php ./modules/Core/Console.php)
    try
    {
        $retObj = $ret | ConvertFrom-Json;
    }
    catch
    {
        Write-Host "skrypt PHP nie wykonał się poprawnie" -ForegroundColor Red
        Write-Host $ret -BackgroundColor Red -ForegroundColor White
        return;
    }
    $retObj.debug | %{
        $line = $_.backtrace[0];
        Write-Host ($line.function + "() " + $line.file + ":" + $line.line) -ForegroundColor blue;
        if ($_.jsons)
        {
            $_.jsons | %{ ConvertFrom-Json $_ }|%{
                Write-Host $_ -ForegroundColor Green;
            };
        }
        $_.strings | %{
            Write-Host $_ -ForegroundColor Green;
        };
    }
    if ($retObj.error)
    {
        $retObj.error.stack | ft
        throw [PhpException]::New($retObj.error);
    }
    return $retObj.data;
}
function New-SymLink($link, $target)
{
    echo "New-SymLink" $link $target
    if ($env:OS -Like "Windows*")
    {
        $link = $link.replace('/', '\\')
        $target = $target.replace('/', '\\')
        $command = "cmd /c mklink /d"
        invoke-expression "$command ""$link"" ""$target"""
    }
    else
    {
        New-Item -Path $link -ItemType SymbolicLink -Value $target
    }
}

function Out-FileUtf8NoBom
{

    [CmdletBinding()]
    param (
        [Parameter(Mandatory, Position = 0)] [string] $LiteralPath,
        [switch] $Append,
        [switch] $NoClobber,
        [AllowNull()] [int] $Width,
        [Parameter(ValueFromPipeline)] $InputObject
    )

    #requires -version 3

    # Make sure that the .NET framework sees the same working dir. as PS
    # and resolve the input path to a full path.
    [System.IO.Directory]::SetCurrentDirectory($PWD) # Caveat: .NET Core doesn't support [Environment]::CurrentDirectory
    $LiteralPath = [IO.Path]::GetFullPath($LiteralPath)

    # If -NoClobber was specified, throw an exception if the target file already
    # exists.
    if ($NoClobber -and (Test-Path $LiteralPath))
    {
        Throw [IO.IOException]"The file '$LiteralPath' already exists."
    }

    # Create a StreamWriter object.
    # Note that we take advantage of the fact that the StreamWriter class by default:
    # - uses UTF-8 encoding
    # - without a BOM.
    $sw = New-Object IO.StreamWriter $LiteralPath, $Append

    $htOutStringArgs = @{ }
    if ($Width)
    {
        $htOutStringArgs += @{ Width = $Width }
    }

    # Note: By not using begin / process / end blocks, we're effectively running
    #       in the end block, which means that all pipeline input has already
    #       been collected in automatic variable $Input.
    #       We must use this approach, because using | Out-String individually
    #       in each iteration of a process block would format each input object
    #       with an indvidual header.
    try
    {
        $Input | Out-String -Stream @htOutStringArgs | % { $sw.WriteLine($_) }
    }
    finally
    {
        $sw.Dispose()
    }

}
function Enable-PhpDebug
{
    $env:XDEBUG_SESSION = 1
}

function Start-WebSocketServer
{
    Push-Location (Find-ProjectDir).Fullname
    php modules/Core/initWebsocketService.php
}
function Start-DevServer([int] $port = 80)
{
    Push-Location (Find-ProjectDir).Fullname
    cd public_html
    php -S 0.0.0.0:$port
}

function Analyze-Problems
{
    try
    {
        Get-Command yarn
    }
    catch
    {
        echo "Yarn not found, download from https://yarnpkg.com/getting-started/install"
    }

    try
    {
        Get-Command php
    }
    catch
    {
        echo "php not found"
    }

    Get-ProjectModules | ft *

}
function Test-Requirements
{
    $isGood = $true;
    try
    {

    }
    catch
    {

    }
    return $isGood;
}
. ./modules/Core/PowerShell/modules.ps1
. ./modules/Core/PowerShell/exception.ps1
. ./modules/Core/PowerShell/tests.ps1
if ((test-path modules/Core) -and (test-path vendor))
{
    Load-AvaibleMethods
}

function Run-PhpStan
{
    Push-Location (Find-ProjectDir).Fullname

    vendor/bin/phpstan analyse -c modules/Core/phpstan.neon modules --level 0 --memory-limit=1024G
    $code = $LASTEXITCODE

    Pop-Location

    if ($code -ne 0)
    {
        exit $code
    }
}
function Run-ScheduleJobs
{
    Push-Location (Find-ProjectDir).Fullname

    php modules/Core/Schedule.php
}

echo "aa";
$args | ft *
$functionName = $args[0]
if ($functionName)
{
    $function = Get-Command -Name $functionName
}
else
{
    $function = $false

}
if ($function)
{
    $scriptBlock = [System.Management.Automation.ScriptBlock]::Create($functionName)
    Invoke-Command -ScriptBlock $scriptBlock -ArgumentList $args[1..($args.Length - 1)]
}
else
{
    echo "common commands:"
    echo "    Build-Project"
    echo "    Analyze-Problems"
    echo "    Start-Project"
    echo "    Repair-Build"
}
