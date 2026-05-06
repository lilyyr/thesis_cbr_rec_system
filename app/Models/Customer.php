<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'gender',
        'dob',
        'occupation',
        'income',
        'num_dependents'
    ];

    protected $casts = [
        'dob' => 'date',
        'income' => 'decimal:2'
    ];

    // Relationship
    public function cases()
    {
        return $this->hasMany(CaseModel::class);
    }

    // Accessor for age
    public function getAgeAttribute()
    {
        return Carbon::parse($this->dob)->age;
    }
}
