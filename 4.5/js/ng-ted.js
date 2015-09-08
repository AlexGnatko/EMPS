var ngted_providers = {};

var ngted_app = angular.module('ngted_app', ng_modules, function($controllerProvider, $compileProvider, $provide) {
    ngted_providers = {
        $controllerProvider: $controllerProvider,
        $compileProvider: $compileProvider,
        $provide: $provide
	}
});

ngted_app.factory('ngted_share', function(){
	var ngted_share = {};
	
	ngted_share.row = {};
	
	return ngted_share;
});

ngted_app.controller('ngted_controller', function($rootScope, $scope, $http, $location, $timeout, ngted_share) {
	$scope.start = 0;
	$scope.perpage = 25;
	$scope.list = [];
	$scope.pages = [];
	$scope.editmode = "add";
	$scope.row = ngted_share.row;
	$scope.after_load = function(scope){};
	
	$scope.load_rows = function(){
		$http.get("./?json=list&start="+$scope.start+"&perpage="+$scope.perpage).success(function (response) {
			if(response.code == 'OK'){
				$scope.list = response.list;
				$location.path(response.path);
				$scope.pages = response.pages;
			}
		});
	};
	
	$scope.roll_to = function(p){
		$scope.start = p.start;
		$scope.load_rows();
	};
	
	$scope.edit = function(id){
		$http.get("./?json=load&id="+id).success(function (response) {
			if(response.code == 'OK'){
				$scope.row = response.row;
				$scope.editmode = "edit";
				$("#ngtedEditModal").modal("show");
			}
		});
	}
	
	$scope.kill = function(id){
		$http.get("./?json=load&id="+id).success(function (response) {
			if(response.code == 'OK'){
				$scope.row = response.row;
				$("#ngtedKillModal").modal("show");
			}
		});
	}
	
	$scope.kill_current = function(){
		var id = $scope.row.id;
		if(!isNaN(id)){
			$http.get("./?json=kill&id="+id).success(function (response) {
				if(response.code == 'OK'){
					$("#ngtedKillModal").modal("hide");
					$scope.load_rows();
				}
			});
		}
	}
	
	$scope.add = function(){
		$scope.editmode = "add";
		$scope.row = {};
		$("#ngtedEditModal").modal("show");
	}
	
	$scope.save_current = function(){
		var row = $scope.row;
		var mode = $scope.editmode;
		
		if(mode == 'add'){
			row['post_add_item'] = 1;
		}else{
			row['post_save_item'] = row.id;
		}
		$http({
		method  : 'POST',
		url     : './',
		data    : $.param(row),  
		headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  
		})
		.success(function(response) {
			if(response.code == 'OK'){
				$("#ngtedEditModal").modal("hide");
				$scope.load_rows();
			}
		});
	}
	
	$scope.init = function(){
		$scope.load_rows();
	};

	$scope.$on('ngted_load_rows', function(event, args){
		$scope.start = 0;
		$scope.load_rows();
	});	
	
	$scope.$on('ngted_everything_loaded', function(event, args){
		$timeout(function(){
			$scope.after_load($scope);
		}, 500);
	});
	
	$scope.$on('$locationChangeSuccess', function(event) {
		var path = $location.path();
		var x = path.split('/');
		var start = parseInt(x[3]);
		if(!isNaN(start)){
			if(start != $scope.start){
				p = {start: start};
				$scope.roll_to(p);
			}
		}
	});
	
	$scope.pad = function(code, id){
		$rootScope.$broadcast('load_row', {id: id, code: code});
	};
	
	if((typeof ngted_custom) != "undefined"){
		$scope = angular.extend($scope, ngted_custom);
	}
	
	$scope.init();
	

})
.config(function($locationProvider) {
  $locationProvider.html5Mode({enabled:true,requireBase:false}).hashPrefix('!');
});

ngted_app.controller('ngted_zoom_controller', function($rootScope, $scope, $timeout, $http, $location, $timeout, ngted_share) {
	$scope.row = ngted_share.row;
	$scope.tabcode = "";
	$scope.current_link = "";
	$scope.return_link = "";
	$scope.pads_loaded = {};

	$timeout(function(){	
		ngted_will_inject();
		$(".tab-content .tab-pane").each(function(){
			var code = $(this).data("code");
			$(this).load("./?load_tab="+code, function(data){
				$(this).data('loaded', true);
			});
		});
		

	}, 10);
	
	$('.ng-zoom a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		$scope.$apply(function(){
			$scope.tabcode = $(e.target).data('code');
			$scope.activate_pad();
		});
	})
	
	$scope.activate_pad = function(){
		$scope.current_link = $scope.row.pads[$scope.tabcode];
		$location.path($scope.current_link);
		$location.hash("");
		$timeout(function(){
			$rootScope.$broadcast('ngted_pad_'+$scope.tabcode, {row: $scope.row});
		}, 10);
	}
	
	$scope.$on('load_row', function(event, args){
		$scope.tabcode = args.code;
		$http.get("./?json=load&id="+args.id).success(function (response) {
			if(response.code == 'OK'){
				$scope.row = response.row;
				$scope.return_link = $scope.row.backlink;
				$(".ngted-list").css("display", "none");
				$(".ng-zoom").css("display", "block");
				$timeout(function(){
					$scope.activate_pad();
					$timeout(function(){
						$("#ngtab_link_"+$scope.tabcode).click();
					}, 10);
				}, 10);
			}
		});
	});
	
	$scope.$on('update_row', function(event, args){
		$http.get("./?json=load&id="+args.id).success(function (response) {
			if(response.code == 'OK'){
				$scope.row = response.row;
			}
		});
	});
	
	$scope.$on('pad_loaded', function(event, args){
		var padcode = args.pad;
		$scope.pads_loaded[padcode] = true;
	});
	
	$scope.return_to_list = function(){
		$(".ngted-list").css("display", "block");
		$(".ng-zoom").css("display", "none");
		$location.path($scope.return_link);
		$location.hash("");
		$timeout(function(){
			$rootScope.$broadcast('ngted_load_rows', {});
		}, 10);
	};
	
	$scope.check_loaded = function(){
		var loaded = true;
		$(".tab-content .tab-pane").each(function(){
			if(!$(this).data('loaded')){
				if($(this).html().length > 0){
					if(!$scope.pads_loaded[$(this).data('code')]){
						loaded = false;
					}
				}
			}
		});
		if(!loaded){
			$timeout( function(){ $scope.check_loaded(); }, 50);
		}else{
			$rootScope.$broadcast('ngted_everything_loaded', {});
		}
	};

	$scope.reload_row = function(){	
		$http.get("./?json=load&id="+$scope.row.id).success(function (response) {
			if(response.code == 'OK'){
				$scope.row = response.row;
			}
		});
	};
	
	$timeout(function(){	
		$scope.check_loaded();
	}, 50);

});

function ngted_default_tab_scope($rootScope, $scope, $http, $location, $timeout, ngted_share) {
	$scope.saving = false;
	$scope.no_autosave = false;
	$scope.cd_promise = false;

	var that = this;
	
	$scope.check_new_inputs = function(){
		if(typeof $scope.emps_nginputs != "undefined"){
			var emps_ngi_script;
			while($scope.emps_nginputs.length > 0){
				emps_ngi_script = $scope.emps_nginputs.shift();
				emps_ngi_script.call(that, $rootScope, $scope, $http, $location, $timeout);
			}
		}
		$timeout(function(){
			$scope.check_new_inputs();
		}, 500);
	};
	
	$scope.save_changes = function(){
		var row = $scope.row;
		$scope.saving = true;
		$scope.rowForm.$setPristine();
		row['post_save_item'] = row.id;
		$http({
		method  : 'POST',
		url     : './',
		data    : jQuery.param(row),  
		headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  
		})
		.success(function(response) {
			if(response.code == 'OK'){
				$timeout(function(){
					$rootScope.$broadcast('update_row', {id: row.id});
					$scope.saving = false;
				});
			}
		});
	};
	$scope.submit_form = function(){
		$scope.save_changes();
	};
	
	$scope.$watchCollection('rowForm', function(newData, oldData){
		$timeout.cancel($scope.cd_promise);
		$scope.cd_promise = $timeout(function(){
			$scope.check_dirty();	
		}, 5000);
	});
	
	$scope.check_dirty = function(){
		if(typeof $scope.rowForm != "undefined"){
			if($scope.rowForm.$dirty){
				if(!$scope.no_autosave){
					$scope.save_changes();
				}
			}
		}
		$scope.cd_promise = $timeout(function(){	
			$scope.check_dirty();
		}, 5000);
	};

	$scope.cd_promise = $timeout(function(){
		$scope.check_dirty();	
	}, 5000);
	
	$scope.reload_row = function(){	
		if($scope.row !== undefined){
			$http.get("./?json=load&id="+$scope.row.id).success(function (response) {
				if(response.code == 'OK'){
					$scope.row = response.row;
				}
			});
		}
	};
	
	$timeout(function(){
		$scope.check_new_inputs();
	});
};

var ngtedInjectQueueLen = 0;
function ngted_will_inject(){
	ngtedInjectQueueLen = angular.module('ngted_app')._invokeQueue.length;
}

function ngted_inject_new($app, $div){
	// Register the controls/directives/services we just loaded
	var queue = angular.module('ngted_app')._invokeQueue;
	for(var i = ngtedInjectQueueLen; i<queue.length; i++) {
		var call = queue[i];
		// call is in the form [providerName, providerFunc, providerArguments]
		var provider = ngted_providers[call[0]];
		if(provider) {
			// e.g. $controllerProvider.register("Ctrl", function() { ... })
			provider[call[1]].apply(provider, call[2]);
		}
		ngtedInjectQueueLen++;
	}

	angular.element($app).injector().invoke(function($compile, $rootScope) {
		var scope = angular.element($div).scope();
		$compile($div)(scope);
		$rootScope.$apply();
	});
}

ngted_app.directive('ignoreDirty', [function() {
    return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, elm, attrs, ctrl) {
      ctrl.$pristine = false;
    }
  }
}]);

