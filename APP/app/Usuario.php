<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    
    protected $fillable = ['nombre','apellido','email','password','permiso','estado'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
