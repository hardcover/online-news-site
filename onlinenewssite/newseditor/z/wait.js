window.onload = function () {
    'use strict';
    //
    // Please wait, message after submitting a form
    //
    var elems = document.getElementsByClassName('wait');
    var message = document.getElementById('waiting');
    if (elems.length > 0 && message !== null) {
        message.style.display = 'none';
        var callback = function () {
            message.style.display = '';
            window.top.scroll(0, 0);
        };
        for (var i = 0; i < elems.length; i++) {
            elems[i].onsubmit = callback;
        }
    }
};