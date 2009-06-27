// Author: Matt Kruse <matt@mattkruse.com>
// WWW: http://www.mattkruse.com/
//
// Modified by João Prado Maia <jpm@mysql.com>

function autoComplete(field, options)
{
    var found = false;
    for (var i = 0; i < options.length; i++) {
        if (options[i].toUpperCase().indexOf(field.value.toUpperCase()) == 0) {
            found = true;
            break;
        }
    }
    if (field.createTextRange) {
        var cursorKeys = "8;46;37;38;39;40;33;34;35;36;45;";
        if (cursorKeys.indexOf(event.keyCode+";") == -1) {
            var r1 = field.createTextRange();
            var oldValue = r1.text;
            var newValue = found ? options[i] : oldValue;
            if (newValue != field.value) {
                field.value = newValue;
                var rNew = field.createTextRange();
                rNew.moveStart('character', oldValue.length) ;
                rNew.select();
            }
        }
    }
}

