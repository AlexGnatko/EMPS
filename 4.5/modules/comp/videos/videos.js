emps_scripts.push(function() {

    Vue.component('videos', {
        props: {
            context: {
                required: true,
            },
        },
        data: function () {
            return {
                lst: [],
                video_url: "",
            };
        },
        methods: {
            load_list: function (after) {
                var that = this;
                axios
                    .get("./?list_videos=1")
                    .then(function (response) {
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.lst = data.videos;
                            if (after !== undefined) {
                                after.call();
                            }
                        } else {
                            alert(data.message);
                        }
                    });
            },
            pic_url: function(pic) {
                if (!pic) {
                    return "/i/b.gif";
                }
                return "/freepic/" + pic.md5 + "/" + pic.filename + "?size=640x360";
            },
            process_video: function (e) {
                e.preventDefault();
                e.stopPropagation();
                var that = this;
                var row = {};
                row.post_add_video = true;
                row.url = this.video_url;
                axios
                    .post("./", row)
                    .then(function(response){
                        var data = response.data;
                        if(data.code == 'OK'){
                            that.lst = data.videos;
                        }
                    });
                return false;
            },
            delete_video: function(index) {
                var that = this;
                var id = this.lst[index].id;
                axios
                    .get("./?delete_video=" + id)
                    .then(function (response) {
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.lst = data.videos;
                        } else {
                            alert(data.message);
                        }
                    });
            },
            on_sort_videos: function(e) {
                var files = this.lst.slice();
                files.splice(e.newIndex, 0, files.splice(e.oldIndex, 1)[0]);


                var ids = [];
                for (var i = 0; i < files.length; i++ ) {
                    ids.push(files[i].id);
                }

                this.lst = [];

                var that = this;
                axios
                    .get("./?reorder_videos=" + ids.join(','))
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.lst = data.videos;
                            $("button").blur();
                        }else{
                            alert(data.message);
                        }
                    });
            },

        },
        watch: {
        },
        mounted: function () {
            this.load_list();
        }
    });

});