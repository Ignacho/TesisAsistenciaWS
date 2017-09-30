<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'docentes';
    
    protected $fillable = ['id_usuario','nombre','apellido','telefono'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
