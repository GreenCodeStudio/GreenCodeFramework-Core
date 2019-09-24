function Init-Project
{
    Push-Location (Find-ProjectDir).Fullname
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

    node "modules/core/js/build.js"

    ls modules | ? { Test-Path "modules/$_/dist" } | %{ New-SymLink "./public_html/dist/$_" "../../modules/$_/dist" }

    ls modules | ? { "Test-Path modules/$_/scss" } | %{ New-SymLink "./scss/$_" "../modules/$_/scss" }
    $file = ''
    ls modules | ? { Test-Path "modules/$_/scss/mixins.scss" } | % { $file += "@import ""./" + $_ + "/mixins"";`r`n" }
    ls modules | ? { Test-Path "modules/$_/scss/index.scss" } | % { $file += "@import ""./" + $_ + "/index"";`r`n" }
    $file | Out-FileUtf8NoBom "scss/build.scss"

    ls modules | ? { Test-Path "modules/$_/js" } | %{ New-SymLink "./js/$_" "../modules/$_/js" }
    $file = ''
    ls modules | ? { Test-Path "modules/$_/js/index.js" } | % { $file += "require( ""./" + $_ + "/index"");`r`n" }
    $file | Out-FileUtf8NoBom "js/build.js"

    $composerIncludes = [System.Collections.ArrayList]::new();
    ls modules | ? { Test-Path "modules/$_/composer.json" } | %{ $composerIncludes.Add("modules/$_/composer.json") }
    $composer = @{ require = @{ "wikimedia/composer-merge-plugin" = "dev-master" }; extra = @{ "merge-plugin" = @{ include = $composerIncludes } } }
    $composer | convertto-json -Depth 10 | Out-FileUtf8NoBom "composer.json"
}
function Build-Project
{
    Push-Location (Find-ProjectDir).Fullname
    Init-Project

    Prepare-Build

    composer install
    yarn
    yarn build
    Pop-Location
}
function Start-DevelopmentServer([Int]$port = 8000)
{
    Push-Location (Find-ProjectDir).Fullname
    try
    {
        Init-Project
        cd public_html
        php -S "localhost:$port"

    }
    finally
    {
        Pop-Location
    }
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
            if ($_.isOptional)
            {
                "`$$( $_.name )"
            }
            else
            {
                "[Parameter(Mandatory=`$true)]`$$( $_.name )"
            }
        })
        $paramsString = $params | & { $ofs = ','; "$input" }
        $paramsUse = ($_.parameters | % { "`$$( $_.name )" } | & { $ofs = ','; "$( $input )" })
        iex  "Set-Item -Path function:global:Run-$( $_.controllerName )-$( $_.name ) -Value {Param ($paramsString) return Run-Command $( $_.controllerName ) $( $_.name ) @($paramsUse) }"
    }
}
function Get-AvaibleMethods
{
    return Run-Command System GetMethods;
}
function Run-Command([Parameter(Mandatory = $true)][String]$controller, [Parameter(Mandatory = $true)][String]$action, [Object[]]$params = @())
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
    if ($retObj.output.Length -gt 0)
    {
        Write-Host $retObj.output;
    }
    $retObj.debug | %{
        $line = $_.backtrace[0];
        Write-Host ($line.function + "() " + $line.file + ":" + $line.line) -ForegroundColor blue;

        $_.vars |%{
            Write-Host $_ -ForegroundColor Green;
        };
    }
    return $retObj.data;
}
function New-SymLink($link, $target)
{
    <# if ($PSVersionTable.PSVersion.Major -ge 5)
    {
        New-Item -Path $link -ItemType SymbolicLink -Value $target
    }
    else
    {#>
    $link = $link.replace('/', '\\')
    $target = $target.replace('/', '\\')
    $command = "cmd /c mklink /d"
    invoke-expression "$command ""$link"" ""$target"""
    #}
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
. ./modules/Core/PowerShell/modules.ps1
if ((test-path modules/Core) -and (test-path vendor))
{
    Load-AvaibleMethods
}