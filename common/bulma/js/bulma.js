emps_scripts.push(function(){
    $(".navbar-burger").click(function() {
        var id = $(this).data('target');
        console.log(id);
        $("#" + id).toggleClass("is-active");
        $(this).toggleClass("is-active");
    });

});