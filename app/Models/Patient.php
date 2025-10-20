<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // ✅ Use Authenticatable instead of Model
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'profile_img',
        'age',
        'gender',
        'email',
        'phone_number',
        'address',
        'password',
        'is_archived',
    ];

    protected $hidden = [
        'password',
    ];

    // 🩺 Relationships
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
