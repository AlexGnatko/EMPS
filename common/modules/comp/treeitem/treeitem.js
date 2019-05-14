(function() {

    Vue.component('treeitem', {
        template: '#treeitem-template',
        props: ['item'],
        data: function(){
            return {
            };
        },
        computed: {
            is_folder: function () {
                return this.item.subs &&
                    this.item.subs.length;
            },
            is_active: function() {
                if (this.item.active) {
                    return true;
                }
                return false;
            },
        },
        methods: {
            set_open: function(is_open) {
                Vue.set(this.item, "is_open", is_open);
            },
            toggle: function () {
                if (this.is_folder) {
                    if (!this.item.is_open) {
                        Vue.set(this.item, "is_open", true);
                    } else {
                        Vue.set(this.item, "is_open", false);
                    }
                    if (!this.item.is_open) {
                        this.set_active();
                    }
                }
            },
            delete_folder: function() {
                if (confirm("Удалить подраздел?")) {
                    this.$emit('delete_folder', this.item);
                }
            },
            edit_folder: function() {
                this.$emit('edit_folder', this.item);
            },
            set_active: function() {
                this.$emit('set_active', this.item);
            }
        },
        watch: {
            item: function(val) {
            }
        }
    });


})();
