<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DropdownController;



// ---------------------
// Specialization Routes
// ---------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



// ---------------------
// Specialization Routes
// ---------------------
Route::get('/getspecializations', [SpecializationController::class, 'getSpecializations']);
Route::get('/getspecializations/{id}', [SpecializationController::class, 'getSpecializationById']);
Route::post('/createspecialization', [SpecializationController::class, 'createSpecialization']);
Route::post('/updatespecialization/{id}', [SpecializationController::class, 'updateSpecialization']);
Route::post('/deletespecialization/{id}', [SpecializationController::class, 'deleteSpecialization']);


// ---------------------
// Doctor Routes
// ---------------------
Route::get('/getdoctors', [DoctorController::class, 'getDoctors']);
Route::get('/getdoctors/{id}', [DoctorController::class, 'getDoctorById']);
Route::post('/createdoctor', [DoctorController::class, 'createDoctor']);
Route::post('/updatedoctor/{id}', [DoctorController::class, 'updateDoctor']);
Route::post('/deletedoctor/{id}', [DoctorController::class, 'deleteDoctor']);
Route::post('approveappointment/{id}', [DoctorController::class, 'approveAppointment']);
Route::post('rejectappointment/{id}', [DoctorController::class, 'rejectAppointment']);


// ---------------------
// Patient Routes
// ---------------------
Route::get('/getpatients', [PatientController::class, 'getPatients']);
Route::get('/getpatients/{id}', [PatientController::class, 'getPatientById']);
Route::post('/createpatient', [PatientController::class, 'createPatient']);
Route::post('/updatepatient/{id}', [PatientController::class, 'updatePatient']);
Route::post('/deletepatient/{id}', [PatientController::class, 'deletePatient']);
Route::get('getmyappointments', [PatientController::class, 'getMyAppointments'])->middleware('auth:sanctum');


// ---------------------
// Appointment Routes
// ---------------------
Route::get('/getappointments', [AppointmentController::class, 'getAppointments']);
Route::get('/getappointments/{id}', [AppointmentController::class, 'getAppointmentById']);
Route::post('/createappointment', [AppointmentController::class, 'createAppointment']);
Route::post('/updateappointment/{id}', [AppointmentController::class, 'updateAppointment']);
Route::post('/deleteappointment/{id}', [AppointmentController::class, 'deleteAppointment']);


// ---------------------
// Medical Record Routes
// ---------------------
Route::get('/getmedicalrecords', [MedicalRecordController::class, 'getMedicalRecords']);
Route::get('/getmedicalrecords/{id}', [MedicalRecordController::class, 'getMedicalRecordById']);
Route::post('/createmedicalrecord', [MedicalRecordController::class, 'createMedicalRecord']);
Route::post('/updatemedicalrecord/{id}', [MedicalRecordController::class, 'updateMedicalRecord']);
Route::post('/deletemedicalrecord/{id}', [MedicalRecordController::class, 'deleteMedicalRecord']);


// ---------------------
// Payment Routes
// ---------------------
Route::get('/getpayments', [PaymentController::class, 'getPayments']);
Route::get('/getpayments/{id}', [PaymentController::class, 'getPaymentById']);
Route::post('/createpayment', [PaymentController::class, 'createPayment']);
Route::post('/updatepayment/{id}', [PaymentController::class, 'updatePayment']);
Route::post('/deletepayment/{id}', [PaymentController::class, 'deletePayment']);

// ---------------------
// Dropdown Routes
// ---------------------
Route::prefix('dropdown')->group(function () {
    Route::get('/getpatients', [DropdownController::class, 'getPatients']);
    Route::get('/getdoctors', [DropdownController::class, 'getDoctors']);
    Route::get('/getspecializations', [DropdownController::class, 'getSpecializations']);
    Route::get('/getappointments', [DropdownController::class, 'getAppointments']);
    Route::get('/getdoctorsbyspecialization/{specializationId}', [DropdownController::class, 'getDoctorsBySpecialization']);
});
