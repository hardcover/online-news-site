window.onresize = function() {
    //
    // Return control to CSS by window width
    //
    var x = document.getElementById("aside");
    if (window.innerWidth > 600) {
        x.style = null;
    }

};

function cellMenu() {
    //
    // Toggle the cell phone menu button styles
    //
    var x = document.getElementById("aside");
    if (x.style.display === "inline") {
        x.style.display = "none";
    } else {
        x.style.display = "inline";
        x.style.position = "absolute";
        x.style.width = "61.8%";
        x.style.background = "#f9f9f9";
        x.style.paddingRight = "1rem";
        x.style.paddingBottom = "1rem";
        x.style.paddingLeft = "1rem";
        x.style.borderStyle = "solid";
        x.style.borderWidth = "1px";
        x.style.borderColor = "#aaa";
    }
}

document.onsubmit = function() {
    //
    // Wait cursor after submitting a form
    //
    window.scrollTo({ behavior: "smooth", top: 0 });
    document.body.style.cursor = "wait";
};
