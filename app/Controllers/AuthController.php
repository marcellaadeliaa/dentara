<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;

class AuthController extends Controller {

    public function showLogin(): void {
        if (Auth::check()) {
            redirect(Auth::getDashboardUrl());
        }

        $this->render('auth/login', [
            'title' => 'Masuk ke Sistem DENTARA'
        ], 'layouts/auth');
    }

    public function login(): void {
        if (!verify_csrf()) {
            setFlash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect('login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            setFlash('error', 'Username dan password wajib diisi.');
            redirect('login');
        }

        if (Auth::login($username, $password)) {
            $user = Auth::user();
            setFlash('success', "Selamat datang kembali, {$user['full_name']}!");

            // Cek apakah ada redirect url sebelumnya
            if (!empty($_SESSION['redirect_after_login'])) {
                $target = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $target);
                exit;
            }

            header('Location: ' . Auth::getDashboardUrl());
            exit;
        } else {
            setFlash('error', 'Username atau password salah, atau akun Anda dinonaktifkan.');
            redirect('login');
        }
    }

    public function logout(): void {
        Auth::logout();
        setFlash('success', 'Anda telah berhasil keluar dari sistem DENTARA.');
        redirect('login');
    }

    public function showRegister(): void {
        if (Auth::check()) {
            redirect(Auth::getDashboardUrl());
        }

        $this->render('auth/register', [
            'title' => 'Registrasi Pasien Baru'
        ], 'layouts/auth');
    }

    public function register(): void {
        if (!verify_csrf()) {
            setFlash('error', 'Token keamanan kedaluwarsa.');
            redirect('register');
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $nik      = trim($_POST['nik'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $gender   = $_POST['gender'] ?? 'L';
        $birthDate = $_POST['birth_date'] ?? '';
        $address   = trim($_POST['address'] ?? '');

        // Validasi input
        if (empty($fullName) || empty($nik) || empty($username) || empty($password) || empty($phone) || empty($birthDate) || empty($address)) {
            setFlash('error', 'Semua bidang wajib bertanda bintang (*) harus diisi.');
            redirect('register');
        }

        if (strlen($nik) !== 16 || !ctype_digit($nik)) {
            setFlash('error', 'NIK harus terdiri dari tepat 16 digit angka.');
            redirect('register');
        }

        // Cek username atau email atau NIK ganda
        $existsUser = Database::fetchOne("SELECT id FROM users WHERE username = :u OR email = :e", [
            ':u' => $username,
            ':e' => $email
        ]);
        if ($existsUser) {
            setFlash('error', 'Username atau Email sudah terdaftar dalam sistem.');
            redirect('register');
        }

        $existsNik = Database::fetchOne("SELECT id FROM patients WHERE nik = :nik", [':nik' => $nik]);
        if ($existsNik) {
            setFlash('error', 'NIK sudah terdaftar sebagai pasien. Silakan hubungi resepsionis klinik jika Anda lupa akun.');
            redirect('register');
        }

        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // 1. Buat User
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, phone, role, is_active)
                VALUES (:u, :e, :p, :fn, :ph, 'patient', 1)
            ");
            $stmtUser->execute([
                ':u'  => $username,
                ':e'  => $email,
                ':p'  => $hashedPassword,
                ':fn' => $fullName,
                ':ph' => $phone
            ]);
            $userId = (int)$pdo->lastInsertId();

            // 2. Generate Nomor Rekam Medis Unik (RM-YYYYMM-XXXX)
            $prefix = 'RM-' . date('Ym') . '-';
            $lastRm = Database::fetchOne("SELECT medical_record_number FROM patients WHERE medical_record_number LIKE :pfx ORDER BY id DESC LIMIT 1", [':pfx' => $prefix . '%']);
            $nextSeq = 1;
            if ($lastRm) {
                $lastNum = (int)substr($lastRm['medical_record_number'], -4);
                $nextSeq = $lastNum + 1;
            }
            $rmNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            // 3. Simpan Pasien
            $stmtPatient = $pdo->prepare("
                INSERT INTO patients (user_id, medical_record_number, nik, full_name, gender, birth_date, phone, email, address)
                VALUES (:uid, :rm, :nik, :fn, :gen, :bd, :ph, :em, :addr)
            ");
            $stmtPatient->execute([
                ':uid'  => $userId,
                ':rm'   => $rmNumber,
                ':nik'  => $nik,
                ':fn'   => $fullName,
                ':gen'  => $gender,
                ':bd'   => $birthDate,
                ':ph'   => $phone,
                ':em'   => $email,
                ':addr' => $address
            ]);

            $pdo->commit();

            setFlash('success', "Pendaftaran berhasil! Nomor Rekam Medis Anda adalah {$rmNumber}. Silakan login.");
            redirect('login');

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            redirect('register');
        }
    }
}
