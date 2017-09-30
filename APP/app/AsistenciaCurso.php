<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AsistenciaCurso extends Model
{
    protected $table = 'asistencias_cursos';
    
    protected $fillable = ['id_dictado','id_docente','estado_curso'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
