*** Settings ***
Library    SeleniumLibrary
Test Teardown    Close All Browsers


*** Test Cases ***

01 - Receptek megnyitasa majd vissza
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Log To Console    >>> Bejelentkezes...
    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Receptek megnyitasa...
    Scroll Element Into View    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Receptek"]]
    Sleep    1s
    Click Element    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Receptek"]]
    Sleep    5s
    Capture Page Screenshot

    Log To Console    >>> Vissza Dashboardra...
    Scroll Element Into View    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    1s
    Click Element    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    5s


02 - Uzenetek megnyitasa majd vissza
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s

    Log To Console    >>> Uzenetek megnyitasa...
    Scroll Element Into View    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Üzenetek"]]
    Sleep    1s
    Click Element    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Üzenetek"]]
    Sleep    5s

    Scroll Element Into View    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    1s
    Click Element    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    5s


03 - Haztartas megnyitasa majd vissza
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s

    Log To Console    >>> Haztartas megnyitasa...
    Scroll Element Into View    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Háztartás"]]
    Sleep    1s
    Click Element    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Háztartás"]]
    Sleep    5s

    Scroll Element Into View    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    1s
    Click Element    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    5s


04 - Raktar megnyitasa majd vissza
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s

    Log To Console    >>> Raktar megnyitasa...
    Scroll Element Into View    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Raktár"]]
    Sleep    1s
    Click Element    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Raktár"]]
    Sleep    5s

    Scroll Element Into View    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    1s
    Click Element    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    5s


05 - Bevasarlolista megnyitasa majd vissza
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s

    Log To Console    >>> Bevasarlolista megnyitasa...
    Scroll Element Into View    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Bevásárlólista"]]
    Sleep    1s
    Click Element    xpath=//a[contains(@class,"menu-tile")][.//div[normalize-space(.)="Bevásárlólista"]]
    Sleep    5s

    Scroll Element Into View    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    1s
    Click Element    xpath=//a[contains(@href,"/dashboard") and normalize-space(.)="MagicFridge"]
    Sleep    5s


06 - Kijelentkezes
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Sleep    3s

    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    5s

    Log To Console    >>> Kijelentkezes...
    Click Button    xpath=//button[@type="submit" and normalize-space(.)="Kijelentkezés"]
    Sleep    5s

