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
$email = $request->email;
$password = $request->password;
$id = 0;

//Obtener id de usuario logueado.
$result = $conn->query ("SELECT doc.Id_docente 
	FROM usuarios usu
        inner join docentes doc ON (
              usu.Id_usu = doc.UsuariosId_usu
        )          
	WHERE 
            usu.email ='$email' AND 
            usu.password= '$password' AND
	    usu.estado = 1");

$numRegistros = $result->num_rows;  // guarda cantidad de registros encontrados en consulta

if ($numRegistros == 0) {
	die('Error: ' . mysqli_error());
} else {
	$obj = $result->fetch_assoc();
}

 if ($obj != null)
	echo json_encode($obj['Id_docente']);
 else
        echo json_encode(500);
 	 	   	   	     
?>				