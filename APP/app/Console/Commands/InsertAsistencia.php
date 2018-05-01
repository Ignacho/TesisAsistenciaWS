<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AsistenciaCurso;
use App\DictadoClase;
use App\Asistente;

class InsertAsistencia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:asistencia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job que inserta la asistencia en los casos de que no se haya registrado en el día.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$dias = array("domingo","lunes","martes","miercoles","jueves","viernes","sabado");
        $dia_semana = $dias[date("w")];
		
		//Actualizo las Asistencias no Confimadas al cierre del día.
		$objAsisRegist = AsistenciaCurso::join('dictados_clases','asistencias_cursos.id_dictado','=', 'dictados_clases.id_dictado')
					   ->join('dias','dictados_clases.id_dia','=','dias.id')
					   ->where('dias.descripcion', '=',$dia_semana)
					   ->whereDate('asistencias_cursos.created_at', '=',date("Y-m-d"))
					   ->select('asistencias_cursos.id_dictado')
					   ->get();

		/*Si encuentra la asistencia Registrada esta OK*/									
		if ($objAsisRegist->count()){
			$this->info('Asistencias del día Registradas...');
		/*Si no se registro la asistencia, obtengo los alumnos para insertarlos...*/
		}else{
			$this->info('Inicio Insercción  Asistencias NO Registradas...');	
			
			$objAsisRegist = DictadoClase::join('inscriptos','dictados_clases.id_dictado','=', 'inscriptos.id_dictado')
						   ->join('dictados','dictados_clases.id_dictado','=','dictados.id')
						   ->join('asignados','dictados.id','=','asignados.id_dictado')
				           ->join('dias','dictados_clases.id_dia','=','dias.id')
						   ->where('dias.descripcion', '=',$dia_semana)
						   ->select('inscriptos.id_dictado','inscriptos.id_alumno','asignados.id_docente')
						   ->get();					   
		
			$id_dictado_aux = null;
			foreach ($objAsisRegist as $record) {						
				
				//INSERT asistentes
				$asistente = new Asistente;
				$asistente->id_alumno = $record->id_alumno;
				$asistente->id_dictado = $record->id_dictado;
				$asistente->cod_asist = 0;//Presente 
				$asistente->save();
				
				//Si se trata de otro curso inserto se respectiva asistencia.
				if($id_dictado_aux <> $record->id_dictado){

					//Inserto en asistencias_cursos las asistencia en estado confirmada.
					$asist_cursos = new AsistenciaCurso;
					$asist_cursos->id_dictado = $record->id_dictado;
					$asist_cursos->id_docente = $record->id_docente;
					$asist_cursos->estado_curso = 'C'; //Se registran Confirmadas.
					$asist_cursos->save();
				}

				$id_dictado_aux = $record->id_dictado;
				
			}	
			$this->info('Fin Insercción  Asistencias NO Registradas...');
		}		
    }
}
