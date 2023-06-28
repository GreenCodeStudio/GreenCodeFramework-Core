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
function Run-E2eTests {
    try {
        Push-Location (Find-ProjectDir).FullName
        Write-Host "Start of composer install"
        composer install
        Write-Host "End of composer install"

        Write-Host "Start of migration"
        try {
            Run-Command Migration preview
        }
        catch {
            Write-Host $_
        }

        try {
            Run-Command Migration upgrade
        }
        catch {
            Write-Host $_
        }
        Write-Host "Migration completed"

        $mail = "e2etest_" + -join ((65..90) + (97..122) | Get-Random -Count 5 | ForEach-Object { [char]$_ }) + "@green-code.studio"
        $password = -join ((65..90) + (97..122) | Get-Random -Count 20 | ForEach-Object { [char]$_ })
        try {
            $user = Run-Command User add @("Test", "Admin", $mail, $password)
            Run-Command User addAllPermissions @($user.id)
        }
        catch {
            Write-Host $_
        }
        Write-Host "User added"

        Run-TestEnvironment
        Write-Host "Start Selenium"
        node ./modules/E2eTests/Test/Selenium/init.js $mail $password

        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }

        Pop-Location
    }
    catch {
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
    $job = Start-Job -ScriptBlock  {
        php -S 0.0.0.0:8080 -t public_html *> ./tmp/TestEnvironment.log
    }
    return $job;
}
