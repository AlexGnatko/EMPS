
(function() {

    if (window.EMPS_vted_mixins === undefined) {
        window.EMPS_vted_mixins = [];
    }

    Vue.component('vted', {
        data: function(){
            return {
                list_mode: true,
                struct_row: false,
                row: {},
                guid: guid(),
                selected_row: {},
                new_row: {},
                sort: {},
                lst: [],
                pages: {},
                path: {},
                filter: {},
                search_text: '',
                list_url_prefix: './',
                url_prefix: './',
                tree_url_prefix: './',
                lookup_id: undefined,
                no_scroll: false,
                parents: [],
                tree: [],
                clipboard: {},
                need_new_tree: true,
                show_tree: true,
                mhtml_vars: [{name: "HTML", var: "html"}],
                emps_tinymce_settings: Vue.util.extend(window.emps_tinymce_settings, {}),
            }
        },
        components: {
            'editor': Editor // <- Important part
        },
        mixins: window.EMPS_vted_mixins,
        mounted: function(){
            this.parse_path();
            var that = this;
            window.onpopstate = function(event) {
                that.parse_path();
            };
            vuev.$on("navigate", this.parse_path);

            if (localStorage.getItem("show_tree") === "true") {
                this.show_tree = true;
            } else {
                this.show_tree = false;
                this.$forceUpdate();
            }
        },
        methods: {
            insert_at_cursor: function(id, text) {
                tinmce.get(id).execCommand('mceInsertContent', false, text);
            },
            load_row: function(after) {
                if (this.path.key !== undefined) {
                    var that = this;
                    axios
                        .get(this.url_prefix + "?load_row=" + this.path.key)
                        .then(function(response){
                            var data = response.data;
                            if (data.code == 'OK') {
                                that.row = data.row;
                                that.selected_row = data.row;
                                if (that.path.ss !== undefined) {
                                    vuev.$emit("update_pad", that.path.ss);
                                }
                                that.$forceUpdate();
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
            collect_oa: function(oa, tree) {
                if (tree === undefined) {
                    return;
                }
                var l = tree.length;
                for (var i = 0; i < l; i++) {
                    this.collect_oa(oa, tree[i].subs);
                    if (tree[i].active) {
                        oa.active.push(tree[i].id);
                    }
                    if (tree[i].is_open) {
                        oa.open.push(tree[i].id);
                    }
                }
            },
            set_oa: function(oa, tree) {
                if (tree === undefined) {
                    return;
                }
                var l = tree.length;
                var had_active = false;
                for (var i = 0; i < l; i++) {
                    var rv = this.set_oa(oa, tree[i].subs);
                    if (oa.active.find(function(v) {
                            return tree[i].id == v;
                        }) !== undefined) {
                        tree[i].active = true;
                        had_active = true;
                    } else {
                        tree[i].active = false;
                    }
                    if (oa.open.find(function(v) {
                            return tree[i].id == v;
                        }) !== undefined) {
                        tree[i].is_open = true;
                        had_active = true;
                    } else {
                        tree[i].is_open = false;
                    }
                    if (rv) {
                        had_active = true;
                        tree[i].is_open = true;
                    }
                }
                return had_active;
            },
            update_tree: function(old_tree, new_tree) {
                var oa = {open: [], active: []};
                this.collect_oa(oa, old_tree);
                if (oa.active.length == 0) {
                    if (this.path.sd) {
                        oa.active.push(this.path.sd);
                    }
                }
                this.set_oa(oa, new_tree);
                this.tree = new_tree;
            },
            load_tree: function(parent_id) {
                if (!this.need_new_tree) {
                    if (this.tree.length > 0) {
                        return;
                    }
                }
                var that = this;

                axios
                    .get(this.url_prefix + "?load_tree=1&parent_id=" + parent_id)
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.update_tree(that.tree, data.tree);

                        }else{
                            toastr.error(data.message);
                        }
                    });
            },
            refresh_tree: function() {
                this.need_new_tree = true;
                this.load_tree(0);
            },
            load_list: function(after) {
                if (this.path.key === undefined) {
                    var that = this;
                    vuev.$emit("vted:load_list");
                    if (this.has_tree) {
                        this.load_tree(0);
                    }
                    axios
                        .get(this.url_prefix + "?load_list=1")
                        .then(function(response){
                            var data = response.data;
                            if (data.code == 'OK') {
                                that.lst = data.lst;
                                if (data.pages.total > 0 && data.lst.length == 0) {
                                    that.roll_to(data.pages.first);
                                }
                                that.pages = data.pages;
                                that.clipboard = data.clipboard;
                                that.search_text = data.search_text;
                                if (!data.filter) {
                                    that.filter = {};
                                } else {
                                    that.filter = data.filter;
                                }
                                if (data.parents !== undefined) {
                                    that.parents = data.parents;
                                }
                                if (data.sort !== undefined) {
                                    that.sort = data.sort;
                                }
                                that.lookup_id = undefined;
                                if (after !== undefined){
                                    after.call();
                                }
                            }else{
                                toastr.error(data.message);
                            }
                        });
                }
            },
            has_filter: function() {
                if (Object.keys(this.filter).length == 0) {
                    return false;
                }
                return true;
            },
            parse_path: function() {
//                alert("parse path");
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
                    this.need_new_tree = true;
                    this.load_row(function(){
                        that.list_mode = false;
                        var s = that.path.key.split('-');
                        if (s[0] == 'struct') {
                            that.struct_row = true;
                        } else {
                            that.struct_row = false;
                        }
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
                if (page === undefined) {
                    return;
                }
                if (page.link === undefined) {
                    return;
                }
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
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            toastr.success(window.string_deleted);
                            that.to_current_list();
                        } else {
                            toastr.error(data.message);
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
                if (e !== undefined) {
                    e.preventDefault();
                }

                var that = this;
                var row = {};
                row.post_save = 1;
                row.payload = this.selected_row;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            that.load_row();
                            vuev.$emit("form_submitted");
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
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.load_list();
                            that.close_modal("createModal");
                            that.new_row = {};
                            $('form *').blur();
                            toastr.info(window.string_created);
                            if (data.open_new) {
                                that.navigate(data.new_url);
                            }
                        } else {
                            toastr.error(data.message);
                        }
                    });

                return false;
            },
            post_set_filter: function() {
                var that = this;
                var row = {};
                row.post_filter = 1;
                row.payload = this.filter;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.load_list();
                            that.close_modal("vtedFilterModal");
                        } else {
                            toastr.error(data.message);
                        }
                    });

                return false;
            },
            sorting: function(field) {
                var new_sort = {};
                if (this.sort[field] === undefined) {
                    new_sort[field] = 1;
                } else {
                    new_sort[field] = this.sort[field] * -1;
                }
                this.sort = new_sort;
                var that = this;
                var row = {};
                row.post_sorting = 1;
                row.payload = this.sort;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.load_list();
                        } else {
                            toastr.error(data.message);
                        }
                    });

                return false;
            },
            clear_filter: function() {
                var that = this;
                var row = {};
                row.post_clear_filter = 1;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.load_list();
                            that.close_modal("vtedFilterModal");
                        } else {
                            toastr.error(data.message);
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
                var v = EMPS.elink(this.path, ['key', 'ss']);
                return v;
            },
            all_items_link: function() {
                var path = this.path;
                path.sd = 'all';
                var v = EMPS.elink(path, ['key', 'ss']);
                return v;
            },
            search: function(e) {
                if (e !== undefined) {
                    e.preventDefault();
                }
                $('form *').blur();

                var that = this;
                var row = {};
                row.post_search = 1;
                row.search_text = this.search_text;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.load_list();
                        } else {
                            toastr.error(data.message);
                        }
                    });
            },
            clear_search: function() {
                this.search_text = '';
                this.search();
            },
            open_filter: function() {
                vuev.$emit("modal:open:vtedFilterModal");
            },
            find_active_item: function(tree) {
                if (tree === undefined) {
                    return;
                }
                var l = tree.length;
                for (var i = 0; i < l; i++) {
                    var found = this.find_active_item(tree[i].subs);
                    if (found === undefined) {
                        if (tree[i].active) {
                            return tree[i];
                        }
                    } else {
                        return found;
                    }
                }
            },
            make_folder: function (item) {
                if (item === undefined) {
                    item = this.find_active_item(this.tree);
                    if (item !== undefined) {
                        this.make_folder(item);
                    } else {
                        this.add_item_to_list(undefined, this.tree);
                    }
                } else {
                    if (!item.subs ||
                        item.subs.length == 0) {
                        Vue.set(item, 'subs', []);
                    }
                    this.add_item(item);

                    Vue.set(item, 'is_open', true);
                }

            },
            delete_folder: function(item) {
                var that = this;
                var row = {};
                row.post_delete_folder = 1;
                row.id = item.id;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            that.unselect_all_folders();
                            that.delete_item(that.tree, item);
                        } else {
                            toastr.error(data.message);
                        }
                    });
            },
            edit_folder: function(item) {
                var path = Vue.util.extend({}, this.path);
                path.ss = "info";
                path.start = undefined;
                path.key = "struct-" + item.id;
                var link = EMPS.link(path);
                this.navigate(link);
            },
            add_item_to_list: function(parent, tree) {
                var row = {};
                row.post_create_folder = 1;
                if (parent !== undefined) {
                    row.parent_id = parent.id;
                }

                axios
                    .post(this.tree_url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if (data.code == 'OK') {
                            tree.push(data.row);
                        } else {
                            toastr.error(data.message);
                        }
                    });
            },
            add_item: function (item) {
                this.add_item_to_list(item, item.subs);
            },
            delete_item: function(tree, item) {
                if (tree === undefined) {
                    return;
                }
                if (item === undefined) {
                    return;
                }
                var l = tree.length;
                for (var i = 0; i < l; i++) {
                    this.delete_item(tree[i].subs, item);
                    if (tree[i] === item) {
                        tree.splice(i, 1);
                        break;
                    }
                }
            },
            set_inactive: function(tree) {
                if (tree === undefined) {
                    return;
                }
                var l = tree.length;
                for (var i = 0; i < l; i++) {
                    this.set_inactive(tree[i].subs);
                    tree[i].active = false;
                }
            },
            unselect_all_folders: function() {
                this.set_inactive(this.tree);
                var path = Vue.util.extend({}, this.path);
                path.ss = undefined;
                path.sd = undefined;
                path.key = undefined;
                var link = EMPS.link(path);
                this.navigate(link);

            },
            set_active: function(item) {
                this.need_new_tree = false;
                this.set_inactive(this.tree);
                Vue.set(item, 'active', true);
                var path = Vue.util.extend({}, this.path);
                path.ss = undefined;
                path.sd = item.id;
                path.key = undefined;
                var link = EMPS.link(path);
                this.navigate(link);
            },
            select_row: function(row) {
                Vue.set(row, 'selected', !row.selected);
            },
            select_all: function() {
                var lst = this.lst;

                var marked = false;

                var l = lst.length;
                var i = 0;
                var e = null;
                for (i = 0; i < l; i++) {
                    e = lst[i];
                    if (!e.selected) {
                        Vue.set(e, 'selected', true);
                        marked = true;
                    }
                }

                if (!marked) {
                    for (i = 0; i < l; i++) {
                        e = lst[i];
                        Vue.set(e, 'selected', false);
                    }
                }
                this.$forceUpdate();
            },
            copy_selected: function() {
                this.to_clipboard('copy');
            },
            cut_selected: function() {
                this.to_clipboard('cut');
            },
            to_clipboard: function(mode) {
                var lst = this.lst;
                var slst = [];

                var l = lst.length;
                var i = 0;
                var e = null;
                for (i = 0; i < l; i++) {
                    e = lst[i];
                    if (e.selected) {
                        slst.push({'item_id': e.id, 'struct_id': this.path.sd});
                    }
                }

                var that = this;
                var row = {};
                row.post_clipboard = mode;

                row.slst = slst;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            that.load_list();
                        }
                    });
            },
            paste: function() {
                var that = this;
                var row = {};
                row.post_paste = true;
                axios
                    .post(this.url_prefix, row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            that.load_list();
                        }
                    });
            },
            in_clipboard: function(row) {
                if (this.clipboard === undefined) {
                    return false;
                }
                if (this.clipboard === null) {
                    return false;
                }
                var cb = null;
                if (this.clipboard.copy !== undefined) {
                    cb = this.clipboard.copy;

                    var l = cb.length;
                    for (var i = 0; i < l; i++) {
                        if (cb[i].item_id == row.id) {
                            return "copy";
                        }
                    }
                }
                if (this.clipboard.cut !== undefined) {
                    cb = this.clipboard.cut;

                    var l = cb.length;
                    for (var i = 0; i < l; i++) {
                        if (cb[i].item_id == row.id) {
                            return "cut";
                        }
                    }
                }

                return false;
            },
            toggle: function(v) {
                this[v] = !this[v];
                $('a, button').blur();
            }
        },
        computed: {
            has_selected: function() {
                var lst = this.lst;

                var l = lst.length;
                for (var i = 0; i < l; i++) {
                    var e = lst[i];
                    if (e.selected) {
                        return true;
                    }
                }

                return false;
            },
            has_clipboard: function() {
                if (this.clipboard === undefined) {
                    return false;
                }
                if (this.clipboard === null) {
                    return false;
                }
                var cb = null;
                if (this.clipboard.copy !== undefined) {
                    cb = this.clipboard.copy;

                    if (cb.length > 0) {
                        return true;
                    }

                }
                if (this.clipboard.cut !== undefined) {
                    cb = this.clipboard.cut;

                    if (cb.length > 0) {
                        return true;
                    }
                }

                return false;
            }
        },
        watch: {
            show_tree: function(new_val) {
                localStorage.setItem("show_tree", new_val);
            }
        }
    });

})();
