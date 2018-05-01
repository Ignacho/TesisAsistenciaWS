<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';
    
    protected $fillable = ['desc_carr','plan'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
