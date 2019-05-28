(function() {

Vue.component('props-editor', {
    data: function(){
        return {
            lst: [],
            guid: guid(),
            edit_mode: false,
            current_row: {},
            import_text: '',
            types: [
                {'code': 't', 'name': 'Text'},
                {'code': 'c', 'name': 'Varchar(255)'},
                {'code': 'i', 'name': 'Integer'},
                {'code': 'f', 'name': 'Float'},
                {'code': 'd', 'name': 'Data'},
                {'code': 'b', 'name': 'Boolean'},
            ]
        };
    },
    methods: {
        load_data: function(after){
            var that = this;
            axios
                .get("./?load_settings=1")
                .then(function(response){
                    var data = response.data;
                    if (data.code == 'OK') {
                        that.lst = data.lst;
                        if (after !== undefined){
                            after.call();
                        }
                    }else{
                        alert(data.message);
                    }
                });
        },
        edit_prop: function(row){
            this.current_row = Vue.util.extend({}, row);
            this.edit_mode = true;
            this.open_modal("editModal");
        },
        ask_delete: function(){
            this.open_modal("deleteModal");
        },
        delete_props: function(){
            that = this;
            var id_list = [];
            this.lst.forEach(function(v, k){
                if(v.checked){
                    id_list.push(v.id);
                }
            });
            var row = {};
            row.delete_settings_rows = 1;
            row.id_list = id_list;
            axios
                .post("./", row)
                .then(function(response){
                    var data = response.data;
                    if(data.code == 'OK'){
                        that.close_modal("deleteModal");
                        that.load_data();
                    }
                });
        },
        add_prop: function(){
            this.edit_mode = false;
            this.current_row = {type: 't'};
            this.open_modal("editModal");
        },
        submit_changes: function(){
            var that = this;
            var row = {};
            row.post_save_changes_settings = 1;
            row.id = this.current_row.id;
            row.row = this.current_row;
            axios
                .post("./", row)
                .then(function(response){
                    var data = response.data;
                    if(data.code == 'OK'){
                        $("#editModal").removeClass("is-active");
                        that.load_data();
                    }
                });
        },
        type_name: function(code){
            var l = this.types.length;
            for(var i = 0; i < l; i++){
                if(this.types[i].code == code){
                    return this.types[i].name;
                }
            }
            return "";
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
        open_modal: function(id){
            vuev.$emit("modal:open:" + id);
        },
        close_modal: function(id){
            $("#" + id).removeClass("is-active");
        },
        export_selected: function(mode){
            var list = [];
            var l = this.lst.length;
            for(var i = 0; i < l; i++){
                var c = this.lst[i];
                if(!c.checked){
                    continue;
                }
                var a = {};
                a.code = c.code;
                a.type = c.type;
                a.value = c.value;
                list.push(a);
            }
            if(mode == 'json'){
                this.import_text = JSON.stringify(list);
            }else{
                var t = "";
                l = list.length;
                for(var i = 0; i < l; i++){
                    var a = list[i];
                    t += a.code + "=" + a.value + "\r\n";
                }
                this.import_text = t;
            }
            this.open_modal("exportModal");
        },
        do_import: function() {
            var that = this;
            var row = {};
            row.post_import = 1;
            row.import_json = this.import_text;
            axios
                .post("./", row)
                .then(function(response){
                    var data = response.data;
                    if(data.code == 'OK'){
                        $("#exportModal").removeClass("is-active");
                        that.load_data();
                    }
                });
        }
    },
    mounted: function(){
        this.load_data();
    }
});

})();