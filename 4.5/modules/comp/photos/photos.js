(function() {
    Vue.filter('size_format', function (value) {
        var s = value / (1024 * 1024);
        s = Math.round(s * 10);
        s /= 10;
        return s.toString() + " MB";
    });

    Vue.component('photo-uploader', {
        props: ['pad'],
        data: function(){
            return {
                selected_photo: '',
                import_list: '',
                importing: false,
                export_list: '',
                export_zip_link: '',
                change_uploading: false,
                change_mode: 'upload',
                change_download_url: '',
                queue: [],
                files: [],
                file: {},
                loading: false,
                uploading_zip: false,
                edit_mode: 'q',
            };
        },
        methods: {
            add_files: function() {
                this.$refs.files.click();
            },
            select_change_file: function() {
                this.$refs.change_file.click();
            },
            select_zip_file: function() {
                this.$refs.zip_file.click();
            },
            handle_uploads: function(){
                var files = this.$refs.files.files;

                if (files.length == 0) {
                    return;
                }

                for (var i = 0; i < files.length; i++ ) {
                    files[i].image_url = URL.createObjectURL(files[i]);
                    files[i].started = false;
                    files[i].progress = 0;
                    //console.log(files[i]);
                    this.queue.push(files[i]);
                    this.start_uploading();
                }
            },
            handle_zip_upload: function() {
                this.uploading_zip = true;
                this.$forceUpdate();

                var files = this.$refs.zip_file.files;
                var file = files[0];

                var form_data = new FormData();
                form_data.append('post_upload_zip', '1');
                form_data.append('files[0]', file);
                var that = this;
                axios.post( './',
                    form_data,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }
                ).then(function(response){
                    var data = response.data;
                    that.uploading_zip = false;

                    if (data.code == 'OK') {
                        that.files = data.files;
                        that.close_modal("uploadZipModal");
                    }else{
                        toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                    }

                }).catch(function(){
                    that.uploading_zip = false;
                    if (!file.cancelled) {
                        toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                    }
                });

            },
            handle_change_upload: function() {
                this.change_uploading = true;
                this.$forceUpdate();

                var files = this.$refs.change_file.files;
                var file = files[0];

                var form_data = new FormData();
                form_data.append('post_reupload_photo', '1');
                form_data.append('photo_id', this.selected_photo.id);
                form_data.append('files[0]', file);
                var that = this;
                axios.post( './',
                    form_data,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }
                ).then(function(response){
                    var data = response.data;
                    that.change_uploading = false;

                    if (data.code == 'OK') {
                        that.files = data.files;
                        that.close_modal("changeModal");
                    }else{
                        toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                    }

                })
                .catch(function(){
                    that.change_uploading = false;
                    if (!file.cancelled) {
                        toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                    }
                });

            },
            format_size: function(size) {
                return EMPS.format_size(size);
            },
            start_uploading: function() {
                for (var i = 0; i < this.queue.length; i++ ) {
                    var file = this.queue[i];
                    if (!file.started) {
                        file.started = true;
                        var form_data = new FormData();
                        form_data.append('post_upload_photo', '1');
                        form_data.append('files[0]', file);
                        var that = this;
                        axios.post( './',
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
            load_files: function(after) {
                if (!this.context) {
                    this.files = [];
                }
                this.loading = true;
                var that = this;
                axios
                    .get("./?list_uploaded_photos=1")
                    .then(function(response){
                        this.loading = false;
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.files = data.files;
                            if (after !== undefined) {
                                call(after);
                            }
                        }else{
                            alert(data.message);
                        }
                    });

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
                var that = this;
                axios
                    .get("./?delete_uploaded_photo=" + file.id)
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
            on_sort_photos: function(e) {
                var files = this.files.slice();
                files.splice(e.newIndex, 0, files.splice(e.oldIndex, 1)[0]);


                var ids = [];
                for (var i = 0; i < files.length; i++ ) {
                    ids.push(files[i].id);
                }

                this.files = [];

                var that = this;
                axios
                    .get("./?reorder_photos=" + ids.join(','))
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {

                            that.files = data.files;
                            for (var i = 0; i < that.files.length; i++ ) {
                                that.files[i].mark = false;
                            }
                            $("button").blur();
                        }else{
                            alert(data.message);
                        }
                    });
            },
            cancel_all: function() {
                for (var i = 0; i < this.queue.length; i++ ) {
                    this.cancel_upload(this.queue[i]);
                }
                $("button").blur();
            },
            cancel_upload: function(file) {
                file.cancelled = true;
                file.cancel_executor();
                $("button").blur();
            },
            inverse_selection: function() {
                for (var i = 0; i < this.files.length; i++ ) {
                    if (this.files[i].mark) {
                        this.files[i].mark = false;
                    } else {
                        this.files[i].mark = true;
                    }
                }
                this.$forceUpdate();
            },
            export_pics: function() {
                var lst = [];
                var id_lst = [];
                var get_url = window.location;
                var base_url = get_url.protocol + "//" + get_url.host;

                var i = 0;
                for (i = 0; i < this.files.length; i++ ) {
                    if (this.files[i].mark) {
                        lst.push(base_url + this.files[i].url);
                        id_lst.push(this.files[i].id);
                    }
                }
                if (lst.length === 0) {
                    for (i = 0; i < this.files.length; i++ ) {
                        lst.push(base_url + this.files[i].url);
                        id_lst.push(this.files[i].id);
                    }
                }
                this.export_list = lst.join("\r\n");
                this.export_zip_link = "./?zip=" + id_lst.join(",");
                this.open_modal("exportPicsModal");
            },
            upload_zip: function() {
                this.open_modal("uploadZipModal");
            },
            open_modal: function(id){
                vuev.$emit("modal:open:" + id);
            },
            close_modal: function(id){
                $("#" + id).removeClass("is-active");
            },
            delete_selected: function() {
                this.open_modal("deleteSelectedModal");
            },
            delete_selected_do: function() {
                this.close_modal("deleteSelectedModal");
                var that = this;
                var ids = [];
                for (var i = 0; i < this.files.length; i++ ) {
                    if (this.files[i].mark) {
                        ids.push(this.files[i].id);
                    }
                }

                var list = ids.join(",");
                axios
                    .get("./?delete_uploaded_photo=" + list)
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
            download_files: function() {
                this.open_modal("importModal");
            },
            get_progress: function(file) {
//                console.log(file.progress);
                if (file.total !== 0) {
                    file.progress = Math.round((file.loaded / file.total) * 100, 2);
                    if (isNaN(file.progress)) {
                        file.progress = 0;
                    }
                    //console.log(file.progress);
                }else{
                    file.progress = 0;
                }

                return file.progress;
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
            is_uploading: function() {
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i].started) {
                        return true;
                    }
                }
                return false;
            },
            edit_descr: function(file) {
                this.selected_photo = file;
                this.open_modal("descrModal");
            },
            change_file: function(file) {
                this.selected_photo = file;
                this.open_modal("changeModal");
            },
            submit_descr: function() {
                var that = this;
                var row = {};
                row.post_save_description = 1;
                row.photo_id = this.selected_photo.id;
                row.descr = this.selected_photo.descr;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;
                        if(data.code == 'OK')
                        {
                            that.close_modal("descrModal");
                            that.files = data.files;
                            $("button").blur();
                        }
                    });

            },
            submit_import: function() {
                this.importing = true;
                var that = this;
                var row = {};
                row.post_import_photos = 1;
                row.list = this.import_list;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;
                        if(data.code == 'OK')
                        {
                            that.close_modal("importModal");
                            that.files = data.files;
                            that.importing = false;
                            that.import_list = '';
                            $("button").blur();
                        }
                    });
            },
            submit_change: function() {
                if (this.change_mode == 'upload') {
                    this.handle_change_upload();
                }else{
                    this.change_uploading = true;
                    var that = this;
                    var row = {};
                    row.post_reimport_photo = 1;
                    row.url = this.change_download_url;
                    row.photo_id = this.selected_photo.id;
                    axios
                        .post("./", row)
                        .then(function(response){
                            var data = response.data;
                            that.change_download_url = '';
                            that.change_uploading = false;
                            if(data.code == 'OK')
                            {
                                that.close_modal("changeModal");
                                that.files = data.files;
                                $("button").blur();
                            }
                        });
                }
            },
            update_pad: function(pad) {
                if (this.pad == pad) {
                    if (!this.loading) {
                        this.load_files();
                    }
                }
            },
            photo_editor: function(file) {
                this.file = Vue.util.extend({}, file);
                this.open_modal("editPhotoModal");
            },
            manipulate_pic: function(file_id, payload) {
                var that = this;
                var row = {};
                row.post_manipulate = 1;
                row.photo_id = file_id;
                row.payload = payload;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;
                        if(data.code == 'OK')
                        {
                            that.files = data.files;

                            var l = that.files.length;
                            for (var i = 0; i < l; i++) {
                                if (that.files[i].id == file_id) {
                                    that.file = Vue.util.extend({}, that.files[i]);
                                }
                            }

                            $("button").blur();
                        }
                    });
            }
        },
        computed: {
        },
        mounted: function(){
            this.load_files();
            vuev.$on("update_pad", this.update_pad);
        }
    });


})();
