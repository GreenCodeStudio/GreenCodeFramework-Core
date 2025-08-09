function Run-UnitTests
{
    try
    {
        Push-Location (Find-ProjectDir).Fullname
        Prepare-Build
        composer install
        yarn
        foreach ($d in (ls ./modules/))
        {
            if (test-path "./modules/$d/Tests")
            {
                echo "./modules/$d/Tests";
                ./vendor/bin/phpunit "./modules/$d/Tests"
            }
        }
        #yarn test
        Pop-Location
    }
    catch
    {
        Write-Host "An error occurred:"
        Write-Host $_
        exit -1
    }
}
function Run-E2eTests
{
    try
    {
        $env:APP_ENVIRONMENT = "test"
        Push-Location (Find-ProjectDir).FullName
        Write-Host "Start of composer install"
        composer update
        Write-Host "End of composer install"

        Write-Host "Start of migration"
        try
        {
            Run-Command Migration preview
        }
        catch
        {
            Write-Host $_
        }

        try
        {
            Run-Command Migration upgrade
        }
        catch
        {
            Write-Host $_
        }
        Write-Host "Migration completed"

        $mail = "e2etest_" + -join ((65..90) + (97..122) | Get-Random -Count 5 | ForEach-Object { [char]$_ }) + "@green-code.studio"
        $password = -join ((65..90) + (97..122) | Get-Random -Count 20 | ForEach-Object { [char]$_ })
        try
        {
            $user = Run-Command User add @("Test", "Admin", $mail, $password)
            Run-Command User addAllPermissions @($user.id)
        }
        catch
        {
            Write-Host $_
        }
        Write-Host "User added"


        Run-TestEnvironment 8080
        Write-Host "Start Selenium"
        node ./modules/E2eTests/Test/Selenium/init.js $mail $password

        Pop-Location

        if ($LASTEXITCODE -ne 0)
        {
            return $LASTEXITCODE
        }

    }
    catch
    {
        Write-Host "An error occurred:"
        Write-Host $_
        return -1
    }
}
function Test-E2eTests
{
    node ./modules/E2eTests/Test/Selenium/init.js
}


function Run-TestEnvironment($port)
{
    echo "starting etst environment"
    Push-Location (Find-ProjectDir).Fullname
    Build-Project -Production
    $job = Start-Job -ScriptBlock  {
        param($app_env)
        $env:APP_ENVIRONMENT = $app_env
        $env:aaa = 1111
        $ENV:bbb = 1111
        php -S 0.0.0.0:8080 -t public_html -d variables_order= *> ./tmp/TestEnvironment.log
    } -ArgumentList $ENV:APP_ENVIRONMENT
    return $job;
}
