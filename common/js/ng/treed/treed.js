app.controller('tree_editor', function($rootScope, $scope, $http, $timeout) {

    if(tree_editor_extenders !== undefined){
        var l = tree_editor_extenders.length;
        for(var i = 0; i < l; i++) {
            tree_editor_extenders[i]($rootScope, $scope, $http, $timeout);
        }
    }

});