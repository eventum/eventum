var expanding = false;

// expands the cell specified by ecID and msgID. This will initiate the call to the remote script to get the
// content using our HTTPClient. Since this call can take time, a "loading.." message will be displayed
// temporarily in the cell.
function expand(baseURL, ecID, listID)
{
    var row = getRow(ecID, listID);
    var cell = getCell(ecID, listID);
    
    if ((cell.innerHTML == "") || (cell.innerHTML == 'loading...')) {
        cell.innerHTML = "loading...";
        var httpClient = new HTTPClient();
        httpClient.loadRemoteContent(baseURL + 'get_remote_data.php?action=' + getRemoteFunction(ecID) + '&ec_id=' + ecID + '&list_id=' + listID, 'handleCallback');
    }
    row.style.display = getDisplayStyle();
}

// hides the current cell. The data is not lost so if the cell is expanded in the future, the content will not be reloaded.
function collapse(ecID, listID)
{
    getRow(ecID, listID).style.display = "none";
}

function handleCallback(response)
{
    var message = response.responseText;
    // parse listID and ecID out of text.
    var ecID = message.substr(0, message.indexOf(":"));
    message = message.substr(message.indexOf(":") + 1, message.length);
    var listID = message.substr(0, message.indexOf(":"));
    message = message.substr(message.indexOf(":") + 1, message.length);
    expandCell(ecID, listID, message);
}

function expandCell(ecID, listID, txt)
{
    var currentDiv = getCell(ecID, listID);
    currentDiv.innerHTML = txt;
    currentDiv.style.display = getDisplayStyle();
}

function expandAll(baseURL, ecID)
{
    var cells = getAllCells(ecID);
    for (i = 0; i < cells.length; i++) {
        id = cells[i].id;
        chunks = id.split("_");
        expand(baseURL, ecID, chunks[3]);
    }
}

function collapseAll(ecID)
{
    var cells = getAllCells(ecID);
    for (i = 0; i < cells.length; i++) {
        id = cells[i].id;
        chunks = id.split("_");
        collapse(ecID, chunks[3]);
    }
}

function setRemoteFunction(ecID, url)
{
    self['ec_remote_func_' + ecID] = url;
}

function getRemoteFunction(ecID)
{
    return self['ec_remote_func_' + ecID];
}

// returns the row for the specified ecID and listID
function getRow(ecID, listID) 
{
    return document.getElementById("ec_" + ecID + "_item_" + listID + "_row");
}

// returns the cell for the specified ecID and listID
function getCell(ecID, listID) 
{
    return document.getElementById("ec_" + ecID + "_item_" + listID + "_cell");
}

// returns an array of all cells that are part of the specified expandable table.
function getAllCells(ecID)
{
    var cells = document.body.getElementsByTagName("TD");
    var newCells = new Array();
    for (i = 0; i < cells.length; i++) {
        if ((cells[i].id != '') && (cells[i].id.substring(0,("ec_" + ecID).length) == ('ec_' + ecID))) {
            newCells.length++;
            newCells[newCells.length-1] = cells[i];
        }
    }
    return newCells;
}
