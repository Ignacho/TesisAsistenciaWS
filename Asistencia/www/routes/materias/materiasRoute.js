angular.module('app').config(function ($routeProvider){
	$routeProvider
		.when('/materias/:id_docente', {
		    templateUrl: 'routes/materias/materias.html',    
		    controller: 'materiasController'
		})
  });
