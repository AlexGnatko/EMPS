var EMPS_common_mixin = {
    data: function() {
        return {
        };
    },
    methods: {
        open_modal: function(id){
            vuev.$emit("modal:open:" + id);
        },
        close_modal: function(id){
            $("#" + id).removeClass("is-active");
        },
        enum_val: function(ecode, code) {
            if (this.enums === undefined) {
                return "[no_enums]";
            }
            if (this.enums[ecode] === undefined) {
                return "[no_enum]";
            }
            var l = this.enums[ecode].length;
            for (var i = 0; i < l; i++) {
                var v = this.enums[ecode][i];
                if (v.code === code) {
                    return v.value;
                }
            }
            return "[no_value]";
        },
        open_in_blank: function(href) {
            var link = document.createElementNS("http://www.w3.org/1999/xhtml", "a");
            link.href = href;
            link.target = '_blank';
            var event = new MouseEvent('click', {
                'view': window,
                'bubbles': false,
                'cancelable': true
            });
            link.dispatchEvent(event);
        },
    },
    mounted: function() {
    }
};
