<?php
header("Content-Type: application/json", true); 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type");
// Handling the Preflight


  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
    exit;
    }
 
 $server = "us-cdbr-iron-east-03.cleardb.net";
 $username = "b950b27eb27af9";
 $password = "dce8e130";
 $database = "heroku_8636d613bba5fb8";
 
 $conn = new mysqli($server, $username, $password, $database);

/* comprueba la conexión */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);
$id_docente= (int) $request->id_docente;


date_default_timezone_set('America/Argentina/Buenos_Aires');

 $year = date("Y");
 $week = date("l");
 $today = date('Y-m-d');
 $dias = array("domingo","lunes","martes","miercoles","jueves","viernes","sabado");

 $dia_semana = $dias[date("w")];


   		//Obtener los cursos actuales del docente logueado.
   		$result = $conn->query ("SELECT mat.Desc_Mat as Materia, dic.Alt_hor as Alternativa, dic.Id_curso as IdC, acu.estado_curso as Estado
			FROM
			    docentes doc
                        INNER JOIN asignados asi ON (
                             doc.Id_docente = asi.DocentesId_docente
                        )
                        INNER JOIN dictados dic ON (
                             asi.DictadosId_curso = dic.id_curso
                        )
                        INNER JOIN materias mat ON (
                             dic.MateriasId_materia = mat.Id_materia
                        )
                        LEFT OUTER JOIN asistencias_cursos acu ON (
                             acu.DictadosId_curso = dic.Id_curso
                        )

		        WHERE
			    doc.Id_docente = $id_docente AND
			    dic.ano = $year AND			    
			    '$today' BETWEEN dic.Finicio AND dic.Ffin AND
                            '$dia_semana' = dic.Diacursada AND
                            (acu.Estado_curso IS NULL OR acu.Estado_curso <> 'C')");
		
   		
   		$numRegistros = $result->num_rows;  // guarda cantidad de registros encontrados en consulta

		if ($numRegistros > 0)
		{
			$arr = array();
		        while ($obj = $result->fetch_assoc()) {
		        					            
			      $array[] = array(
		                'idC' => $obj['IdC'],
		                'Materia' => $obj['Materia'],
		                'Alternativa' => $obj['Alternativa'],
                                'Estado' => $obj['Estado'] 
		       	      );
			 }
		         $arr = json_encode($array);
		         echo $arr;
		}
               else
               {
                  echo json_encode(101);
                 
               }      	     
?>												