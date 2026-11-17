<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class PatientDashboardController extends Controller {

    public function index(): void {
        $userId = Auth::id();

        // Ambil profil data pasien
        $patient = Database::fetchOne("
            SELECT p.*, u.email as u_email, u.phone as u_phone 
            FROM patients p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = :uid LIMIT 1
        ", [':uid' => $userId]);

        $appointments = [];
        $medicalRecords = [];
        $billings = [];

        if ($patient) {
            $patientId = $patient['id'];

            // Riwayat reservasi
            $appointments = Database::fetchAll("
                SELECT a.*, d.room_name, u.full_name as doctor_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE a.patient_id = :pid
                ORDER BY a.appointment_date DESC, a.id DESC LIMIT 5
            ", [':pid' => $patientId]);

            // Riwayat pemeriksaan
            $medicalRecords = Database::fetchAll("
                SELECT mr.*, u.full_name as doctor_name, d.specialization
                FROM medical_records mr
                JOIN doctors d ON mr.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE mr.patient_id = :pid
                ORDER BY mr.record_date DESC LIMIT 5
            ", [':pid' => $patientId]);

            // Riwayat pembayaran
            $billings = Database::fetchAll("
                SELECT b.* 
                FROM billings b
                WHERE b.patient_id = :pid
                ORDER BY b.id DESC LIMIT 5
            ", [':pid' => $patientId]);
        }

        $this->render('patient/dashboard', [
            'title'          => 'Portal Pasien DENTARA',
            'patient'        => $patient,
            'appointments'   => $appointments,
            'medicalRecords' => $medicalRecords,
            'billings'       => $billings
        ], 'layouts/admin');
    }
}
