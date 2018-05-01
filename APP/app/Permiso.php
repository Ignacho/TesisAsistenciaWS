<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';
    
    protected $fillable = ['descripcion'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
