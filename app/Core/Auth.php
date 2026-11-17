<?php
namespace App\Core;

use App\Core\Database;

class Auth {
    public static function login(string $username, string $password): bool {
        $sql = "SELECT * FROM users WHERE (username = :u OR email = :e) AND is_active = 1 LIMIT 1";
        $user = Database::fetchOne($sql, [':u' => $username, ':e' => $username]);

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            Database::query("UPDATE users SET last_login = NOW() WHERE id = :id", [':id' => $user['id']]);

            // Jika role adalah doctor, ambil data tambahan dari tabel doctors
            $doctorData = null;
            if ($user['role'] === ROLE_DOCTOR) {
                $doctorData = Database::fetchOne("SELECT * FROM doctors WHERE user_id = :uid LIMIT 1", [':uid' => $user['id']]);
            }

            // Jika role adalah patient, ambil data dari tabel patients
            $patientData = null;
            if ($user['role'] === ROLE_PATIENT) {
                $patientData = Database::fetchOne("SELECT * FROM patients WHERE user_id = :uid LIMIT 1", [':uid' => $user['id']]);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['doctor_id'] = $doctorData['id'] ?? null;
            $_SESSION['patient_id'] = $patientData['id'] ?? null;
            $_SESSION['logged_in_at'] = time();

            return true;
        }

        return false;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (!headers_sent() && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        return [
            'id'         => $_SESSION['user_id'] ?? null,
            'username'   => $_SESSION['username'] ?? null,
            'full_name'  => $_SESSION['full_name'] ?? null,
            'email'      => $_SESSION['email'] ?? null,
            'role'       => $_SESSION['role'] ?? null,
            'doctor_id'  => $_SESSION['doctor_id'] ?? null,
            'patient_id' => $_SESSION['patient_id'] ?? null,
        ];
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION['role'] ?? null;
    }

    public static function hasRole($roles): bool {
        if (!self::check()) {
            return false;
        }
        $currentRole = self::role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return $currentRole === $roles;
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public static function requireRole($roles): void {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            require __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }

    public static function getDashboardUrl(): string {
        $role = self::role();
        switch ($role) {
            case ROLE_ADMIN:
                return BASE_URL . '/admin/dashboard';
            case ROLE_DOCTOR:
                return BASE_URL . '/doctor/dashboard';
            case ROLE_RECEPTIONIST:
                return BASE_URL . '/receptionist/dashboard';
            case ROLE_PHARMACIST:
                return BASE_URL . '/pharmacy/dashboard';
            case ROLE_CASHIER:
                return BASE_URL . '/cashier/dashboard';
            case ROLE_PATIENT:
                return BASE_URL . '/patient/portal';
            default:
                return BASE_URL . '/';
        }
    }
}
