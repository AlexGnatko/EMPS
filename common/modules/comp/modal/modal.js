(function() {

    Vue.component('modal', {
        template: '#modal-component-template',
        props: ['id', 'form', 'submit', 'size', 'buttonClass'],
        data: function(){
            return {
                btn_class: {'button': true}
            };
        },
        methods: {
            close_modal: function(e){
                $("#" + this.id).removeClass("is-active");
            },
            on_open: function(data){
                $("#" + this.id).addClass("is-active");
            },
            submit_form: function(){
                if(this.submit !== undefined){
                    this.submit.call();
                }
            },
            get_class: function(){
                var c = "modal-card";
                switch(this.size){
                    case "lg":
                        c += " modal-lg";
                        break;
                    case "sm":
                        c += " modal-sm";
                        break;
                }
                return c;
            }
        },
        mounted: function(){
            this.btn_class = Vue.util.extend(this.btn_class, this.buttonClass);
            this.$forceUpdate();
            vuev.$on("modal:open:" + this.id, this.on_open);
        }
    });


})();
