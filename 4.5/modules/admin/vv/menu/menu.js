var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#menu_app',
        mounted: function(){
            $("#menu_app").show();
            $(".app-loading").hide();
        }
    });

});
