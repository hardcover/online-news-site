$(document).ready(function () {
    "use strict";
    $.datepicker.setDefaults({
        dateFormat: 'yy-mm-dd',
        numberOfMonths: 2
    });

    $(function () {
        $(".datepicker").datepicker();
    });
});
