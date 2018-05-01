<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Alternativa extends Model
{
    protected $table = 'alternativas';
    
    protected $fillable = ['codigo','descripcion'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
