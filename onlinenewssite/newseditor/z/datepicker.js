$(document).ready(function () {
    //
    // Date picker
    //
    $.datepicker.setDefaults({
        changeMonth: true,
        changeYear: true,
        closeText: "X",
        dateFormat: "yy-mm-dd",
        showButtonPanel: "true",
        numberOfMonths: 2
    });

    $(function () {
        $(".datepicker").datepicker();
    });
    //
    // Please wait, message after submitting a form
    //
    var elems = document.getElementsByClassName("wait");
    var message = document.getElementById("waiting");
    if (elems.length > 0 && message !== null) {
        message.style.display = "none";
        var callback = function () {
            message.style.display = "";
            window.top.scroll(0, 0);
        };
        for (var i = 0; i < elems.length; i++) {
            elems[i].onsubmit = callback;
        }
    }
});
