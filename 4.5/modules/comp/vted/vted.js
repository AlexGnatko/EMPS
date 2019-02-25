(function() {

    Vue.component('vted', {
        data: function(){
            return {
                list_mode: true,
                row: {},
                guid: guid(),
                selected_row: {},
                new_row: {},
                lst: [],
                pages: {},
                path: {},
                lookup_id: undefined,
                no_scroll: false,
                parents: [],
            }
        },
        components: {
            'editor': Editor // <- Important part
        },
        mounted: function(){
            this.parse_path();
            var that = this;
            window.onpopstate = function(event) {
                that.parse_path();
            };
            vuev.$on("navigate", this.parse_path);
        },
        methods: {
            insert_at_cursor: function(id, text) {
                tinmce.get(id).execCommand('mceInsertContent', false, text);
            },
            load_row: function(after) {
                if (this.path.key !== undefined) {
                    var that = this;
                    axios
                        .get("./?load_row=" + this.path.key)
                        .then(function(response){
                            var data = response.data;
                            if (data.code == 'OK') {
                                that.row = data.row;
                                if (that.path.ss !== undefined) {
                                    vuev.$emit("update_pad", that.path.ss);
                                }
                                if (after !== undefined){
                                    after.call();
                                }
                            }else{
                                that.open_modal("cantLoadRowModal");

                                that.navigate(that.back_link());
                            }
                        });
                }
            },
            load_list: function(after) {
                if (this.path.key === undefined) {
                    var that = this;
                    vuev.$emit("vted:load_list");
                    axios
                        .get("./?load_list=1")
                        .then(function(response){
                            var data = response.data;
                            if (data.code == 'OK') {
                                that.lst = data.lst;
                                that.pages = data.pages;
                                if (data.parents !== undefined) {
                                    that.parents = data.parents;
                                }
                                that.lookup_id = undefined;
                                if (after !== undefined){
                                    after.call();
                                }
                            }else{
                                alert(data.message);
                            }
                        });
                }
            },
            parse_path: function() {
                vuev.$emit("vted:navigate");
                this.path = EMPS.get_path_vars();
                var list_mode;
                if (this.path.ss !== undefined){
                    list_mode = false;
                } else {
                    list_mode = true;
                }

                var that = this;

                if (list_mode) {
                    this.load_list(function(){
                        that.list_mode = true;
                    });
                } else {
                    this.load_row(function(){
                        that.list_mode = false;
                        that.selected_row = Vue.util.extend({}, that.row);
                    });
                }
            },
            navigate: function(url, e) {
                if (e !== undefined) {
                    e.preventDefault();
                }

                $('a, button').blur();
                EMPS.soft_navi(vted_title, url);
                this.parse_path();
                if (!this.no_scroll) {
                    window.scrollTo(0, 0);
                }
                this.no_scroll = false;
                return false;
            },
            roll_to: function(page) {
                //alert(JSON.stringify(page));
                this.no_scroll = true;
                this.navigate(page.link);
            },
            open_modal: function(id){
                vuev.$emit("modal:open:" + id);
            },
            close_modal: function(id){
                $("#" + id).removeClass("is-active");
            },
            trigger: function(id) {
                vuev.$emit(id, this.guid);
//                vuev.$emit(id);
            },
            ask_delete: function(row) {
                this.selected_row = row;
                this.open_modal("deleteRowModal");
            },
            delete_selected_row: function() {
                var that = this;
                var row = {};
                row.post_delete = this.selected_row.id;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            toastr.error(window.string_deleted);
                            that.to_current_list();
                        }
                    });
                this.close_modal("deleteRowModal");
            },
            to_current_list: function() {
                if (this.path.key !== undefined && this.path.ss !== undefined) {
                    var path = Vue.util.extend({}, this.path);
                    path.ss = undefined;
                    path.key = undefined;
                    var link = EMPS.link(path);
                    this.navigate(link);
                } else {
                    this.load_list();
                }
            },
            is_active_pad: function(code) {
                if (this.path.ss == code) {
                    return true;
                }
                return false;
            },
            pad_link: function(code) {
                var path = Vue.util.extend({}, this.path);
                path.ss = code;
                var link = EMPS.link(path);
                return link;
            },
            submit_form: function(e) {
                e.preventDefault();

                var that = this;
                var row = {};
                row.post_save = 1;
                row.payload = this.selected_row;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            that.load_row();
                            $('form *').blur();
                            toastr.success(window.string_saved);
                        }
                    });

                return false;
            },
            submit_create: function() {
                var that = this;
                var row = {};
                row.post_new = 1;
                row.payload = this.new_row;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.load_list();
                            that.close_modal("createModal");
                            that.new_row = {};
                            $('form *').blur();
                            toastr.info(window.string_created);
                        } else {
                            alert(data.message);
                        }
                    });

                return false;
            },
            open_by_id: function(e) {
                e.preventDefault();
                var path = Vue.util.extend({}, this.path);
                var id = parseInt(this.lookup_id);
                if (id == 0) {
                    return false;
                }
                path.ss = 'info';
                path.key = this.lookup_id;
                path.sd = undefined;

                var link = EMPS.link(path);
                this.navigate(link);

                return false;
            },
            create_new: function() {
                this.open_modal("createModal");
            },
            back_link: function() {
                var v = EMPS.elink({}, ['key', 'ss']);
                return v;
            }
        },
    });

})();