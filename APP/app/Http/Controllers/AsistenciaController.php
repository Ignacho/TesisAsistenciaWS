<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Usuario;
use App\Docente;
use App\Inscripto;
use App\AsistenciaCurso;
use App\DictadoClase;
use App\Asistente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
//date_default_timezone_set('America/Argentina/Buenos_Aires');

class AsistenciaController extends Controller
{

    public function postLogin(Request $request) {
        
        $email = $request->input('email');
        $password = $request->input('password');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://caeceasistencia.com/api/authenticate");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"email=".$email."&password=".$password."&isDocente=1");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$token = curl_exec ($ch);
		curl_close ($ch);
		$formattedToken = json_decode($token);

		//Error al autenticar credenciales.	
		if(isset($formattedToken->error)){
			return 500;
		}else{
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"http://caeceasistencia.com/api/identity?token=".$formattedToken->token);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$userData = curl_exec ($ch);
			curl_close ($ch);
			$userInfo = json_decode($userData);
			$id_usuario = $userInfo->id;
			
			//Error al autenticar token.
			if(isset($formattedToken->error)){
				return 500;
			}else{	
				$user = Usuario::join('docentes','usuarios.id','=', 'docentes.id_usuario')
				->join('permisos','usuarios.id_permiso','=','permisos.id')
				->where('usuarios.id', '=',$id_usuario)
				->where('usuarios.estado','=',1)//Activo
				->where('permisos.id','=',2)//Docente
				->select('docentes.id AS id_docente')
				->get(); 
				
				return $user;
			}	
		}    

    }
    
    public function getMaterias(Request $request) {
        $id_docente = $request->input('id_docente');
        $dias = array("domingo","lunes","martes","miercoles","jueves","viernes","sabado");
        $dia_semana = $dias[date("w")];
        $today = date("Y-m-d");

        $obj = Docente::join('asignados','docentes.id','=', 'asignados.id_docente')
        ->join('dictados','asignados.id_dictado','=','dictados.id')
        ->join('materias','dictados.id_materia','=','materias.id')
		->join('dictados_clases','dictados.id','=','dictados_clases.id_dictado')
		->join('dias','dictados_clases.id_dia','=','dias.id')
		->join('alternativas','dictados_clases.id_alternativa','=','alternativas.id')

        ->where('docentes.id', '=',$id_docente)
        ->where('dias.descripcion','=',$dia_semana)
        ->where('dictados.ano','=',date("Y"))
        ->where('dictados.fecha_fin','>=',$today)
        ->where('dictados.fecha_inicio','<=', $today)
        
		->select('materias.desc_mat AS Materia','alternativas.codigo AS Alternativa','dictados.id AS IdC')
		
		//->groupBy('materias.desc_mat','alternativas.codigo','dictados.id')
		
        ->orderBy('materias.desc_mat')

        ->get();
		
		$data = [];
        $i=0;
		
		/*Recorro las materias que se corresponden al día actual*/
        foreach ($obj as $result) {    

			/*Verifico si por cada materia posee la asistencia Guardada (solo será la del día actual ya que el JOB al finalizar el día Confirma todas las asistencias)*/
			$objMat = AsistenciaCurso::where('id_dictado','=',$result->IdC)
					->where('estado_curso','=','G')
					->select('id_dictado')
					->get();

			/*Si encuentra una asistencia Guardada devuelve dicho estado*/					
			if ($objMat->count()){
				$result->Estado = "G";
				$data[$i] = $result;
				$i++;
			}else{/*Si no hay asistencias Guardadas, verifico que no haya una Confirmada para el día actual*/
				$objMat = AsistenciaCurso::where('id_dictado','=',$result->IdC)
						->where('estado_curso','=','C')
						->whereDate('created_at','=',$today)
						->select('id_dictado')
						->get();
				
				/*Si no hay ninguna Guardada la informo en el listado de materias*/
				if (!$objMat->count()){
					$result->Estado = "";
					$data[$i] = $result;
					$i++;
				}
			}
        }

        return $data;
    }

    public function getInscriptos(Request $request) {
        $id_curso = $request->input('id_curso');
        $id_docente = $request->input('id_docente');
        $today = date("Y-m-d");

        //INICIO DE VALIDACIÓN "Cursos guardados sin confirmar".
        $obj1 = AsistenciaCurso::where('estado_curso','=','G')
                ->where('id_docente','=',$id_docente)
                ->select('id_dictado')
                ->get();


        foreach ($obj1 as $result) {
            if ($result->id_dictado != $id_curso){
                return 101;
            }
        }        
        //FIN DE VALIDACIÓN "Cursos guardados sin confirmar".

        
        $obj2 = Inscripto::join('alumnos','inscriptos.id_alumno','=','alumnos.id')           
        ->join('dictados','inscriptos.id_dictado','=','dictados.id')
		->leftJoin('asistentes', function ($query) use ($today) {
                $query->on('inscriptos.id_alumno','=','asistentes.id_alumno')
					  ->on('inscriptos.id_dictado','=','asistentes.id_dictado')
					  ->whereDate('asistentes.created_at','=',$today);;
        })
		->leftJoin('asistencias_cursos', function ($query) use ($today) {
                $query->on('dictados.id','=','asistencias_cursos.id_dictado')
                      ->whereDate('asistencias_cursos.created_at','=',$today);
        })        	

        ->where('inscriptos.id_dictado','=', $id_curso)
        
        ->select('alumnos.nombre','alumnos.apellido','alumnos.id AS id_alumno','inscriptos.id_dictado AS id_curso','asistencias_cursos.estado_curso','asistentes.cod_asist','inscriptos.libre')

        ->orderBy('alumnos.apellido')

        ->get();

        //Si la materia no tiene incriptos devuelvo 501.    
        if (!$obj2->count()){
            return 501;
        }

        $data = [];
        $i=0;

        foreach ($obj2 as $result) {    

            if ($result->cod_asist == "0"){
                $result->cod_falta = "P";
            }else if ($result->cod_asist == "1"){
                $result->cod_falta = "A";
            }else if ($result->cod_asist == "2"){    
                $result->cod_falta = "T";
            }    
            
            $data[$i] = $result;
            $i++;
        }

        return $data;
    }



    public function setRegistrarAsistencia(Request $request) {

        $id_docente = (int) $request->id_docente;
        $estado_curso = $request->estado_curso;
        $id_curso = (int) $request->id_curso;
        $action = $request->action;
		$today = date("Y-m-d");

        $data = $request->data;
        
        //Si están todos los alumnos libres no se tiene que registrar la asistencia.
        if ($data==null){
            return 500;
        }        

        $cant_record = count($data);

        for ($i=0 ; $i<$cant_record ; $i++){
            $data_aux =json_decode($data[$i],true);

            $data_res[$i]["falta"] = json_decode($data_aux["falta"],true);
            $data_res[$i]["id_alumno"] = json_decode($data_aux["id_alumno"],true);
        }


        if ($estado_curso == 'G'){//Asistencia previamente guardada.

            //Recorro todos los registros del arreglo de objetos (cantidad alumnos a guardar asistencia).
            foreach ($data_res as $record) {
                
                $id_alumno = $record['id_alumno'];
                $falta = $record['falta'];   

                ////Obtener el código de asistencia ya guardado, antes de actualizar con el nuevo.
                $sql = Asistente::where('asistentes.id_dictado', '=',$id_curso)
                ->where('asistentes.id_alumno', '=',$id_alumno)
				->whereDate('asistentes.created_at', '=',$today)
                ->select('asistentes.cod_asist','asistentes.id AS id_asistente')
                ->get();

                foreach ($sql as $result) {

                    //Obtengo el código anterior para usarlo después.
                    $cod_asist_aux = $result->cod_asist;
                    
                    //UPDATE asistentes con el nuevo código.
                    $asistente = Asistente::find($result->id_asistente);
                    $asistente->cod_asist = $falta;
                    $asistente->save();
                }
                    
                //Obtener la cantidad de faltas antes de actualizar.
                $sql = Inscripto::join('dictados','inscriptos.id_dictado','=','dictados.id')
                ->where('inscriptos.id_dictado', '=',$id_curso)                
                ->where('inscriptos.id_alumno', '=',$id_alumno)
                ->select('inscriptos.cant_faltas_act','inscriptos.id AS id_inscripto','dictados.cant_faltas_max')
                ->get();   

                foreach ($sql as $result) {
                    $cant_faltas_aux = $result->cant_faltas_act;
                    $id_inscripto = $result->id_inscripto;
                    $cant_faltas_max = $result->cant_faltas_max;
                }    

                //Asignar valor a cantidad de faltas actuales en base al nuevo codigo de asistencia seleccionado.
                if ($cod_asist_aux == '0'){
                    switch($falta){
                        case '0': 
                            $cant_faltas_act = $cant_faltas_aux;
                            break;
                        case '1':
                            $cant_faltas_act = $cant_faltas_aux + 1;
                            break;
                        case '2':
                            $cant_faltas_act = $cant_faltas_aux + 0.5;
                            break;                  
                    }   
                }else if ($cod_asist_aux == '1'){
                    switch($falta){
                        case '0':
                            $cant_faltas_act = $cant_faltas_aux - 1;
                            break;
                        case '1':
                            $cant_faltas_act = $cant_faltas_aux;
                            break;
                        case '2':
                            $cant_faltas_act = $cant_faltas_aux - 0.5;
                            break;
                    }
                }else if ($cod_asist_aux == '2'){
                    switch($falta){
                        case '0':
                            $cant_faltas_act = $cant_faltas_aux - 0.5;
                            break;
                        case '1':
                            $cant_faltas_act = $cant_faltas_aux + 0.5;
                            break;
                        case '2':
                            $cant_faltas_act = $cant_faltas_aux;
                            break;
                    }
                }

                //Actualizar faltas en inscriptos.
                $ins = Inscripto::find($id_inscripto);
                $ins->cant_faltas_act = $cant_faltas_act;
                $ins->save(); 
                $faltaUPD = $ins->cant_faltas_act;

                //Se presionó en Confirmar asistencia. Se verifica si el alumno quedó libre.   
                if ($action == 'C'){
                    if ($faltaUPD > $cant_faltas_max){
                        
                        //Actualizo el alumno como libre.
                        $inscripto = Inscripto::find($result->id_inscripto);
                        $inscripto->libre = 'T';
                        $inscripto->save();
                    }
                }
            }

            //Se presionó en Confirmar asistencia.    
            if ($action == 'C'){

                //Actualizo en asistencias_cursos nuevo estado_curso = 'C'.
                $asis_curso = AsistenciaCurso::where('id_dictado', $id_curso)
                ->where('id_docente', $id_docente)
                ->update(['estado_curso' => $action]);
            }   

        }else{//Asistencia guardada ó confirmada por primera vez.

             //Recorro todos los registros del arreglo de objetos (cantidad alumnos a guardar asistencia).
            foreach ($data_res as $record) {
                
                $id_alumno = $record['id_alumno'];
                $falta = $record['falta'];   

                //INSERT asistentes
                $asistente = new Asistente;
                $asistente->id_alumno = $id_alumno;
                $asistente->id_dictado = $id_curso;
                $asistente->cod_asist = $falta; 
                $asistente->save();
              
                //Tratamiento de media falta. Para las demas estan OK los valores.
                if ($falta == 2){
                    $falta = 0.5;
                }

                //Obtener la cantidad de faltas actuales del alumno para incrementarlas.
                $sql = Inscripto::join('dictados','inscriptos.id_dictado','=','dictados.id')
                ->where('inscriptos.id_dictado', '=',$id_curso)                
                ->where('inscriptos.id_alumno', '=',$id_alumno)
                ->select('inscriptos.cant_faltas_act','inscriptos.id AS id_inscripto','dictados.cant_faltas_max')
                ->get(); 

                foreach ($sql as $result) {
                        
                    //UPDATE faltas en inscriptos.
                    $inscripto = Inscripto::find($result->id_inscripto);
                    $inscripto->cant_faltas_act =  $result->cant_faltas_act + $falta;
                    $inscripto->save();
                    $faltaUPD = $inscripto->cant_faltas_act;

                    //Se presionó en Confirmar asistencia. Se verifica si el alumno quedó libre.   
                    if ($action == 'C'){
                        if ($faltaUPD > $result->cant_faltas_max){
                            
                            //Actualizo el alumno como libre.
                            $inscripto = Inscripto::find($result->id_inscripto);
                            $inscripto->libre = 'T';
                            $inscripto->save();
                        }
                    }
                }    
            }

            //Inserto en asistencias_cursos el estado correspondiente a la $action realizada.
            $asist_cursos = new AsistenciaCurso;
            $asist_cursos->id_dictado = $id_curso;
            $asist_cursos->id_docente = $id_docente;
            $asist_cursos->estado_curso = $action; //Guardar ó Confirmar.
            $asist_cursos->save();
        }
    }
	
	public function cantAsistencias(Request $request) {
        
		$id_carrera = 1;//$request->input('id_carrera');
        $id_materia = 22;//$request->input('id_materia');
		$date_from = '2018-01-01';//$request->input('date_from');
		$date_to = '2018-05-20';//$request->input('date_to');
        $i=1;
		
		$result = new \StdClass();
		
		$myObj = new \StdClass();
		$myObj->cols=[];
		$myObj->rows=[];
		
		$myObj->cols[0] = new \StdClass();
		$myObj->cols[0]->id = "insc";
		$myObj->cols[0]->label = "Inscriptos";
		$myObj->cols[0]->type = "string";
		
		/*PRESENTES*/
		$presentes = Asistente::join('dictados','asistentes.id_dictado','=','dictados.id')
			->join('materias','dictados.id_materia','=','materias.id')		
			->where('materias.id_carrera', '=',$id_carrera)
			->where('materias.id', '=',$id_materia)
			->where('asistentes.cod_asist', '=','0')
			->whereDate('asistentes.created_at', '>=',$date_from)
			->whereDate('asistentes.created_at', '<=',$date_to)
			->select(DB::raw('count(1) AS total'))
			->groupBy('asistentes.cod_asist')				
			->get();
		
		//Si no devuelve registros...
		if (!$presentes->count()){
			$myObj->rows[0]->c[0] = new \StdClass();
			$myObj->rows[0]->c[0]->v = "Presentes";
			$myObj->rows[0]->c[1] = new \StdClass();
			$myObj->rows[0]->c[1]->v = 0;	
		}else{
			$myObj->rows[0]->c[0] = new \StdClass();			
			$myObj->rows[0]->c[0]->v = "Presentes";
			$myObj->rows[0]->c[1] = new \StdClass();
			$myObj->rows[0]->c[1]->v = $presentes[0]->total;
		}
		
		/*AUSENTES*/
		$ausentes = Asistente::join('dictados','asistentes.id_dictado','=','dictados.id')
			->join('materias','dictados.id_materia','=','materias.id')		
			->where('materias.id_carrera', '=',$id_carrera)
			->where('materias.id', '=',$id_materia)
			->where('asistentes.cod_asist', '=','1')
			->whereDate('asistentes.created_at', '>=',$date_from)
			->whereDate('asistentes.created_at', '<=',$date_to)
			->select(DB::raw('count(1) AS total'))
			->groupBy('asistentes.cod_asist')	
			->get();

		if (!$ausentes->count()){
			$myObj->rows[1]->c[0] = new \StdClass();
			$myObj->rows[1]->c[0]->v = "Ausentes";
			$myObj->rows[1]->c[1] = new \StdClass();
			$myObj->rows[1]->c[1]->v = 0;	
		}else{
			$myObj->rows[1]->c[0] = new \StdClass();			
			$myObj->rows[1]->c[0]->v = "Ausentes";
			$myObj->rows[1]->c[1] = new \StdClass();
			$myObj->rows[1]->c[1]->v = $ausentes[0]->total;
		}
	
		/*MEDIA FALTA*/
		$media = Asistente::join('dictados','asistentes.id_dictado','=','dictados.id')
			->join('materias','dictados.id_materia','=','materias.id')		
			->where('materias.id_carrera', '=',$id_carrera)
			->where('materias.id', '=',$id_materia)
			->where('asistentes.cod_asist', '=','2')
			->whereDate('asistentes.created_at', '>=',$date_from)
			->whereDate('asistentes.created_at', '<=',$date_to)
			->select(DB::raw('count(1) AS total'))
			->groupBy('asistentes.cod_asist')	
			->get();

		if (!$media->count()){
			$myObj->rows[2]->c[0] = new \StdClass();			
			$myObj->rows[2]->c[0]->v = "Media Falta";
			$myObj->rows[2]->c[1] = new \StdClass();
			$myObj->rows[2]->c[1]->v = 0;		
		}else{
			$myObj->rows[2]->c[0] = new \StdClass();
			$myObj->rows[2]->c[0]->v = "Media Falta";
			$myObj->rows[2]->c[1] = new \StdClass();
			$myObj->rows[2]->c[1]->v = $media[0]->total;
		}

		$result->inscriptos = $myObj;
		return	$result;
    }   
	
	public function cantInscriptos(Request $request) {
        
		$id_carrera = $request->input('id_carrera');
        $id_materia = $request->input('id_materia');
		$ano = $request->input('ano');
		$cuat = $request->input('cuat');
        $i=1;


		$result[0] = ['Materia','total'];

		/*SIN MATERIA SELECCIONADA*/
		if($id_materia == ""){
			echo "vacio";
			$sql = Inscripto::join('dictados','inscriptos.id_dictado','=','dictados.id')
				->join('materias','dictados.id_materia','=','materias.id')
				->where('materias.id_carrera', '=',$id_carrera)
				->where('dictados.ano', '=',$ano)
				->where('dictados.cuat', '=',$cuat)
				->select('materias.desc_mat', DB::raw('count(1) AS total'))
				->groupBy('materias.desc_mat')				
				->get();

			foreach ($sql as $key => $value) {
				$result[$i] = [$value->desc_mat, $value->total];
				$i++;
			}
		
		/*CON MATERIA SELECCIONADA*/		
		}else{
			$sql = Inscripto::join('dictados','inscriptos.id_dictado','=','dictados.id')
				->join('materias','dictados.id_materia','=','materias.id')
				->where('materias.id', '=',$id_materia)
				->where('materias.id_carrera', '=',$id_carrera)
				->where('dictados.ano', '=',$ano)
				->where('dictados.cuat', '=',$cuat)
				->select('materias.desc_mat', DB::raw('count(1) AS total'))
				->groupBy('materias.desc_mat')				
				->get();

			foreach ($sql as $key => $value) {
				$result[$i] = [$value->desc_mat, $value->total];
				$i++;
			}
			
		}

		return $result;
    }
	
	public function cantAlumnosLibres(Request $request) {
        
		$id_carrera = $request->input('id_carrera');
        $ano = $request->input('ano');
		$cuat = $request->input('cuat');
        $i=1;

		$result[0] = ['Materia','totalLibres'];

		$sql = Inscripto::join('dictados','inscriptos.id_dictado','=','dictados.id')
			->join('materias','dictados.id_materia','=','materias.id')
			->where('dictados.ano', '=',$ano)
			->where('dictados.cuat', '=',$cuat)
			->where('inscriptos.libre','=','T')
			->select('materias.desc_mat', DB::raw('count(1) AS total'))
			->groupBy('materias.desc_mat')				
			->get();

		foreach ($sql as $key => $value) {
			$result[$i] = [$value->desc_mat, $value->total];
			$i++;
		}
		
		return $result;
    }

     
}    