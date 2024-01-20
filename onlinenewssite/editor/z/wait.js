window.onload = function () {
    //
    // Wait cursor after submitting a form
    //
    document.onsubmit = function() {
        window.scrollTo({ behavior: "smooth", top: 0 });
        document.body.style.cursor = "wait";
    };
};
