*** Settings ***
Library    SeleniumLibrary

*** Variables ***
${BASE_URL}          http://127.0.0.1:8000
${BROWSER}           Chrome
${TEST_EMAIL}        %{MAGICFRIDGE_TEST_EMAIL=sutusrobert910@gmail.com}
${TEST_PASSWORD}     %{MAGICFRIDGE_TEST_PASSWORD=fridge}
${SELENIUM_SPEED}    0s

*** Keywords ***
Open MagicFridge
    Open Browser    ${BASE_URL}    ${BROWSER}
    Maximize Browser Window
    Set Selenium Speed    ${SELENIUM_SPEED}
    Set Selenium Timeout    10s

Close MagicFridge
    Close Browser

Require Test User
    Skip If    '${TEST_EMAIL}' == '' or '${TEST_PASSWORD}' == ''    Protected frontend tests need TEST_EMAIL and TEST_PASSWORD variables.

Log In With Test User
    Require Test User
    Go To    ${BASE_URL}/login
    Wait Until Page Contains Element    css:input[name="email"]
    Input Text    css:input[name="email"]    ${TEST_EMAIL}
    Input Password    css:input[name="password"]    ${TEST_PASSWORD}
    Click Button    Login
    Wait Until Location Contains    /dashboard
    Wait Until Page Contains    Choose a module

Log Out If Logged In
    ${present}=    Run Keyword And Return Status    Page Should Contain Button    Log out
    IF    ${present}
        Click Button    Log out
        Wait Until Location Contains    /login
    END

Go To App Page
    [Arguments]    ${path}
    Go To    ${BASE_URL}${path}

Accept Alert If Present
    ${status}=    Run Keyword And Return Status    Handle Alert    ACCEPT    timeout=2s
    RETURN    ${status}
