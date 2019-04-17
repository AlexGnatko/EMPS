emps_scripts.push(function(){
    var elist = document.getElementsByClassName("captcha-pic");
    var l = elist.length;
    for (var i = 0; i < l; i++) {
        var e = elist[i];
        var url = e.getAttribute("data-src");

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.onload = function(request) {
            var response = request.currentTarget.response || request.target.responseText;
            e.setAttribute("src", response);
        };
        xhr.send();
    }
});