(function() {

    Vue.component('html-insert', {
        props: ['id'],
        data: function(){
            return {
                lst: [],
                pics: [],
                insert_params: {
                    class: 'pic-full',
                    new_mode: 'upload',
                    selected_pic: {},
                },
                classes: window.emps_pic_classes,
                no_class: window.emps_pic_no_class,
                select_class: false,
                guid: guid(),
            };
        },
        methods: {
            reset_params: function() {
                this.insert_params = {
                    class: 'pic-full',
                    new_mode: 'upload',
                    selected_pic: {},
                };
            },
            insert: function(text) {
                tinymce.get(this.id).execCommand('mceInsertContent', false, text);
            },
            insert_pic: function(mode) {
                var html = '';
                if (mode == 'full') {
                    html = '<img src="' + this.insert_params.selected_pic.url + '" class="'
                        + this.insert_params.class + '"/>';
                }
                this.close_modal('htmlinsertPhotoModal');
                this.reset_params();
                this.insert(html);
            },
            open_modal: function(id){
                vuev.$emit("modal:open:" + id);
            },
            close_modal: function(id){
                $("#" + id).removeClass("is-active");
            },
            on_photo: function(data) {
                this.load_pics();
                this.open_modal('htmlinsertPhotoModal');
            },
            on_photo_line: function(data) {
                alert('photo-line ' + JSON.stringify(data));
            },
            on_photo_montage: function(data) {
                alert('photo-montage ' + JSON.stringify(data));
            },
            on_video: function(data) {
                alert('video' + JSON.stringify(data));
            },
            on_audio: function(data) {
                alert('audio' + JSON.stringify(data));
            },
            on_cut: function(data) {
                this.insert('{{*cut*}}');
            },
            load_pics: function() {
                var that = this;
                axios
                    .get("./?list_uploaded_photos=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.pics = data.files;
                        }else{
                            alert(data.message);
                        }
                    });
            },
            select_pic: function(pic) {
                this.insert_params.selected_pic = pic;
            },
            select_new_photo: function() {
                this.$refs.new_photo.click();
            },
            handle_new_upload: function() {
                this.insert_params.uploading = true;
                this.$forceUpdate();

                if (this.insert_params.new_mode == 'upload') {
                    var files = this.$refs.new_photo.files;
                    var file = files[0];

                    var form_data = new FormData();
                    form_data.append('post_upload_photo', '1');
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
                        that.insert_params.uploading = false;

                        if (data.code == 'OK') {
                            that.pics = data.files;
                            that.insert_params.selected_pic = that.pics[that.pics.length - 1];
                        }else{
                            toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                        }
                    })
                        .catch(function(){
                            that.insert_params.uploading = false;
                            toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                        });
                } else {
                    var that = this;
                    var row = {};
                    row.post_import_photos = 1;
                    row.list = this.insert_params.download_url;
                    axios
                        .post("./", row)
                        .then(function(response){
                            var data = response.data;
                            that.insert_params.uploading = false;
                            if(data.code == 'OK')
                            {
                                that.pics = data.files;
                                that.insert_params.selected_pic = that.pics[that.pics.length - 1];
                                $("button").blur();
                            }
                        });
                }
            }
        },
        mounted: function(){
            vuev.$off("htmlinsert:photo");
            vuev.$off("htmlinsert:photo-line");
            vuev.$off("htmlinsert:photo-montage");
            vuev.$off("htmlinsert:video");
            vuev.$off("htmlinsert:audio");
            vuev.$off("htmlinsert:cut");

            vuev.$on("htmlinsert:photo", this.on_photo);
            vuev.$on("htmlinsert:photo-line", this.on_photo_line);
            vuev.$on("htmlinsert:photo-montage", this.on_photo_montage);
            vuev.$on("htmlinsert:video", this.on_video);
            vuev.$on("htmlinsert:audio", this.on_audio);
            vuev.$on("htmlinsert:cut", this.on_cut);
        }
    });

})();