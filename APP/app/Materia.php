<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';
    
    protected $fillable = ['id_carrera','desc_mat'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
