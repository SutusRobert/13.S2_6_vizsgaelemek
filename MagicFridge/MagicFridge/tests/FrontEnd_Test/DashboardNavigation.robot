*** Settings ***
Resource    resources/MagicFridge.resource
Suite Setup    Open MagicFridge
Suite Teardown    Close MagicFridge
Test Setup    Log In With Test User
Test Teardown    Log Out If Logged In

*** Test Cases ***
Dashboard Shows Main Modules
    Page Should Contain    Recipes
    Page Should Contain    Messages
    Page Should Contain    Household
    Page Should Contain    Inventory
    Page Should Contain    Shopping list

Dashboard Opens Inventory
    Click Link    xpath=//a[.//div[normalize-space(.)='Inventory']]
    Wait Until Location Contains    /inventory
    Page Should Contain    Add a new item
    Page Should Contain Link    Open inventory

Dashboard Opens Shopping List
    Go To App Page    /dashboard
    Click Link    xpath=//a[.//div[normalize-space(.)='Shopping list']]
    Wait Until Location Contains    /shopping
    Page Should Contain    New item
    Page Should Contain    list

Dashboard Opens Household Page
    Go To App Page    /dashboard
    Click Link    xpath=//a[.//div[normalize-space(.)='Household']]
    Wait Until Location Contains    /households
    Page Should Contain    Invite member
    Page Should Contain    Members

Dashboard Opens Messages Page
    Go To App Page    /dashboard
    Click Link    xpath=//a[.//div[normalize-space(.)='Messages']]
    Wait Until Location Contains    /messages
    Page Should Contain    Messages
