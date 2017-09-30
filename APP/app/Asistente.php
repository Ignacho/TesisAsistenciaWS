<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Asistente extends Model
{
    protected $table = 'asistentes';
    
    protected $fillable = ['id_alumno','id_dictado','fecha_clase','cod_asist'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
