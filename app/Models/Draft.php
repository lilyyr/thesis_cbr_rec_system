<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    protected $fillable = [
        'agent_id',
        'customer_name',
        'form_data',
        'last_saved_at',
    ];

    protected $casts = [
        'form_data'     => 'array',
        'last_saved_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
