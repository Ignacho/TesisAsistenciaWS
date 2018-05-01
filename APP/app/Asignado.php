<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Asignado extends Model
{
    protected $table = 'asignados';
    
    protected $fillable = ['id_dictado','id_docente','desc_cargo'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
