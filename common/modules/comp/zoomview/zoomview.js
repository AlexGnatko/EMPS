(function() {

    Vue.component('zoomview', {
        template: '#zoomview-component-template',
        props: ['spacer', 'normal', 'zoom', 'scale', 'divClass', 'aClass', 'spacerClass'],
        data: function(){
            return {
                zoomed: false,
                tapped: false,
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
            touchzoom: function(event) {
                if (!this.tapped) {
                    this.tapped = true;
                    var that = this;
                    setTimeout(function() {
                        that.tapped = false;
                    }, 300);
                } else {
                    this.zoomed = !this.zoomed;
                    event.preventDefault();
                    event.stopPropagation();
                }
            },
            touchmove: function(event) {
                if (!this.zoomed) {
                    return;
                }
                var touch = event.targetTouches.item(0);
                if (touch !== undefined) {
                    this.move(touch);
                }
                event.preventDefault();
                event.stopPropagation();
                return false;
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
                var ow = this.$el.offsetWidth;
                var oh = this.$el.offsetHeight;

                if (relativeX < 0) {
                    relativeX = 0;
                }
                if (relativeY < 0) {
                    relativeY = 0;
                }
                if (relativeX > ow) {
                    relativeX = ow;
                }
                if (relativeY > oh) {
                    relativeY = oh;
                }
                var ze = this.$refs.zoom;
                var magX = (ze.offsetWidth - ow) / ow;
                var magY = (ze.offsetHeight - oh) / oh;
                var resultX = -1 * (relativeX * magX);
                var resultY = -1 * (relativeY * magY);
                ze.style.left = resultX + "px";
                ze.style.top = resultY + "px";
//                console.log(magX + " / " + magY);
            },
        },
        watch: {
            normal: function() {
                this.zoomed = false;
            },
            zoom: function() {
                this.zoomed = false;
            },
        },
        mounted: function(){
        }
    });


})();
