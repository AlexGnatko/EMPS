(function() {

    Vue.component('flatpickr', {
        template: '#flatpickr-component-template',
        props: ['size', 'value', 'hasTime', 'minDate', 'maxDate', 'setclass', 'dateFormat', 'placeholder', 'asButton'],
        data: function(){
            return {
                picker: null,
                set_class: '',
                config: emps_flatpickr_options,
            };
        },
        methods: {
            redraw: function(newConfig) {
                this.picker.config = Object.assign(this.picker.config, newConfig);
                this.picker.config.minDate = this.minDate;
                this.picker.config.maxDate = this.maxDate;
                this.picker.config.disableMobile = true;
                this.picker.redraw();
                this.picker.jumpToDate();
            },
            set_date: function(newDate, oldDate) {
//                alert(newDate + " / " + oldDate);
                if ((newDate !== oldDate) && newDate !== undefined && newDate != '') {
                    this.picker.setDate(newDate);
                    //console.log("Setting date: " + newDate + " / " + oldDate);
                }
                if (newDate === undefined || newDate == '') {
                    $(this.$refs.input).val('');
                }
            },
            date_updated: function(selectedDates, dateStr) {
                if (dateStr !== undefined && dateStr != '') {
                    //console.log("Date updated: "  + dateStr);
                    this.value = dateStr;
                    this.$emit("input", this.value);
                }
            }
        },
        mounted: function(){
            this.config.minDate = this.minDate;
            this.config.maxDate = this.maxDate;
            if (!this.picker) {
                this.config.onValueUpdate = this.date_updated;
                this.config.disableMobile = true;
                var dateFormat = "d.m.Y";
                if (this.dateFormat !== undefined) {
                    dateFormat = this.dateFormat;
                }
                if (this.hasTime) {
                    this.config.enableTime = true;
                    this.config.dateFormat = dateFormat + " H:i";
                } else {
                    this.config.enableTime = false;
                    this.config.dateFormat = dateFormat;
                }

                this.picker = flatpickr(this.$refs.input, this.config);
                this.set_date(this.value);
            }
            this.$watch('minDate', this.redraw);
            this.$watch('maxDate', this.redraw);
            this.$watch('config', this.redraw);
            this.$watch('value', this.set_date);
            this.$watch('setclass', function(newval, oldval) {
                this.set_class = newval;
            });
        }
    });


})();
