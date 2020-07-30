var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#content_app',
        data: function() {
            return {};
        },
        mounted: function(){
            $("#content_app").show();
            $(".app-loading").hide();
        },
        methods: {
        }
    });

});


var EMPS_V_content = {
    data: function() {
        return {
            content_json: '',
        };
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
        select_inverse: function(){
            var l = this.lst.length;
            for(var i = 0; i < l; i++){
                if(this.lst[i].checked){
                    this.lst[i].checked = false;
                }else{
                    this.lst[i].checked = true;
                }
            }
        },
        export_selected: function () {
            var that = this;
            var sel = [];
            var l = this.lst.length;
            for (var i = 0; i < l; i++) {
                if (this.lst[i].checked) {
                    sel.push(this.lst[i].id);
                }
            }
            var row = {};
            row.post_export = 1;
            row.sel = sel;
            axios
                .post("./", row)
                .then(function(response){
                    var data = response.data;

                    if(data.code == 'OK'){
                        that.content_json = data.json;
                        that.open_modal("importExportModal");
                    }
                });
        },
        do_export: function() {

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
    },
};

if (window.EMPS_vted_mixins === undefined) {
    window.EMPS_vted_mixins = [];
}

window.EMPS_vted_mixins.push(EMPS_V_content);