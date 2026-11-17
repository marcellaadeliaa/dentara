<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class DoctorDashboardController extends Controller {

    public function index(): void {
        $today = date('Y-m-d');
        $currentUser = Auth::user();
        $doctorId = $currentUser['doctor_id'] ?? null;

        // Ambil data antrean untuk dokter ini hari ini
        $queues = [];
        $currentPatient = null;

        if ($doctorId) {
            $queues = Database::fetchAll("
                SELECT q.*, p.medical_record_number, p.full_name as patient_name, p.gender,
                       p.birth_date, p.allergies, p.medical_history, a.complaint
                FROM queues q
                JOIN patients p ON q.patient_id = p.id
                LEFT JOIN appointments a ON q.appointment_id = a.id
                WHERE q.queue_date = :td AND q.doctor_id = :did AND q.status NOT IN ('cancelled', 'completed')
                ORDER BY FIELD(q.status, 'in_consultation', 'calling', 'waiting'), q.id ASC
            ", [':td' => $today, ':did' => $doctorId]);

            // Pasien yang sedang aktif diperiksa
            $currentPatient = Database::fetchOne("
                SELECT q.*, p.id as patient_id, p.medical_record_number, p.full_name as patient_name,
                       p.gender, p.birth_date, p.allergies, p.medical_history, p.phone, a.complaint
                FROM queues q
                JOIN patients p ON q.patient_id = p.id
                LEFT JOIN appointments a ON q.appointment_id = a.id
                WHERE q.queue_date = :td AND q.doctor_id = :did AND q.status IN ('in_consultation', 'calling')
                ORDER BY q.called_at DESC LIMIT 1
            ", [':td' => $today, ':did' => $doctorId]);
        }

        $this->render('doctor/dashboard', [
            'title'          => 'EMR Dokter Gigi - Pemeriksaan Pasien',
            'queues'         => $queues,
            'currentPatient' => $currentPatient,
            'today'          => $today
        ], 'layouts/admin');
    }
}
