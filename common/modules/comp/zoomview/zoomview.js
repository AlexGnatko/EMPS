(function() {

    Vue.component('zoomview', {
        template: '#zoomview-component-template',
        props: ['spacer', 'normal', 'zoom', 'scale', 'divClass', 'aClass', 'spacerClass'],
        data: function(){
            return {
                zoomed: false
            };
        },
        methods: {
            offset: function(el) {
                var rect = el.getBoundingClientRect(),
                    scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
                    scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                return {
                    y: rect.top + scrollTop,
                    x: rect.left + scrollLeft
                }
            },
            dozoom: function() {
                this.zoomed = true;
            },
            unzoom: function() {
                this.zoomed = false;
            },
            move: function(event) {
                if (!this.zoomed) {
                    return;
                }
                var offset = this.offset(this.$el);
                var relativeX = event.clientX - offset.x + window.pageXOffset;
                var relativeY = event.clientY - offset.y + window.pageYOffset;
                var ze = this.$refs.zoom;
                var ow = this.$el.offsetWidth;
                var oh = this.$el.offsetHeight;
                var magX = ze.offsetWidth / ow;
                var magY = ze.offsetHeight / oh;
                var resultX = -1 * (relativeX * magX - ow);
                var resultY = -1 * (relativeY * magY - oh);
                ze.style.left = resultX + "px";
                ze.style.top = resultY + "px";
//                console.log(magX + " / " + magY);
            },
        },
        watch: {
        },
        mounted: function(){
        }
    });


})();
