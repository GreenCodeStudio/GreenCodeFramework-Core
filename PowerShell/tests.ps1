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
        Push-Location (Find-ProjectDir).Fullname
        echo "start of composer install"
        composer install
        echo "start of composer install 2"
        composer install
        echo "start of migration"
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

        echo "migrated"
        $mail = "e2etest_" + -join ((65..90) + (97..122) | Get-Random -Count 5 | % { [char]$_ }) + "@green-code.studio"
        $password = -join ((65..90) + (97..122) | Get-Random -Count 20 | % { [char]$_ })
        Run-Command User add @("Test", "Admin", $mail, $password)
        echo "user added"

        Run-TestEnvironment
        echo "start selenium"
        node ./modules/E2eTests/Selenium/selenium.js $mail $password
        Pop-Location
    }
    catch
    {
        Write-Host "An error occurred:"
        Write-Host $_
        exit -1
    }
}

function Run-TestEnvironment
{
    echo "starting etst environment"
    Push-Location (Find-ProjectDir).Fullname
    Build-Project -Production
    cd public_html
    Start-Job -ScriptBlock  { php -S 0.0.0.0:8080 }
    Pop-Location
}
