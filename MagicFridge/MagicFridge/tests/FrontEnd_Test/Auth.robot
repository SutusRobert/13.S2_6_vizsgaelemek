*** Settings ***
Resource    resources/MagicFridge.resource
Suite Setup    Open MagicFridge
Suite Teardown    Close MagicFridge
Test Teardown    Log Out If Logged In

*** Test Cases ***
Login Page Is Available
    Go To App Page    /login
    Wait Until Page Contains    Login
    Page Should Contain Element    css:input[name="email"]
    Page Should Contain Element    css:input[name="password"]
    Page Should Contain Button    Login

Register Page Is Available
    Go To App Page    /register
    Wait Until Page Contains    Registration
    Page Should Contain Element    css:input[name="full_name"]
    Page Should Contain Element    css:input[name="email"]
    Page Should Contain Element    css:input[name="password"]
    Page Should Contain Element    css:input[name="password_confirmation"]
    Page Should Contain Button    Register

Guest Opening Home Is Sent To Login
    Go To App Page    /
    Wait Until Location Contains    /login
    Page Should Contain    Login

Invalid Login Shows Error Message
    Go To App Page    /login
    Input Text    css:input[name="email"]    nobody@example.test
    Input Password    css:input[name="password"]    wrong-password
    Click Button    Login
    Wait Until Page Contains    Invalid e-mail or password.

Verified User Can Log In And Log Out
    Log In With Test User
    Page Should Contain    Choose a module
    Click Button    Log out
    Wait Until Location Contains    /login
    Page Should Contain    Login
