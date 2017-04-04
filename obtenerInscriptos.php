<?php
header("Content-Type: application/json", true); 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type");
// Handling the Preflight
 
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
    exit;
}

// base de datos heroku
$server = "us-cdbr-iron-east-03.cleardb.net";         //Informacion del servidor
$username = "b950b27eb27af9";
$password = "dce8e130";
$database = "heroku_8636d613bba5fb8";

// base de datos local
//$server = "localhost";
//$username = "root";
//$password = "";
//$database = "asistencia3";

$year = date("Y");
 $week = date("l");
 $today = date('Y-m-d');

$conn = new mysqli($server, $username, $password, $database);

/* comprueba la conexión */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);
$id_docente = (int) $request->id_docente;
$idcurso = (int) $request->id_curso;
$datos= $conn->query("SELECT asist.DictadosId_Curso as idD FROM asistencias_cursos asist WHERE asist.Estado_curso='G' AND asist.DocentesId_docente = $id_docente");
$cont = $datos->num_rows;
$datos = $datos->fetch_assoc();

if ($cont == 0 || $datos['idD'] == $idcurso)
{
$result = $conn->query("SELECT alu.Apellido apellido, 
	        alu.Nombre nombre, 
		alu.Id_alumno id_alumno, 
		ins.DictadosId_curso id_curso, 
		acu.estado_curso estado_curso,
                IF  (acu.Fecha = '$today',asi.Cod_asist,NULL) as Cod_asist
	 FROM 	
               inscriptos ins
         inner join alumnos alu ON (
              ins.AlumnosId_alumno = alu.Id_alumno
         )
         left outer join asistentes asi ON (
              asi.AlumnosId_alumno = alu.Id_alumno AND
              asi.DictadosId_curso =  $idcurso
         )         
         left outer join asistencias_cursos acu ON (
              acu.DictadosId_curso = ins.DictadosId_curso AND
              acu.Fecha = '$today'
         )
	 WHERE ins.DictadosId_curso = $idcurso");       
$numRegistros = $result->num_rows;  // guarda cantidad de registros encontrados en consulta

if ($numRegistros > 0){  //  si existen registros encontrados
	$arr = array();       // almacena el arreglo de registros
	while ($obj = $result->fetch_assoc()) {	// devuelve el arreglo asociativo de campos del registro y guardo    			
              if ($obj['Cod_asist'] == "0")
                 $obj['cod_falta'] = "P";
              if ($obj['Cod_asist'] == "1")
                $obj['cod_falta'] = "A";
              if ($obj['Cod_asist'] == "2")
                $obj['cod_falta'] = "T";
              $arr[] = $obj;	
	}	
  echo json_encode($arr);    // muestra la lista de registros
}
else 
	echo 501;
}
else
{
         echo 101;
}
$conn->close();
?>				