var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#content_app',
        mounted: function(){
            $("#content_app").show();
            $(".app-loading").hide();
        }
    });

});
