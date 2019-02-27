var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#e_users_app',
        mounted: function(){
            $("#e_users_app").show();
            $(".app-loading").hide();
        },
        methods: {
        }
    });
});
