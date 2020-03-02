emps_scripts.push(function() {
    Vue.component('multi-input', {
        template: '#multiinput-component-template',
        props: ['value'],
        data: function(){
            return {
                lst: [{text: ""}],
                block_list: false,
                timeout: null,
            };
        },
        methods: {
            emit_value: function() {
//                console.log("Emitting value");
                var l = this.lst.length;
                for (var i = 0; i < l; i++) {
                    if (this.lst[i].text) {
                        if (this.lst[i].text != this.value[i]) {
                            this.value[i] = this.lst[i].text;
                        }
                    }
                }

                this.value.splice(l - 1);

                this.$emit('input', this.value);
                //console.log("Emitting: " + l + " = " + JSON.stringify(this.value));
            },
            set_value: function(new_val) {
                this.block_list = true;
                var l = new_val.length;
                for (var i = 0; i < l; i++) {
                    this.$set(this.lst, i, {text: new_val[i]});
                }
                this.lst.splice(l);
                this.lst.push({text: ""});
                var that = this;
                setTimeout(function() {
                    that.block_list = false;
                }, 100);
            }
        },
        computed: {
        },
        watch: {
            lst: {
                handler: function(val) {
                    if (this.block_list) {
                        return;
                    }
                    //console.log("handler");
                    var l = val.length;
                    for (var i = 0; i < l; i++) {
                        if (val[i] === undefined) {
                            continue;
                        }
                        if ((val[i].text == "") && (i < (l - 1))) {
                            val.splice(i, 1);
                        }
                    }
                    l = val.length;
                    if (l > 0) {
                        if (val[l - 1].text != "") {
                            val.push({text: ""});
                        }
                    }

                    this.lst = val;

                    if (this.timeout !== null) {
                        clearTimeout(this.timeout);
                    }

                    this.timeout = setTimeout(this.emit_value, 100);

                },
                deep: true
            },
            value: {
                handler: function (new_val) {
                    //console.log("set value");
                    this.set_value(new_val);
                },
                deep: true
            }
        },
        mounted: function(){
            this.set_value(this.value);
        }
    });


});
