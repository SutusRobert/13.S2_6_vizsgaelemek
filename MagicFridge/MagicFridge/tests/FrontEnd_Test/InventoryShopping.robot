*** Settings ***
Resource    resources/MagicFridge.resource
Suite Setup    Open MagicFridge
Suite Teardown    Close MagicFridge
Test Setup    Log In With Test User
Test Teardown    Log Out If Logged In

*** Test Cases ***
User Can Add And Find Inventory Item
    ${stamp}=    Get Time    epoch
    ${item}=    Set Variable    Robot Milk ${stamp}
    Go To App Page    /inventory
    Wait Until Page Contains Element    css:input[name="name"]
    Input Text    css:input[name="name"]    ${item}
    Input Text    css:input[name="category"]    Dairy
    Select From List By Value    css:select[name="location"]    fridge
    Input Text    css:input[name="quantity"]    2
    Input Text    css:input[name="unit"]    l
    Click Button    Add
    Wait Until Location Contains    /inventory/list
    Wait Until Page Contains    ${item}
    Input Text    css:input[name="q"]    ${item}
    Click Button    Filter
    Wait Until Page Contains    ${item}

User Can Update Inventory Item Quantity
    ${stamp}=    Get Time    epoch
    ${item}=    Set Variable    Robot Rice ${stamp}
    Go To App Page    /inventory
    Input Text    css:input[name="name"]    ${item}
    Select From List By Value    css:select[name="location"]    pantry
    Input Text    css:input[name="quantity"]    1
    Input Text    css:input[name="unit"]    kg
    Click Button    Add
    Wait Until Page Contains    ${item}
    ${row}=    Set Variable    xpath=//tr[.//strong[normalize-space(.)='${item}']]
    Input Text    ${row}//input[@name='quantity']    3
    Click Button    ${row}//button[normalize-space(.)='Save']
    Wait Until Page Contains    ${item}
    Page Should Contain    3

User Can Add Shopping List Item And Mark It Bought
    ${stamp}=    Get Time    epoch
    ${item}=    Set Variable    Robot Bread ${stamp}
    Go To App Page    /shopping
    Wait Until Page Contains    New item
    Input Text    css:input[name="name"]    ${item}
    Input Text    css:input[name="quantity"]    1
    Input Text    css:input[name="unit"]    pcs
    Select From List By Value    css:select[name="location"]    pantry
    Click Button    Add
    Wait Until Page Contains    ${item}
    Click Button    xpath=//div[contains(@class,'sl-item')][.//*[contains(normalize-space(.),'${item}')]]//button[normalize-space(.)='Bought']
    Wait Until Page Contains    Back

User Can Delete Shopping List Item
    ${stamp}=    Get Time    epoch
    ${item}=    Set Variable    Robot Delete ${stamp}
    Go To App Page    /shopping
    Input Text    css:input[name="name"]    ${item}
    Click Button    Add
    Wait Until Page Contains    ${item}
    Click Button    xpath=//div[contains(@class,'sl-item')][.//*[contains(normalize-space(.),'${item}')]]//button[normalize-space(.)='Delete']
    Accept Alert If Present
    Wait Until Page Does Not Contain    ${item}
