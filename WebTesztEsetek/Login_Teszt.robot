*** Settings ***
Library           SeleniumLibrary

*** Test Cases ***
Login sikeres
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa2
    Click Button    xpath=//button[@type="submit"]
    Sleep    3s
    Close Browser

Login sikertelen
    Open Browser    http://127.0.0.1:8000/login    chrome
    Maximize Browser Window
    Input Text    name=email    aaa2@gmail.com
    Input Text    name=password    aaa
    Click Button    xpath=//button[@type="submit"]
    Sleep    3s
    Close Browser
