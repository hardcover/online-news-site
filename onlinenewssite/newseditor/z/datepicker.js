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
    // Wait cursor after submitting a form
    //
    document.onsubmit = function() {
        window.scrollTo({ behavior: "smooth", top: 0 });
        document.body.style.cursor = "wait";
    };
});
