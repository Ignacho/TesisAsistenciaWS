<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DictadoClase extends Model
{
    protected $table = 'dictados_clases';
    
    protected $fillable = ['id_dictado','id_dia','id_alternatica'];
    
    protected $dates = ['created_at','updated_at','deleted_at'];
}
