*** Settings ***
Library           SeleniumLibrary

*** Test Cases ***
Regisztracio sikeres
    Open Browser    http://127.0.0.1:8000/register    chrome
    Maximize Browser Window
    Input Text    name=full_name    Teszt Elek
    Input Text    name=email    teszt@email.com
    Input Password    xpath=(//input[@type="password"])[1]    12345678
    Input Password    xpath=(//input[@type="password"])[2]    12345678
    Click Button    xpath=//button[@type="submit"]
    Sleep    3s
    Close Browser

Regisztracio sikertelen
    open Browser    http://127.0.0.1:8000/register    chrome
    Maximize Browser Window
    Input Text    name=full_name    Teszt Elek
    Input Text    name=email    teszt@email.com
    Input Password    xpath=(//input[@type="password"])[1]    12345678
    Input Password    xpath=(//input[@type="password"])[2]    12345677
    Click Button    xpath=//button[@type="submit"]
    Sleep    3s
    Close Browser
