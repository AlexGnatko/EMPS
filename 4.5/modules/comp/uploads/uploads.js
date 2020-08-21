emps_scripts.push(function() {
    Vue.component('uploads', {
        template: '#uploads-component-template',
        props: ['context', 'readonly'],
        data: function(){
            return {
                selected_file: '',
                need_upload: false,
                queue: [],
                files: []
            };
        },
        methods: {
            add_files: function() {
                this.$refs.files.click();
            },
            handle_uploads: function(){
                var files = this.$refs.files.files;

                if (files.length == 0) {
                    return;
                }

                this.$emit("uptodate", false);
                for (var i = 0; i < files.length; i++ ) {
                    files[i].image_url = URL.createObjectURL(files[i]);
                    files[i].started = false;
                    files[i].progress = 0;
                    this.queue.push(files[i]);
                    this.start_uploading();
                }
            },
            start_uploading: function() {
                if (this.context === undefined || this.context === null || !this.context) {
                    this.need_upload = true;
                    return;
                }

                this.need_upload = false;

                for (var i = 0; i < this.queue.length; i++ ) {
                    var file = this.queue[i];
                    if (!file.started) {
                        file.started = true;
                        var form_data = new FormData();
                        form_data.append('post_upload_file', '1');
                        form_data.append('files[0]', file);
                        var that = this;
                        axios.post( this.target,
                            form_data,
                            {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                },
                                onUploadProgress: function(e) {
                                    if(e.lengthComputable){
                                        file.loaded = e.loaded;
                                        file.total = e.total;
                                        //console.log(file);
                                        that.$forceUpdate();
                                    }
                                },
                                cancelToken: new axios.CancelToken(function executor(c) {
                                    // An executor function receives a cancel function as a parameter
                                    file.cancel_executor = c;
                                })
                            }
                        ).then(function(response){
                            that.remove_upload(file);
                            var data = response.data;

                            if (data.code == 'OK') {
                                // remove from queue, add to files
                                that.files = data.files;
                                that.$emit("uptodate", true);
                            }else{
                                toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                            }

                        })
                            .catch(function(){
                                if (!file.cancelled) {
                                    toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                                }

                                that.remove_upload(file);

                            });
                    }
                }
            },
            remove_upload: function(file) {
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i] === file) {
                        this.queue.splice(i, 1);
                        break;
                    }
                }
            },
            delete_file: function(file) {
                if (!confirm("Удалить файл?")) {
                    return;
                }
                var that = this;
                axios
                    .get(this.target + "?delete_uploaded_file=" + file.id)
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.files = data.files;
                            $("button").blur();
                        }else{
                            alert(data.message);
                        }
                    });
            },
            delete_queue: function(idx) {
                this.queue.splice(idx, 1);
            },
            load_files: function() {
                if (!this.context) {
                    this.files = [];
                    return;
                }
                var that = this;
                axios
                    .get(this.target + "?list_uploaded_files=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.files = data.files;
                        }else{
                            alert(data.message);
                        }
                    });

            },
            is_uploading: function() {
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i].started) {
                        return true;
                    }
                }
                return false;
            },
            get_total_progress: function() {
                var loaded = 0, total = 0;
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i].started) {
//                        console.log(this.queue[i]);
                        if (!isNaN(this.queue[i].loaded)) {
                            loaded += this.queue[i].loaded;
                        }
                        if (!isNaN(this.queue[i].total)) {
                            total += this.queue[i].total;
                        }
                    }
                }

                if (total === 0) {
                    return 0;
                }

                var rv = Math.round((loaded / total) * 100, 2);
                return rv;
            },
        },
        computed: {
            target: function() {
                return "/json-upload/" + this.context + "/";
            }
        },
        watch: {
            context: function(new_val, old_val) {
//                alert("New context: " + new_val);
                if (this.need_upload) {
                    this.start_uploading();
                } else {
                    if (new_val === undefined || new_val === null || !new_val) {
                        var that = this;
                        setTimeout(function() {
                            that.load_files();
                        }, 500);
                    }
                    this.$emit("uptodate", true);
                }

            },
            files: function(new_val, old_val) {
                this.$emit("update", new_val);
                if (new_val.length == 0 && this.queue.length == 0) {
//                    alert("Empty files update");
                    this.$emit("uptodate", true);
                }
            },
            queue: function(new_val, old_val) {
                if (new_val.length == 0) {
//                    alert("Empty queue update");
                    this.$emit("uptodate", true);
                }
            },
        },
        mounted: function(){
            this.$watch('context', this.load_files);
            this.load_files();
        }
    });


});
