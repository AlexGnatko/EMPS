(function() {

    Vue.component('flatpickr', {
        template: '#flatpickr-component-template',
        props: ['size', 'value', 'hasTime', 'minDate'],
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
                (newDate != oldDate) && this.picker.setDate(newDate);
            },
            date_updated: function(selectedDates, dateStr) {
                this.value = dateStr;
                this.$emit("input", this.value);
            }
        },
        mounted: function(){
            this.config.minDate = this.minDate;
            if (!this.picker) {
                this.config.onValueUpdate = this.date_updated;
                if (this.hasTime) {
                    this.config.enableTime = true;
                    this.config.dateFormat = "d.m.Y H:i";
                } else {
                    this.config.enableTime = false;
                    this.config.dateFormat = "d.m.Y";
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
