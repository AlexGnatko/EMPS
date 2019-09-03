(function() {

    Vue.component('flatpickr', {
        template: '#flatpickr-component-template',
        props: ['size', 'value', 'hasTime', 'minDate', 'dateFormat'],
        data: function(){
            return {
                picker: null,
                config: emps_flatpickr_options,
            };
        },
        methods: {
            redraw: function(newConfig) {
                this.picker.config = Object.assign(this.picker.config, newConfig);
                this.picker.config.minDate = this.minDate;
                this.picker.redraw();
                this.picker.jumpToDate();
            },
            set_date: function(newDate, oldDate) {
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
            if (!this.picker) {
                this.config.onValueUpdate = this.date_updated;
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
            this.$watch('config', this.redraw);
            this.$watch('value', this.set_date);
        }
    });


})();
