/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
/**
 * Expands the cell specified by ecID and msgID.
 * This will initiate the call to the remote script to get the content using
 * jQuery.
 * Since this call can take time, a "loading.." message will be displayed
 * temporarily in the cell.
 */
function expand(baseURL, ecID, listID)
{
    var $row = $getRow(ecID, listID);
    var $cell = $getCell(ecID, listID);
    var val = $cell.html(), url;

    // TODO: translate
    if (val == "" || val == 'loading...') {
        $cell.html("loading...");
        url = baseURL + 'get_remote_data.php?action=' + getRemoteFunction(ecID) + '&ec_id=' + ecID + '&list_id=' + listID;
        $.getJSON(url + '&callback=?', function(data) {
            // data.ecID, data.listID, data.message;
            if (data.error) {
                // TODO: translate
                $cell.html('Error: ' + data.error);
                return;
            }

            // it locks up slower browsers, display warning for users
            var len = data.message.length;
            if (len > 10240) {
                // TODO: translate
                $cell.html("Loading " + len + " bytes of data, please wait...");
                // have some time to see the message
                setTimeout(function() {
                    $cell.html(data.message);
                }, 10);
            } else {
                $cell.html(data.message);
            }
        });
    }
    $row.show();
}

// hides the current cell. The data is not lost so if the cell is expanded in the future, the content will not be reloaded.
function collapse(ecID, listID)
{
    $getRow(ecID, listID).hide()
}

function expandAll(baseURL, ecID)
{
    var cells = getAllCells(ecID), i, id, chunks;
    for (i = 0; i < cells.length; i++) {
        id = cells[i].id;
        chunks = id.split("_");
        expand(baseURL, ecID, chunks[3]);
    }
}

function collapseAll(ecID)
{
    var cells = getAllCells(ecID), i, id, chunks;
    for (i = 0; i < cells.length; i++) {
        id = cells[i].id;
        chunks = id.split("_");
        collapse(ecID, chunks[3]);
    }
}

// setRemoteFunction('email', 'getEmail')
// setRemoteFunction('note', 'getNote')
// setRemoteFunction('phone', 'getPhoneSupport')
// setRemoteFunction('draft', 'getDraft')
// TODO: discard this mapping, change backend call params
function setRemoteFunction(ecID, url)
{
    self['ec_remote_func_' + ecID] = url;
}

// TODO: discard this mapping along with setRemoteFunction
function getRemoteFunction(ecID)
{
    return self['ec_remote_func_' + ecID];
}

// returns the row for the specified ecID and listID
function $getRow(ecID, listID)
{
    return $("#ec_" + ecID + "_item_" + listID + "_row");
}

// returns the cell for the specified ecID and listID
function $getCell(ecID, listID)
{
    return $("#ec_" + ecID + "_item_" + listID + "_cell");
}

// returns an array of all cells that are part of the specified expandable table.
// jQuery filter to find all find TD with id="ec_<ecID>_item_<number>" rows
function getAllCells(ecID)
{
    var match = new RegExp("^ec_" + ecID + "_item_\\d+");
    var cells = $('td').filter(function() {
        var id = $(this).attr("id");
        return id && id.match(match);
    });
    return cells;
}
