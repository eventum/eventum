<!--
// @(#) $Id: s.post_load.js 1.3 03/05/07 13:14:26-00:00 jpm $

load_handlers = new Array();
if (window.onload) {
    load_handlers[0] = window.onload;
}

function runLoadHandlers()
{
    for (var i = 0; i < load_handlers.length; i++) {
        load_handlers[i]();
    }
}
window.onload = runLoadHandlers;

//-->