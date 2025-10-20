<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorAvailableDay extends Model
{
    use HasFactory;

    protected $fillable = ['doctor_id', 'day_of_week'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
