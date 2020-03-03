(function() {

    Vue.component('multi-editor', {
        props: ['value', 'vars'],
        components: {
            'editor': Editor // <- Important part
        },
        data: function(){
            return {
                html: "",
                evar: "",
                emps_tinymce_settings: window.emps_tinymce_settings,
            };
        },
        methods: {
            update_html: function(ss) {
                this.html = this.value[this.evar];
            }
        },
        watch: {
            html: function(new_val, old_val) {
                if (this.evar && (this.value !== undefined) && (new_val !== undefined)) {
                    this.$set(this.value, this.evar, new_val);
                    this.$emit('input', this.value);
                }
            },
            evar: function(new_val, old_val) {
                var v = this.value[new_val];
                if (v !== undefined) {
                    this.html = v;
                } else {
                    this.html = "";
                }
            },
            value: {
                handler: function(val) {
                    // do stuff
                    this.html = val[this.evar];
                },
                deep: true
            }
        },
        mounted: function(){
            if (!this.evar) {
                if (this.vars.length > 0) {
                    var first = this.vars[0];
                    this.evar = first.var;
                }
            }
        }
    });

})();