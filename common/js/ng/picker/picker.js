app.controller('item_picker', function($rootScope, $scope, $http, $timeout) {
    $scope.t_search = null;
    $scope.pvar = null;
    $scope.ptype = null;
    $scope.pparams = null;
    $scope.init_done = false;
    $scope.not_found = false;
    $scope.selected_row = null;
    $scope.picker_id = null;

    $scope.open_picker = function(params){
        var pvar = params.pvar;

        $scope.pvar = pvar;
        $scope.picker_id = params.id;

        $scope.selected_row = null;

        $scope.load_options();
        $("#picker-modal-" + pvar).modal("show");

        if(!$scope.init_done){
            $scope.init_done = true;
            $scope.$watch(pvar + ".search", $scope.search_watcher);
        }
    };

    $scope.$on('picker', function(e, params) {
        $scope.rows = [];
        $scope.pages = {};
        $scope.open_picker(params);
    });

    $scope.input_changed = function(){
        $scope.not_found = false;

        if($scope.pvar != null) {
            $scope[$scope.pvar].start = 0;
            $scope.selected_row = null;
            if ($scope.t_search !== null) {
                $timeout.cancel($scope.t_search);
            }
            $scope.t_search = $timeout(function () {
                $scope.load_options();
            }, 500);
        }

    };

    $scope.search_watcher = function(newValue, oldValue){

        $scope.input_changed();
    };



    $scope.select_row = function(key){
        $scope.selected_row = $scope.rows[key];
        $scope.$parent.$broadcast('picker_result', {id: $scope.picker_id, pvar: $scope.pvar, row: angular.copy($scope.selected_row)});
        $timeout(function(){
            $("#picker-modal-" + $scope.pvar).modal("hide");
        }, 100);
    };

    $scope.roll_to = function(page){
        $scope[$scope.pvar].start = page.start;
        $scope.load_options();
    };

    $scope.load_options = function(){

        $scope.not_found = false;

        var params = $scope[$scope.pvar].params;
        if(params === undefined){
            params = "";
        }
        var extra_params = $scope[$scope.pvar].extra_params;
        if(extra_params !== undefined){

            for(var name in extra_params) {
                if(params != ""){
                    params += "|";
                }
                var value = extra_params[name];
                params += name + "=" + value;
            }
        }
        var search = $scope[$scope.pvar].search;
        if(search === undefined){
            search = "";
        }
        var perpage = $scope[$scope.pvar].perpage;
        if(perpage === undefined){
            perpage = 10;
        }

        $http.get("/picker/" + $scope.pvar + "/?picker_st=" + $scope[$scope.pvar].start + "&picker_ps=" + perpage + "&picker_type=" + $scope[$scope.pvar].ptype + "&picker_params=" + encodeURIComponent(params) + "&picker_list=1&picker_search=" + search).success(function (response) {
            if (response.code == 'OK') {
                $scope.rows = response.lst;
                $scope.pages = response.pages;
                if($scope.rows.length == 0){
                    $scope.not_found = true;
                }else{
                    $scope.not_found = false;
                }
            }
        });
    };

    if(emps_picker_extender !== undefined){
        var cb;

        do{
            cb = emps_picker_extender.pop();
            if(cb !== undefined){
                cb($rootScope, $scope, $timeout, $http);
            }
        }while(cb !== undefined);
    }
});