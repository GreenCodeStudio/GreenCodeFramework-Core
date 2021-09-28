function Run-UnitTests{
    try{
        Push-Location (Find-ProjectDir).Fullname
        Prepare-Build
        composer install
        yarn
        foreach($d in (ls ./modules/)){
            if(test-path "./modules/$d/Tests"){
                echo "./modules/$d/Tests";
                ./vendor/bin/phpunit "./modules/$d/Tests"
            }
        }
        #yarn test
        Pop-Location
    }
    catch{
      Write-Host "An error occurred:"
      Write-Host $_
      exit -1
    }
}
function Run-E2eTests{
    try{
        Push-Location (Find-ProjectDir).Fullname
        composer install
        composer install
        try{
        Run-Command Migration Preview
        }catch{
       Write-Host $_
       }
       try{
        Run-Command Migration Upgrade
        }catch{
       Write-Host $_
       }
       try{
        Run-Command System server
        }catch{
       Write-Host $_
       }
        Run-TestEnvironment
        node ./modules/E2eTests/Selenium/selenium.js
        Pop-Location
    }
    catch{
       Write-Host "An error occurred:"
       Write-Host $_
      exit -1
    }
}

function Run-TestEnvironment{
    Push-Location (Find-ProjectDir).Fullname
    Build-Project -Production
    cd public_html
    Start-Job -ScriptBlock  { php -S 0.0.0.0:8080}
    Pop-Location
}
