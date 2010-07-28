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
