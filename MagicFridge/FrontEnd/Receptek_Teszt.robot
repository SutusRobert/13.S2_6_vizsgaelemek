*** Settings ***
Library    SeleniumLibrary
Test Teardown    Close All Browsers

*** Test Cases ***
01 - Receptek / Saját recept / Gyors minta / Mentés / Mégse majd vissza dashboardra
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Log To Console    >>> Bejelentkezes...
    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Receptek megnyitasa (dashboard)...
    Scroll Element Into View    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Receptek"]]
    Sleep    1s
    Click Element    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Receptek"]]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Saját recept oldal megnyitasa...
    Scroll Element Into View    xpath=//a[normalize-space(.)="Saját recept"]
    Sleep    1s
    Click Element    xpath=//a[normalize-space(.)="Saját recept"]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Gyors minta generalas...
    Scroll Element Into View    xpath=//button[normalize-space(.)="Gyors minta"]
    Sleep    1s
    Click Button    xpath=//button[normalize-space(.)="Gyors minta"]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Mentés gomb...
    Scroll Element Into View    xpath=//button[normalize-space(.)="Mentés"]
    Sleep    1s
    Click Button    xpath=//button[normalize-space(.)="Mentés"]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Mégse (vissza a receptekhez)...
    Scroll Element Into View    xpath=//a[normalize-space(.)="Mégse"]
    Sleep    1s
    Click Element    xpath=//a[normalize-space(.)="Mégse"]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Vissza dashboardra (MagicFridge bal fent)...
    Scroll Element Into View    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    1s
    Click Element    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    5s
    Capture Page Screenshot