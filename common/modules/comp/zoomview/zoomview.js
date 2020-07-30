(function() {

    Vue.component('zoomview', {
        template: '#zoomview-component-template',
        props: ['spacer', 'normal', 'zoom', 'scale', 'divClass', 'aClass'],
        data: function(){
            return {
                zoomed: false
            };
        },
        methods: {
            zoom: function() {
                this.zoomed = true;
            },
            unzoom: function() {
                this.zoomed = false;
            },
            move: function(event) {

            },
        },
        watch: {
        },
        mounted: function(){
        }
    });


})();
