angular.module('app').config(function ($routeProvider){
	$routeProvider
		.when('/logout',{
			templateUrl: 'route/login/login.html',
			controller: 'loginController'
		})
  });
