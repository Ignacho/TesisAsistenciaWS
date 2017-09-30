angular.module('app')
.controller('loginController', function ($scope, $http, $location, factorylogin){
	var error = document.getElementById("error");
	error.style.display = "none";
	$scope.login = function(){
		factorylogin.login($http, $scope, $location);		
	};
	$scope.recovery = function(){
		location.hash = "#/recovery";
	};
});

app.factory('factorylogin', function(){
	var factory = {};
	
	factory.login = function($http, $scope, $location){
		var obj = new Object();
		obj.email = $scope.email;
		obj.password = $scope.password;
		var successBackend = function(data, status, headers, config){
			/*$scope.email = "";
			$scope.password = "";*/
			if (data == 500)
			{
				var error = 	document.getElementById("error");
				error.style.display = "block";
			}
			else
			{
				$location.url('/materias/'+data[0].id_docente);	
			}
		};
		var errorBackend = function(data, status, headers, config){
			$scope.password = "";
			var error = document.getElementById("error");
			error.style.display = "block";
		};
			$http({ method: 'POST',
			url: 'http://localhost/workspace/APP/public/api/login',
			data: {'email':obj.email, 'password':obj.password}})
			.success(successBackend)
			.error(errorBackend);
	}
	return factory;
});

app.factory('factorylogout', function(){
	var factory = {};
	factory.logout = function($http, $scope, $location){
		var successBackend = function(data, status, headers, config){
			$location.url('/');
		};
		var errorBackend = function(data, status, headers, config){
			
		};
			$http({ method: 'POST',
			url: location.pathname+"logout",
			data: {}})
			.success(successBackend)
			.error(errorBackend);
	}
	return factory;
});