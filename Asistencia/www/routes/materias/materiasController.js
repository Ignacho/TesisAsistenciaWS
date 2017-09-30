angular.module('app').directive('ngConfirmClick', [
        function(){
            return {
                priority: 1,
                terminal: true,
                link: function (scope, element, attr) {
                    var msg = attr.ngConfirmClick;
               	 	var clickAction = attr.ngClick;
                    element.bind('click',function (event) {
                        if ( window.confirm(msg) ) {
                            scope.$eval(clickAction)
                        }
                    });
                }
            };
    }])

angular.module('app').controller('materiasController', ['$scope','$http','$routeParams','$rootScope', function ($scope, $http, $routeParams, $rootScope, ngTableParams, factorylistado){
	var getDates = function showDate()
	{
			$scope.date = new Date();
	}

	$scope.actualizar = function()
	{
		window.location.reload();
	}

	$scope.estado = function(Estado)
	{
		if (Estado == 'G')
			return true;
		else
			return false;
	}

	$scope.closesession = function()
	{
		location.hash = "#/";
	}

	$scope.redirect = function(materiaSel)
	{
		location.hash = "#/";
	}
	
	$scope.thirdscreen = function()
	{
		result = $scope.materiasel;
		if (angular.isUndefined(result))
		{
			alert('Seleccione una materia');
		}
		else
		{
			result = JSON.parse(result);
			idC = result["IdC"];
			$rootScope.materia = result["Materia"];
			id_doc = $routeParams.id_docente;
			location.hash = "#/listado/"+id_doc+"/"+idC;
		}
	}

	$scope.getMateriasData = function()
		{
			
			var successBackend = function(data, status, headers, config){
				if (data == 101)
				{
					$scope.materias = 101;
				}
				else
				{
					$scope.materias = data;
				}
			};
			
			var errorBackend = function(data, status, headers, config){

			};
			id_doc = $routeParams.id_docente;
			$http({ method: 'POST',
			url: 'http://localhost/workspace/APP/public/api/materia',
			data: {'id_docente':id_doc}})
			.success(successBackend)
			.error(errorBackend);
		}
	getDates();
	$scope.getMateriasData();
}]);
