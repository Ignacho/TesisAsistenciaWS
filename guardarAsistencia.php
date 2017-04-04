<?php
//Para filtrar o no ,por motivos de Origen
header("Access-Control-Allow-Origin: *");    // para que el recurso HTTP responda a cualquier Origen s/credenciales o cabeceras adic
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // para que el recurso HTTP responda a esos metodos,OPTIONS requiere verif previa
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type"); //si requiere cabeceras adic para que la aplicacion funcione
// Handling the Preflight :(Verificacion previa): Intercambio de Cabeceras entre cliente y servidor
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {  // abortar en caso de solicitudes OPTIONS
	exit;
}

// base de datos heroku
$server = "us-cdbr-iron-east-03.cleardb.net";         //Informacion del servidor
$username = "b950b27eb27af9";
$password = "dce8e130";
$database = "heroku_8636d613bba5fb8";

date_default_timezone_set('Europe/Madrid');

$dia = date("d");
$mes = date("m");
$ano = date("Y");
$fecha_actual =$ano.'-'.$mes.'-'.$dia;

// abre la conexion al servidor MySQL con ese user y pass ,y la guarda en la var con , o aborta con error
$con = mysql_connect($server, $username, $password) or die ("Could not connect: " . mysql_error());  
$dbc = mysql_select_db($database, $con);     // selecciona la base de datos a utilizar y la guarda en var dbc

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);

$id_docente = (int) $request->id_docente;
$estado_curso = $request->estado_curso;
$id_curso = (int) $request->id_curso;

$data = $request->data;

$cant_record = count($data);

$data_res = array();
$data_aux = array();

for ($i=0 ; $i<$cant_record ; $i++)
{
    $data_aux =json_decode($data[$i],true);

    $data_res[$i]["falta"] = json_decode($data_aux["falta"],true);
    $data_res[$i]["id_alumno"] = json_decode($data_aux["id_alumno"],true);
}

if ($estado_curso == 'G'){ //Asistencia previamente guardada.

	//Recorro todos los registros del arreglo de objetos (cantidad alumnos a guardar asistencia)
	for($i = 0; $i < $cant_record; $i++ ) {
		
		$id_alumno = $data_res[$i]['id_alumno'];
		$falta = $data_res[$i]['falta'];
		
		//Obtener el c�digo de asistencia ya guardado, antes de actualizar con el nuevo.
		$sql = "SELECT 	Cod_asist
				FROM 	asistentes asi
				WHERE 	asi.AlumnosId_alumno = $id_alumno
						AND asi.DictadosId_curso =  $id_curso";
		
		$q = mysql_query($sql,$con);
		$v_cod_asist_aux = mysql_fetch_assoc($q);
		$cod_asist_aux = $v_cod_asist_aux['Cod_asist'];
		echo 'Codigo de asistencia anterior($cod_asist_aux)<br>';
		var_dump($cod_asist_aux);
		echo 'Codigo de asistencia actual($falta)<br>';
		var_dump($falta);

		//////////////////////UPDATE asistentes con el nuevo codigo//////////////////////////////////////////////////////		
		$sql = "UPDATE 	asistentes
				SET		cod_asist = '$falta',Fecha_clase = '$fecha_actual'
				WHERE 	DictadosId_curso = $id_curso
						AND AlumnosId_alumno = $id_alumno";
		
		//Actualizo en la DB.
		if (mysql_query($sql,$con)) {
			echo "Registro actualizado exitosamente (asistentes)<br>";
		}else{
			echo "Error al actualizar registro: " . mysql_error($con);
		}

		//Obtener la cantidad de faltas antes de actualizar.
		$sql = "SELECT 	Cant_faltas_act			
				FROM 	inscriptos ins
				WHERE 	ins.AlumnosId_alumno = $id_alumno
						AND ins.DictadosId_curso =  $id_curso";
		
		$q = mysql_query($sql,$con);
		
		$v_cant_faltas_act = mysql_fetch_assoc($q);
		
		//Cantidad de faltas antes de actualizar.
		$v_cant_faltas_act = $v_cant_faltas_act['Cant_faltas_act'];
		
		echo 'Cantidad de faltas antes de actualizar($v_cant_faltas_act)<br>';
		var_dump($v_cant_faltas_act);
		
		//Asignar valor a cantidad de faltas actuales en base al nuevo codigo de asistencia seleccionado.
		if ($cod_asist_aux == '0'){
			switch($falta){
				case '0': 
					$cant_faltas_act = $v_cant_faltas_act;
					break;
				case '1':
					$cant_faltas_act = $v_cant_faltas_act + 1;
					break;
				case '2':
					$cant_faltas_act = $v_cant_faltas_act + 0.5;
					break;					
			}	
		}else if ($cod_asist_aux == '1'){
			switch($falta){
				case '0':
					$cant_faltas_act = $v_cant_faltas_act - 1;
					break;
				case '1':
					$cant_faltas_act = $v_cant_faltas_act;
					break;
				case '2':
					$cant_faltas_act = $v_cant_faltas_act - 0.5;
					break;
			}
		}else if ($cod_asist_aux == '2'){
			switch($falta){
				case '0':
					$cant_faltas_act = $v_cant_faltas_act - 0.5;
					break;
				case '1':
					$cant_faltas_act = $v_cant_faltas_act + 0.5;
					break;
				case '2':
					$cant_faltas_act = $v_cant_faltas_act;
					break;
			}
		} 
		echo 'Faltas actualizadas con la nueva<br>';
		var_dump($cant_faltas_act);
				
		//////////////////////UPDATE faltas en inscriptos//////////////////////////////////////////////////////
		$sql = "UPDATE 	inscriptos
				SET		Cant_faltas_act = '$cant_faltas_act'
				WHERE 	DictadosId_curso = $id_curso
						AND AlumnosId_alumno = $id_alumno";
			
		//Actualizo en la DB.
		if (mysql_query($sql,$con)) {
			echo "Registro actualizado exitosamente (inscriptos)<br>";
		}else{
				echo "Error al actualizar registro: " . mysql_error($con);
		}
		
		echo '-------------------------------------------------------------------------------------<br><br>';
		
	} //Fin FOR		 
}else{ //Asistencia guardada por primera vez.
	
	//Recorro todos los registros del arreglo de objetos (cantidad alumnos a guardar asistencia)
	for($i = 0; $i < $cant_record; $i++ ) {
	
		$id_alumno = $data_res[$i]['id_alumno'];
		$falta = $data_res[$i]['falta'];
	
		//////////////////////INSERT asistentes//////////////////////////////////////////////////////	
		$sql = "INSERT INTO	asistentes (AlumnosId_alumno,DictadosId_curso,Fecha_clase,Cod_asist)
				VALUES ($id_alumno,$id_curso,'$fecha_actual','$falta')";		
	
		//Actualizo en la DB.
		if (mysql_query($sql,$con)) {
			echo "Registro insertado exitosamente(asistentes)";
		}else{
			echo "Error al insertar registro: " . mysql_error($con);
		}

		//Tratamiento de media falta. Para las demas estan OK los valores.
		if ($falta == 2){
			$falta = 0.5;
		}
		
		//Obtener la cantidad de faltas actuales del alumno para incrementarlas.
		$sql = "SELECT 	Cant_faltas_act			
				FROM 	inscriptos ins
				WHERE 	ins.AlumnosId_alumno = $id_alumno
						AND ins.DictadosId_curso =  $id_curso";
		
		$q = mysql_query($sql,$con);
		
		$v_cant_faltas_act = mysql_fetch_assoc($q);
		
		//Cantidad de faltas anteriores + nueva falta.
		$cant_faltas_act = $v_cant_faltas_act['Cant_faltas_act'] + $falta;
		
		var_dump($cant_faltas_act);
				
		//////////////////////UPDATE faltas en inscriptos//////////////////////////////////////////////////////
		$sql = "UPDATE 	inscriptos
				SET		Cant_faltas_act = '$cant_faltas_act'
				WHERE 	DictadosId_curso = $id_curso
						AND AlumnosId_alumno = $id_alumno";
			
		//Actualizo en la DB.
		if (mysql_query($sql,$con)) {
			echo "Registro actualizado exitosamente (inscriptos)";
		}else{
				echo "Error al actualizar registro: " . mysql_error($con);
		}	
		
	}//Fin FOR
	
	//Inserto en asistencias_cursos nuevo estado_curso = 'G'.
	$sql = "INSERT INTO	asistencias_cursos (DictadosId_curso,DocentesId_docente,Estado_curso,Fecha)
	VALUES ($id_curso,$id_docente,'G','$fecha_actual')";
	
	//Actualizo en la DB.
	if (mysql_query($sql,$con)) {
		echo "Registro insertado exitosamente(asistencias_cursos)";
	}else{
		echo "Error al insertar registro: " . mysql_error($con);
	}
}
mysql_close($con);  		  
?>			