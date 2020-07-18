class ProjectModule
{
    [string]$Name;
    [Boolean]$FolderExist;
    [Boolean]$HasGit;
    [Boolean]$InConfig;
    [string]$ConfigGitUrl;
    ProjectModule([string]$name, $configModules)
    {
        Push-Location (Find-ProjectDir).Fullname
        $this.Name = $name;
        $this.FolderExist = Test-Path "./modules/$name"
        $this.HasGit = Test-Path "./modules/$name/.git"
        $this.InConfig = ($configModules | ? Name -EQ $name).Length -gt 0
        if ($this.InConfig)
        {
            $this.ConfigGitUrl = ($configModules | ? Name -EQ $name)[0].Value
        }
        Pop-Location
    }

    Download()
    {
        Push-Location (Find-ProjectDir).Fullname
        if (!$this.FolderExist -and $this.InConfig)
        {
            $n = $this.name;
            if (!(Test-Path "./modules/$n"))
            {
                git submodule add $this.ConfigGitUrl ./modules/$n
            }
        }
        Pop-Location
    }
    Update()
    {
        if ($this.FolderExist -and $this.HasGit)
        {
            $projectDir = (Find-ProjectDir).Fullname;
            $n = $this.Name;
            Push-Location "$projectDir/modules/$n"
            git pull
            Pop-Location
        }
    }
}
function Get-ProjectModules
{
    Push-Location (Find-ProjectDir).Fullname
    $config = (Get-Content ./config.json | ConvertFrom-Json);
    $configModules = $config.packages.PSObject.Properties | % {
        return $_.Name;
    }
    $realModules = Get-ChildItem "./modules" | %{ return $_.Name }
    $names = ($configModules + $realModules) |Select-Object -Unique
    $ret = $names | %{ return [ProjectModule]::new($_, $config.packages.PSObject.Properties) }
    Pop-Location
    return $ret;
}
function Download-ProjectModules([Parameter(ValueFromPipeline)] $item)
{
    if ($item)
    {
        process{
            $item.Download()
        }
    }
    else
    {
        Get-ProjectModules| %{ $_.Download() }
    }
}
function Update-ProjectModules
{
    if ($item)
    {
        process{
            $item.Update()
        }
    }
    else
    {
        Get-ProjectModules| %{ $_.Update() }
    }
}
