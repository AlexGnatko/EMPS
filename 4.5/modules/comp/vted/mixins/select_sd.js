var EMPS_V_select_sd = {
    data: function() {
        return {
        };
    },
    methods: {
        select_sd: function(sd) {
            if (sd.length === 0) {
                sd = undefined;
            }
            var path = Vue.util.extend({}, this.path);
            path.sd = sd;
            path.start = undefined;
            path.key = undefined;
            var link = EMPS.link(path);
            EMPS.soft_navi(vted_title, link);
            vuev.$emit("navigate");
        },
    },
    mounted: function() {
    }
};

if (window.EMPS_vted_mixins === undefined) {
    window.EMPS_vted_mixins = [];
}

window.EMPS_vted_mixins.push(EMPS_V_select_sd);