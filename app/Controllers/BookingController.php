<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class BookingController extends Controller {

    public function showBookingForm(): void {
        $doctors = Database::fetchAll("
            SELECT d.*, u.full_name 
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.is_active = 1
        ");

        $schedules = Database::fetchAll("
            SELECT s.*, u.full_name as doctor_name
            FROM doctor_schedules s
            JOIN doctors d ON s.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE s.is_active = 1
        ");

        // Jika user pasien sedang login, pre-fill data
        $patientData = null;
        if (Auth::hasRole(ROLE_PATIENT)) {
            $patientData = Database::fetchOne("
                SELECT p.*, u.email as u_email, u.phone as u_phone 
                FROM patients p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :uid LIMIT 1
            ", [':uid' => Auth::id()]);
        }

        $this->render('public/booking', [
            'title'       => 'Pendaftaran Online (Online Booking)',
            'doctors'     => $doctors,
            'schedules'   => $schedules,
            'patientData' => $patientData
        ]);
    }

    public function storeBooking(): void {
        if (!verify_csrf()) {
            setFlash('error', 'Token keamanan kedaluwarsa.');
            redirect('booking');
        }

        $doctorId        = (int)($_POST['doctor_id'] ?? 0);
        $appointmentDate = $_POST['appointment_date'] ?? '';
        $patientName     = trim($_POST['patient_name'] ?? '');
        $patientPhone    = trim($_POST['patient_phone'] ?? '');
        $patientEmail    = trim($_POST['patient_email'] ?? '');
        $complaint       = trim($_POST['complaint'] ?? '');
        $isNewPatient    = (int)($_POST['is_new_patient'] ?? 0);

        if (!$doctorId || empty($appointmentDate) || empty($patientName) || empty($patientPhone) || empty($complaint)) {
            setFlash('error', 'Silakan lengkapi semua kolom yang wajib diisi.');
            redirect('booking');
        }

        if (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
            setFlash('error', 'Tanggal kunjungan tidak boleh di masa lampau.');
            redirect('booking');
        }

        // Cek hari dalam seminggu
        $daysIndo = [
            'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu',
            'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
        ];
        $bookingDay = $daysIndo[date('D', strtotime($appointmentDate))];

        $schedule = Database::fetchOne("
            SELECT * FROM doctor_schedules 
            WHERE doctor_id = :did AND day_of_week = :day AND is_active = 1
            LIMIT 1
        ", [':did' => $doctorId, ':day' => $bookingDay]);

        if (!$schedule) {
            setFlash('error', "Dokter yang dipilih tidak memiliki jadwal praktik pada hari {$bookingDay}. Silakan cek jadwal dokter.");
            redirect('booking');
        }

        // Cek kuota booking hari itu
        $bookedCount = Database::fetchOne("
            SELECT COUNT(*) as cnt FROM appointments 
            WHERE doctor_id = :did AND appointment_date = :adate AND status != 'cancelled'
        ", [':did' => $doctorId, ':adate' => $appointmentDate])['cnt'] ?? 0;

        if ($bookedCount >= $schedule['quota']) {
            setFlash('error', 'Mohon maaf, kuota pendaftaran untuk dokter ini pada tanggal tersebut telah penuh (' . $schedule['quota'] . ' pasien). Silakan pilih tanggal lain.');
            redirect('booking');
        }

        // Generate Booking Code: BK-YYYYMMDD-XXX
        $prefix = 'BK-' . date('Ymd', strtotime($appointmentDate)) . '-';
        $lastBooking = Database::fetchOne("
            SELECT booking_code FROM appointments 
            WHERE booking_code LIKE :pfx 
            ORDER BY id DESC LIMIT 1
        ", [':pfx' => $prefix . '%']);

        $seq = 1;
        if ($lastBooking) {
            $seq = (int)substr($lastBooking['booking_code'], -3) + 1;
        }
        $bookingCode = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

        // Cari atau hubungkan patient_id jika pasien sudah terdaftar
        $patientId = null;
        if (Auth::hasRole(ROLE_PATIENT) && !empty($_SESSION['patient_id'])) {
            $patientId = $_SESSION['patient_id'];
        } else {
            $existing = Database::fetchOne("SELECT id FROM patients WHERE phone = :ph OR email = :em LIMIT 1", [
                ':ph' => $patientPhone,
                ':em' => $patientEmail
            ]);
            if ($existing) {
                $patientId = $existing['id'];
            }
        }

        try {
            Database::query("
                INSERT INTO appointments (
                    booking_code, patient_id, patient_name, patient_phone, patient_email,
                    doctor_id, appointment_date, estimated_time, complaint, is_new_patient, status
                ) VALUES (
                    :code, :pid, :pname, :pphone, :pemail,
                    :did, :adate, :etime, :comp, :isnew, 'confirmed'
                )
            ", [
                ':code'   => $bookingCode,
                ':pid'    => $patientId,
                ':pname'  => $patientName,
                ':pphone' => $patientPhone,
                ':pemail' => $patientEmail,
                ':did'    => $doctorId,
                ':adate'  => $appointmentDate,
                ':etime'  => $schedule['start_time'],
                ':comp'   => $complaint,
                ':isnew'  => $isNewPatient
            ]);

            // Ambil nama dokter untuk tampilan sukses
            $doctorName = Database::fetchOne("SELECT u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = :did", [':did' => $doctorId])['full_name'] ?? 'Dokter';

            $this->render('public/booking_success', [
                'title'           => 'Pendaftaran Online Berhasil',
                'bookingCode'     => $bookingCode,
                'patientName'     => $patientName,
                'doctorName'      => $doctorName,
                'appointmentDate' => $appointmentDate,
                'estimatedTime'   => $schedule['start_time'] . ' - ' . $schedule['end_time'] . ' WIB'
            ]);

        } catch (\Exception $e) {
            setFlash('error', 'Gagal memproses pendaftaran online: ' . $e->getMessage());
            redirect('booking');
        }
    }
}
