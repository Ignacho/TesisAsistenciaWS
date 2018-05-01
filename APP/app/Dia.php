<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dia extends Model
{
    protected $table = 'dias';
    
    protected $fillable = ['descripcion'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
