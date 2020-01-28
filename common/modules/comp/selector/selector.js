(function() {

    Vue.component('selector', {
        template: '#selector-template',
        props: ['value', 'type', 'title', 'size', 'search', 'noClear', 'noPages', 'noField',
            'infoButton', 'onInfo',
            'placeholder', 'hasExtra'],
        data: function(){
            return {
                guid: guid(),
                description: '',
                searchtext: '',
                pages: {},
                reload_promise: null,
                lst: [],
                start: 0,
            };
        },
        methods: {
            select: function() {
                var that = this;
                this.reload(function(){
                    vuev.$emit("modal:open:" + that.modal_name());
                });
            },
            close_modal: function() {
                $("#" + this.modal_name()).removeClass("is-active");
            },
            ask_reload: function() {
                clearTimeout(this.reload_promise);
                var that = this;
                this.reload_promise = setTimeout(function(){
                    that.reload();
                }, 500);
            },
            roll_to: function(page) {
                this.start = page.start;
                this.reload();
            },
            reload: function(then) {
                var that = this;
                axios
                    .get("/pick-ng-list/" + this.type + "/" + this.start + "/?text="
                        + encodeURIComponent(this.searchtext))
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.lst = data.list;
                            that.pages = data.pages;
                            if (then !== undefined) {
                                then();
                            }
                        }else{
                            alert(data.message);
                        }
                    });
            },
            select_item: function(row) {
                this.value = row.id;
                this.$emit('input', this.value);
                this.close_modal();
            },
            clear: function() {
                this.description = '';
                this.value = 0;
                this.$emit('input', this.value);
            },
            modal_name: function() {
                return "selectorModal" + this.guid;
            },
            describe: function() {
                if (this.value === undefined || this.value === 0 || this.value === '0') {
                    this.description = '';
                    return;
                }
                var that = this;
                axios
                    .get("/pick-ng-describe/" + this.type + "/" + this.value + "/")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.description = data.display;
                        }else{
                            alert(data.message);
                        }
                    });
            },
            follow_link: function() {
                if (this.value > 0) {
                    this.onInfo(this.value);
                }
            }
        },
        mounted: function(){
            this.describe();
        },
        watch: {
            value: function(val) {
                this.describe();
            }
        }
    });


})();
