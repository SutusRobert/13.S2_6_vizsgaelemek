*** Settings ***
Resource    resources/MagicFridge.resource
Suite Setup    Open MagicFridge
Suite Teardown    Close MagicFridge
Test Setup    Log In With Test User
Test Teardown    Log Out If Logged In

*** Test Cases ***
Recipes Page Shows Search And Own Recipes Area
    Go To App Page    /recipes
    Wait Until Page Contains    Own recipes
    Page Should Contain Element    css:input[name="q"]
    Page Should Contain Button    Search
    Page Should Contain Link    +Add new recipes

Own Recipe Form Can Be Filled With Fast Example
    Go To App Page    /recipes/own/create
    Wait Until Page Contains    Own recipes
    Click Button    xpath=//button[contains(normalize-space(.),'Fast Example')]
    ${title}=    Get Value    css:input[name="title"]
    Should Not Be Empty    ${title}
    Page Should Contain    Live preview
    Page Should Contain    Filled in:

Own Recipe Validation Requires Ingredient
    Go To App Page    /recipes/own/create
    Wait Until Page Contains    Own recipes
    Input Text    css:input[name="title"]    Robot Empty Recipe
    Click Button    xpath=//button[contains(normalize-space(.),'Clear ingredients')]
    Click Button    Save
    Handle Alert    ACCEPT
