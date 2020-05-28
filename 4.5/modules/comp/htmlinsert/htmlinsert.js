(function() {

    Vue.component('html-insert', {
        props: ['id', 'context_id'],
        data: function(){
            return {
                lst: [],
                pics: [],
                videos: [],
                insert_params: {
                    class: 'pic-full',
                    new_mode: 'upload',
                    selected_pic: {},
                    picsize: 500,
                    lightbox: false,
                    photoset_type: 'montage',
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
            pic_url: function(pic) {
                if (!pic) {
                    return "/i/b.gif";
                }
                return "/freepic/" + pic.md5 + "/" + pic.filename + "?size=640x360";
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
                if (mode == 'reduced') {
                    html = '<img src="/freepic/' + this.insert_params.selected_pic.md5 + '/'
                        + this.insert_params.selected_pic.filename + '?size=' + this.insert_params.picsize +
                        'x' + this.insert_params.picsize + '&opts=inner" class="'
                        + this.insert_params.class + '"/>';
                    if (this.insert_params.lightbox) {
                        html = '<a href="' + this.insert_params.selected_pic.url + '" class="ipbox">' + html + '</a>';
                    }
                }
                this.close_modal('htmlinsertPhotoModal');
                this.reset_params();
                this.insert(html);
            },
            insert_pics: function(mode) {
                var html = '';
                var plugin = this.insert_params.photoset_type;
                if (mode == 'all') {
                    html += '{{emps plugin=' + plugin + ' context=' + this.context_id + '}}';
                } else {
                    var ids = [];
                    for (var i = 0; i < this.pics.length; i++) {
                        if (this.pics[i].checked) {
                            ids.push(this.pics[i].id);
                        }
                    }
                    var list = ids.join(',');
                    html += '{{emps plugin=' + plugin + ' list=\'' + list + '\'}}';
                }

                this.close_modal('htmlinsertPhotosetModal');
                this.reset_params();
                this.insert(html);
            },
            insert_video: function(video) {
                var html = '{{emps plugin=video id=' + video.id + '}}';
                this.close_modal('htmlinsertVideosModal');
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
            on_photos: function(data) {
                this.load_pics();
                this.open_modal('htmlinsertPhotosetModal');
            },
            on_video: function(data) {
                this.load_videos();
                this.open_modal('htmlinsertVideosModal');
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
            load_videos: function() {
                var that = this;
                axios
                    .get("/json-list-videos/" + this.context_id + "/")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.videos = data.videos;
                        }else{
                            alert(data.message);
                        }
                    });
            },
            select_pic: function(pic) {
                this.insert_params.selected_pic = pic;
            },
            check_pic: function(pic) {
                if (!pic.checked) {
                    pic.checked = true;
                }else{
                    pic.checked = false;
                }
                this.$forceUpdate();
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
            vuev.$off("htmlinsert:photos");
            vuev.$off("htmlinsert:video");
            vuev.$off("htmlinsert:audio");
            vuev.$off("htmlinsert:cut");

            vuev.$on("htmlinsert:photo", this.on_photo);
            vuev.$on("htmlinsert:photos", this.on_photos);
            vuev.$on("htmlinsert:video", this.on_video);
            vuev.$on("htmlinsert:audio", this.on_audio);
            vuev.$on("htmlinsert:cut", this.on_cut);
        }
    });

})();