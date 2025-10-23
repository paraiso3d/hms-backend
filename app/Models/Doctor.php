<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // 🧠 to allow login
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Doctor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'profile_img',
        'doctor_name',
        'email',
        'password',
        'specialization_id',
        'years_of_experience',
        'consultation_fee',
        'qualifications',
        'about',
        'university_graduated',
        'role',
        'is_archived',
    ];

    protected $hidden = ['password'];

    public function availableDays()
    {
        return $this->hasMany(DoctorAvailableDay::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }
}
