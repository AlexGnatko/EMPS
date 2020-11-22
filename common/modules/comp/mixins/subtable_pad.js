
var EMPS_V_subtable = {
    data: function() {
        return {
            newrow: {},
            editrow: {},
            sending: false,
            lst: [],
        };
    },
    methods: {
        load_subtable_list: function() {
            var that = this;
            axios
                .get("./?list_subtable=1")
                .then(function(response){
                    var data = response.data;
                    if (data.code == 'OK') {
                        that.lst = data.lst;
			if (data.total !== undefined) {
				that.total = data.total;
			}
                    }else{
                        toastr.error(data.message);
                    }
                });
        },
        add_row: function() {
            this.sending = true;
            var that = this;
            var row = {};
            row.post_add_to_subtable = 1;
            row.payload = this.newrow;
            axios
                .post("./", row)
                .then(function(response){
                    that.sending = false;
                    var data = response.data;

                    if(data.code == 'OK'){
                        that.load_subtable_list();
                        if (that.after_save !== undefined) {
                            that.after_save();
                        }
                        $('form *, button').blur();
                        toastr.success(window.string_saved);
                    } else {
                        toastr.error(data.message);
                    }
                });
        },
        edit_row: function(row) {
            this.editrow = Vue.util.extend({}, row);
        },
        save_row: function() {
            this.sending = true;
            var that = this;
            var row = {};
            row.post_save_to_subtable = 1;
            row.payload = this.editrow;
            axios
                .post("./", row)
                .then(function(response){
                    that.sending = false;
                    that.editrow = {};
                    var data = response.data;

                    if(data.code == 'OK'){
                        that.load_subtable_list();
                        if (that.after_save !== undefined) {
                            that.after_save();
                        }
                        $('form *, button').blur();
                        toastr.success(window.string_saved);
                    } else {
                        toastr.error(data.message);
                    }
                });
        },
        delete_row: function(selected_row) {
            if (confirm('Удалить строку?')) {
                var that = this;
                var row = {};
                row.post_delete_from_subtable = 1;
                row.id = selected_row.id;
                axios
                    .post("./", row)
                    .then(function(response){
                        that.editrow = {};
                        var data = response.data;

                        if(data.code == 'OK'){
                            that.load_subtable_list();
                            $('form *, button').blur();
                            toastr.success(window.string_deleted);
                        } else {
                            toastr.error(data.message);
                        }
                    });
            }
        }
    },
    mounted: function() {

    }
};

if (window.EMPS_vted_mixins === undefined) {
    window.EMPS_vted_mixins = [];
}

window.EMPS_vted_mixins.push(EMPS_V_subtable);