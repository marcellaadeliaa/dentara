<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ReceptionistDashboardController extends Controller {

    public function index(): void {
        $today = date('Y-m-d');

        // Antrean hari ini
        $queues = Database::fetchAll("
            SELECT q.*, p.medical_record_number, p.full_name as patient_name, p.phone as patient_phone,
                   d.room_name, u.full_name as doctor_name
            FROM queues q
            JOIN patients p ON q.patient_id = p.id
            JOIN doctors d ON q.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE q.queue_date = :td
            ORDER BY q.id ASC
        ", [':td' => $today]);

        // Booking online yang butuh konfirmasi / check-in
        $appointments = Database::fetchAll("
            SELECT a.*, d.room_name, u.full_name as doctor_name
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE a.appointment_date = :td AND a.status IN ('confirmed', 'pending')
            ORDER BY a.id ASC
        ", [':td' => $today]);

        // Dokter aktif
        $doctors = Database::fetchAll("
            SELECT d.id, d.room_name, u.full_name as doctor_name
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_active = 1
        ");

        $this->render('receptionist/dashboard', [
            'title'        => 'Front Desk - Pemantauan & Panggilan Antrean',
            'queues'       => $queues,
            'appointments' => $appointments,
            'doctors'      => $doctors,
            'today'        => $today
        ], 'layouts/admin');
    }
}
