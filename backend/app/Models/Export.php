<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    protected $fillable = ['user_id', 'estado', 'archivo', 'filtros', 'mensaje'];
    protected $casts = ['filtros' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}