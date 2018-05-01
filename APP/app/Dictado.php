<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dictado extends Model
{
    protected $table = 'dictados';
    
    protected $fillable = ['id_materia','ano','cant_insc_act','cant_clases','fecha_inicio','feccha_fin'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
