<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_name',
        'specialization_id',
        'years_of_experience',
        'consultation_fee',
        'qualifications',
    ];

    public function availableDays()
    {
        return $this->hasMany(DoctorAvailableDay::class);
    }
    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
}
