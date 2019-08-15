(function() {

    Vue.component('videos', {
        data: function(){
            return {
                lst: [],
            };
        },
        methods: {
            load_data: function(after){
                var that = this;
                axios
                    .get("./?load_settings=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.lst = data.lst;
                            if (after !== undefined){
                                after.call();
                            }
                        }else{
                            alert(data.message);
                        }
                    });
            },
        },
        mounted: function(){
            this.load_data();
        }
    });

})();