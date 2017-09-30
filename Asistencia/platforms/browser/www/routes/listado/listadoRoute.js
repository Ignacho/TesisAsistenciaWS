angular.module('app').config(function ($routeProvider){
	$routeProvider
		.when('/listado/:id_doc/:result',{
			templateUrl: 'routes/listado/listado.html',
			controller: 'listadoController'
		})
  });
