<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AsistenciaCurso;

class UpdateAsistencia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:asistencia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job que actualiza la asistencia';

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
        $this->info('Inicio Actualización Asistencias NO Confirmadas...');
		//Actualizo las Asistencias no Confimadas al cierre del día.
		/*$ConfirmAsis = AsistenciaCurso::where('estado_curso', 'G')
					 ->update('estado_curso', 'C');*/
		$asis_curso = AsistenciaCurso::where('estado_curso', 'G')
					->update(['estado_curso' => 'C']);		 
		$this->info('Fin Actualización Asistencias NO Confirmadas...');					
    }
}
