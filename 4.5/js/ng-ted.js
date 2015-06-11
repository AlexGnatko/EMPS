var ngted_app = angular.module('ngted_app', []);
ngted_app.controller('ngted_controller', function($scope, $http, $location) {
	$scope.start = 0;
	$scope.perpage = 25;
	$scope.list = [];
	$scope.pages = [];
	$scope.editmode = "add";
	$scope.row = {};
	
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
	
	if((typeof ngted_custom) != "undefined"){
		$scope = angular.extend($scope, ngted_custom);
	}
	
	$scope.init();
	
})

.config(function($locationProvider) {
  $locationProvider.html5Mode({enabled:true,requireBase:false}).hashPrefix('!');
});
