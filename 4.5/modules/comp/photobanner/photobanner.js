emps_scripts.push(function() {
    Vue.component('photobanner', {
        template: '#photobanner-component-template',
        props: {
            context: {
                required: true,
            },
            interval: {
                type: Number,
                default: 5000
            },
        },
        data: function(){
            return {
                files: [],
                display: [],
                index: 0,
                swing_timeout: null,
                update_timeout: null,
                fade_out: false,
            };
        },
        methods: {
            load_list: function() {
                if (!this.context) {
                    this.files = [];
                    return;
                }
                var that = this;
                this.files = [];
                axios
                    .get(this.target + "?list_uploaded_photos=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.files = data.files;
                            that.update_photos();
                        }else{
                            console.log("Photobanner: " + data.message);
                        }
                    });

            },
            photo_link: function(file) {
                return "/pic/" + file.md5 + "/" + file.filename;
            },
            swing: function() {
                this.fade_out = true;
            },
            go: function(val) {
                clearTimeout(this.swing_timeout);
                clearTimeout(this.update_timeout);
                if (!this.fade_out) {
                    this.index--;
                }

                this.index += val;
                if (this.index < 0) {
                    this.index = this.files.length + this.index;
                }
                if (this.index >= this.files.length) {
                    this.index -= this.files.length;
                }
                this.update_photos();
            },
            update_photos: function() {
                if (this.files.length == 0) {
                    return;
                }
                this.fade_out = false;
                this.display = [];
                this.display.push(this.files[this.index]);
                this.index++;
                if (this.index >= this.files.length) {
                    this.index = 0;
                }
                this.display.push(this.files[this.index]);

                this.swing_timeout = setTimeout(this.swing, this.interval - 450);
                this.update_timeout = setTimeout(this.update_photos, this.interval);
            }
        },
        computed: {
            target: function() {
                return "/json-list-photos/" + this.context + "/";
            }
        },
        watch: {
            context: function(new_val, old_val) {
                var that = this;
                setTimeout(function() {
                    that.load_list();
                }, 200);
            }
        },
        mounted: function(){
            this.load_list();
        }
    });


});
