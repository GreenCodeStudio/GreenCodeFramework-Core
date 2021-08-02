function Run-UnitTests{
    Push-Location (Find-ProjectDir).Fullname
    foreach($d in (ls ./modules/)){
        if(test-path "./modules/$d/Tests"){
            echo "./modules/$d/Tests";
            ./vendor/bin/phpunit "./modules/$d/Tests"
        }
    }
    #yarn test
    Pop-Location
}
function Run-E2eTests{
    Push-Location (Find-ProjectDir).Fullname
    Run-TestEnvironment
    Pop-Location
}

function Run-TestEnvironment{
    Push-Location (Find-ProjectDir).Fullname
    Build-Project -Production
    cd public_html
    Start-Job -ScriptBlock  { php -S 0.0.0.0:8080}
    Pop-Location
}
