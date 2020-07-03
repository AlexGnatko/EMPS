
var vuev = new Vue();

var app;

emps_scripts.push(function(){
    app = new Vue({
        el: '#context_photos_app',
        mounted: function(){
            $("#context_photos_app").show();
            $(".app-loading").hide();
        }
    });
});
