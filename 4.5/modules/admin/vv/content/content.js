var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#content_app',
        data: function() {
            return {
                content_json: '',
            };
        },
        mounted: function(){
            $("#content_app").show();
            $(".app-loading").hide();
        },
        methods: {
            open_modal: function(id){
                vuev.$emit("modal:open:" + id);
            },
            close_modal: function(id){
                $("#" + id).removeClass("is-active");
            },
            open_import: function() {
                this.content_json = '';
                this.open_modal("importExportModal");
            },
            submit_import: function() {
                var that = this;
                var row = {};
                row.post_import = 1;
                row.content_json = this.content_json;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            toastr.success(window.string_imported);
                            vuev.$emit("navigate");
                        }
                    });
                this.close_modal("importExportModal");
            }
        }
    });

});
