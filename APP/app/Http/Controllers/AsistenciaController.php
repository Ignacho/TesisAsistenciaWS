<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;
use App\Docente;
use App\Inscripto;
use App\AsistenciaCurso;
use App\Asistente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class AsistenciaController extends Controller
{

    public function postLogin(Request $request) {
        
        $email = $request->input('email');
        $password = $request->input('password');
        //$password = Crypt::encrypt($request->input('password'));
        //return Crypt::decrypt($password);
        $user = Usuario::join('docentes','usuarios.id','=', 'docentes.id_usuario')
        ->where('email', '=',$email)
        ->where('password','=',$password)
        ->where('estado','=',1)
        ->select('docentes.id AS id_docente')
        ->get();

        //Si no existe el usuario ya sea por email o psw incorrectos devuelvo 500.    
        if (!$user->count()){
                return 500;
        }else{
                return $user;
        }    

    }
    
    public function getMaterias(Request $request) {
        
        $id_docente = $request->input('id_docente');
        $dias = array("domingo","lunes","martes","miercoles","jueves","viernes","sabado");
        $dia_semana = $dias[date("w")];
        $today = gmDate("Y-m-d");

        $obj = Docente::join('asignados','docentes.id','=', 'asignados.id_docente')
        ->join('dictados','asignados.id_dictado','=','dictados.id')
        ->join('materias','dictados.id_materia','=','materias.id')

        ->where('docentes.id', '=',$id_docente)
        ->where('dictados.dia_cursada','=',$dia_semana)
        ->where('dictados.ano','=',date("Y"))
        ->where('dictados.fecha_fin','>=',$today)
        ->where('dictados.fecha_inicio','<=', $today)
        
        ->select('materias.desc_mat AS Materia','dictados.alt_hor AS Alternativa','dictados.id AS IdC')
		
		->groupBy('materias.desc_mat','dictados.alt_hor','dictados.id')
		
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
						->where('created_at','=',$today)
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

        $today = gmDate("Y-m-d");

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

        ->leftJoin('asistencias_cursos', function ($query) use ($today) {
                $query->on('inscriptos.id_dictado','=','asistencias_cursos.id_dictado')
                      ->Where('asistencias_cursos.created_at','=',$today);
        })

        ->leftJoin('asistentes', function ($query) use ($today) {
                $query->on('alumnos.id','=','asistentes.id_alumno')
                      ->Where('asistentes.created_at','=',$today);
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
		$today = gmDate("Y-m-d");

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
				->where('asistentes.created_at', '=',$today)
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

    public function pepe(Request $request) {
        
        $id_alumno = (int) $request->id_alumno;

        $id_curso = (int) $request->id_curso;

  

                ////Obtener el código de asistencia ya guardado, antes de actualizar con el nuevo.
                $sql = AsistenciaCurso::where('estado_curso','=','C')
                ->select('asistencias_cursos.created_at')
                ->get();
        return $sql;
    }  
}    