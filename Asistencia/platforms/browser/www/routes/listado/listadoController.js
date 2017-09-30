angular.module('app').controller('listadoController', ['$scope','$http','$routeParams','$rootScope', function ($scope, $http, $routeParams,$rootScope, ngTableParams, $filter){
	$scope.faltas = [
		{id:'0', name:'P'},
		{id:'2', name:'T'},
		{id:'1', name:'A'}
    ];

	$scope.goBack = function()
	{
		$rootScope.materia = '';
		id_doc = $routeParams.id_doc;
		location.hash = "#/materias/"+id_doc;
	}
	$scope.goSave = function(){
		alumnos = $scope.$parent.$$childHead.listado;
		var longitud = alumnos.length;
		$scope.arr = [];
		angular.forEach(alumnos, function(value, key) {
			if(value.view != null)
			$scope.arr.push('{"falta":"'+ value.view + '","id_alumno":"' + value.id_alumno+'"}');
		});
		
		//Validacion que se seleccionen para todos los alumnos su falta.
		if (longitud != $scope.arr.length){
			alert('Por favor seleccione la falta para todos los alumnos');
			return false;
		}	

		result = angular.toJson($scope.arr);
		result = JSON.parse(result);	

		var successBackend = function(data, status, headers, config){
			$scope.getListadoA();
			$scope.listado = '';
			alumnos = null;
			alert('Asistencia Guardada Exitosamente');
			$scope.goBack();
		};
		var errorBackend = function(data, status, headers, config){
		};
	
		d_cur = $routeParams.result;      
		id_doc= $routeParams.id_doc;
		estado_curso = $rootScope.estado_curso;
		$http({ method: 'POST',
		url: 'http://localhost/workspace/APP/public/api/registrarAsistencia',
		data: {'id_curso':id_cur,'id_docente':id_doc,'estado_curso':estado_curso,'data':result,'action':'G'}})
		.success(successBackend) 
		.error(errorBackend); 

	}
	
	$scope.goConfirm = function(){
		alumnos = $scope.$parent.$$childHead.listado;
		$scope.arr = [];
		var longitud = alumnos.length;
		angular.forEach(alumnos, function(value, key) {
			if(value.view != null)
			$scope.arr.push('{"falta":"'+ value.view + '","id_alumno":"' + value.id_alumno+'"}');
		});	
		//Validacion que se seleccionen para todos los alumnos su falta.
		if (longitud != $scope.arr.length){
			alert('Por favor seleccione la falta para todos los alumnos');
			return false;
		}
		result = angular.toJson($scope.arr);
		result = JSON.parse(result);	

		var successBackend = function(data, status, headers, config){
			$scope.getListadoA();
			$scope.listado = '';
			alumnos = null;
			alert('Asistencia Confirmada Exitosamente');
			$scope.goBack();
		};
		var errorBackend = function(data, status, headers, config){
		};
	
		id_cur = $routeParams.result;      
		id_doc= $routeParams.id_doc;
		estado_curso = $rootScope.estado_curso;
		$http({ method: 'POST',
		url: 'http://localhost/workspace/APP/public/api/registrarAsistencia',
		data: {'id_curso':id_cur,'id_docente':id_doc,'estado_curso':estado_curso,'data':result,'action':'C'}})
		.success(successBackend) 
		.error(errorBackend); 

	}
	
	$scope.getListadoA = function(){
		var successBackend = function(data, status, headers, config){
			if (data == 101)
			{
				alert('Todav\u00EDa posee cursos que se encuentran GUARDADOS, sin CONFIRMAR. Por favor, revise esta situaci\u00F3n e intente nuevamente.');
				$scope.goBack();
			}
			else
				if(data == 501)
				{
					$scope.listado = 0;
					$scope.listado.dataI = 501;
					alert('Este curso no tiene alumnos inscriptos. Presione regresar para seleccionar otra materia.');
				}	
				else
				{
					$scope.listado = '';
					$scope.listado = data;
					$scope.listado.materia = $rootScope.materia;
					$scope.listado.dataI = 200;
					$rootScope.estado_curso = data[0].estado_curso;
				}
		};
		var errorBackend = function(data, status, headers, config){
		};
		id_cur = $routeParams.result;
		id_doc = $routeParams.id_doc;
		$http({ method: 'GET',
			url: 'http://localhost/workspace/APP/public/api/inscripto?id_curso='+id_cur+'&id_docente='+id_doc})
			.success(successBackend)
			.error(errorBackend); 
	}
	$scope.getListadoA(); 
}]);
