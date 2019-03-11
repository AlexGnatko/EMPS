var EMPS_V_enumval = {
    data: function() {
        return {
            enums: {},
        };
    },
    methods: {
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
        }
    },
    mounted: function() {
    }
};

if (window.EMPS_vted_mixins === undefined) {
    window.EMPS_vted_mixins = [];
}

window.EMPS_vted_mixins.push(EMPS_V_enumval);