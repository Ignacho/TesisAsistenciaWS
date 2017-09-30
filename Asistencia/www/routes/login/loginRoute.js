angular.module('app').config(function ($routeProvider){
	$routeProvider
		.when('/',{
			templateUrl: 'routes/login/login.html',
			controller: 'loginController'
		})
});
