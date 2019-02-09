var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#menu_app',
        data: function() {
            return {
                code_lst: [],
                path: {},
                menu_json: '',
            };
        },
        methods: {
            refresh_filter: function(after) {
                var that = this;
                axios
                    .get("./?load_filter=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.code_lst = data.lst;
                            if (after !== undefined){
                                after.call();
                            }
                        }else{
                            alert(data.message);
                        }
                    });
            },
            open_modal: function(id){
                vuev.$emit("modal:open:" + id);
            },
            close_modal: function(id){
                $("#" + id).removeClass("is-active");
            },
            parse_path: function() {
                this.path = EMPS.get_path_vars();
            },
            select_sk: function(sk) {
                if (sk.length === 0) {
                    sk = undefined;
                }
                this.parse_path();
                var path = Vue.util.extend({}, this.path);
                path.sk = sk;
                path.key = undefined;
                path.sd = undefined;
                var link = EMPS.link(path);
                EMPS.soft_navi(vted_title, link);
                vuev.$emit("navigate");
                this.parse_path();
            },
            export_menu_load: function(after) {
                var that = this;

                axios
                    .get("./?export_menu=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.menu_json = data.menu_json;
                            if (after !== undefined){
                                after.call();
                            }
                        }else{
                            alert(data.message);
                        }
                    });


            },
            export_menu: function() {
                var that = this;
                this.export_menu_load(function(){
                    that.open_modal('menuExportModal');
                });

            },
            import_menu: function() {
                this.menu_json = '';
                this.open_modal('menuExportModal');
            },
            submit_import: function() {
                var that = this;
                var row = {};
                row.post_import = 1;
                row.menu_json = this.menu_json;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            toastr.success(window.string_imported);
                            vuev.$emit("navigate");
                        }
                    });
                this.close_modal("menuExportModal");
            }

        },
        mounted: function(){
            $("#menu_app").show();
            $(".app-loading").hide();
            vuev.$on("vted:load_list", this.refresh_filter);
            vuev.$on("vted:navigate", this.parse_path);
            this.refresh_filter();
            this.parse_path();
        }
    });


});
