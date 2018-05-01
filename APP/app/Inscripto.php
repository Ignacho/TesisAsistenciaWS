<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inscripto extends Model
{
    protected $table = 'inscriptos';
    
    protected $fillable = ['id_alumno','id_dictado','cant_faltas_act','libre'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
