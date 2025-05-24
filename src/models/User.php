<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $password, $email, $full_name, $role_name = 'Employee') {
        $password_hash = password_hash($password, PASSWORD_ARGON2ID); // Or PASSWORD_BCRYPT

        // Get role_id from role_name
        $stmt = $this->pdo->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $stmt->execute([$role_name]);
        $role = $stmt->fetch();
        if (!$role) {
            // This case should ideally not happen if DB is seeded correctly
            // Fallback or error handling:
            // Option 1: Try to find ANY role (e.g., the one with the smallest ID)
            // Option 2: Throw a more specific error
            // For now, let's assume 'Employee' role MUST exist.
            $stmt_employee_role = $this->pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'Employee'");
            $stmt_employee_role->execute();
            $employee_role = $stmt_employee_role->fetch();
            if(!$employee_role) {
                 throw new Exception("Default role 'Employee' not found. Please ensure database is seeded correctly.");
            }
            $role_id = $employee_role['role_id'];
        } else {
            $role_id = $role['role_id'];
        }


        $sql = "INSERT INTO users (username, password_hash, email, full_name, role_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$username, $password_hash, $email, $full_name, $role_id]);
        } catch (PDOException $e) {
            // Handle duplicate entry, etc.
            if ($e->getCode() == 23000) { // Integrity constraint violation
                if (strpos($e->getMessage(), 'username') !== false) {
                    throw new Exception("Username already exists.");
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    throw new Exception("Email already exists.");
                }
            }
            throw $e; // Re-throw other PDO exceptions
        }
    }

    public function login($username, $password) {
        $sql = "SELECT user_id, username, password_hash, email, full_name, role_id, is_active FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                return ['error' => 'Account is deactivated.'];
            }
            // Password matches, return user data (excluding password hash)
            unset($user['password_hash']);
            return $user;
        }
        return false; // Login failed
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT user_id, username, email, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findById($user_id) {
        $stmt = $this->pdo->prepare("SELECT user_id, username, email, full_name, role_id, is_active FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    public function createPasswordResetToken($user_id) {
        // Ensure users table has password_reset_token (VARCHAR 255, nullable) and password_reset_expires (TIMESTAMP, nullable)
        // If not, these ALTER TABLE statements would be needed (run once manually or via migration)
        // ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL DEFAULT NULL;
        // ALTER TABLE users ADD COLUMN password_reset_expires TIMESTAMP NULL DEFAULT NULL;
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour

        $sql = "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$token, $expires, $user_id])) {
            return $token;
        }
        return false;
    }

    public function verifyPasswordResetToken($token) {
        $sql = "SELECT user_id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function resetPassword($token, $new_password) {
        $user = $this->verifyPasswordResetToken($token);
        if (!$user) {
            return false; // Invalid or expired token
        }
        $password_hash = password_hash($new_password, PASSWORD_ARGON2ID);
        $sql = "UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$password_hash, $user['user_id']]);
    }

    public function updateProfile($user_id, $full_name, $email) {
        // Check if email is being changed and if it's unique
        $currentUser = $this->findById($user_id);
        if (!$currentUser) {
            throw new Exception("User not found.");
        }
        if ($currentUser['email'] !== $email) {
            $existingUser = $this->findByEmail($email);
            if ($existingUser && $existingUser['user_id'] != $user_id) {
                throw new Exception("Email address is already in use by another account.");
            }
        }

        $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$full_name, $email, $user_id]);
    }
    
    public function changePassword($user_id, $current_password, $new_password) {
        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password_hash'])) {
            $new_password_hash = password_hash($new_password, PASSWORD_ARGON2ID);
            $sql_update = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $stmt_update = $this->pdo->prepare($sql_update);
            return $stmt_update->execute([$new_password_hash, $user_id]);
        }
        return false; // Current password did not match
    }

    public function getUserRole($user_id) {
        $sql = "SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['role_name'] : null;
    }
    
    // Admin functions
    public function getAllUsers() {
        // For admin panel - consider pagination for large user sets
        $stmt = $this->pdo->query("SELECT u.user_id, u.username, u.email, u.full_name, r.role_name, u.is_active, u.created_at FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id ASC");
        return $stmt->fetchAll();
    }

    public function updateUserByAdmin($user_id, $username, $email, $full_name, $role_id, $is_active) {
         // Check if email is being changed and if it's unique
        $currentUser = $this->findById($user_id);
        if (!$currentUser) {
            throw new Exception("User not found for update.");
        }

        if ($currentUser['email'] !== $email) {
            $existingUser = $this->findByEmail($email);
            if ($existingUser && $existingUser['user_id'] != $user_id) {
                throw new Exception("Email address is already in use by another account.");
            }
        }
        // Check if username is being changed and if it's unique
        if ($currentUser['username'] !== $username) {
             $stmt_check = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
             $stmt_check->execute([$username, $user_id]);
             if($stmt_check->fetch()){
                 throw new Exception("Username already exists.");
             }
        }

        $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role_id = ?, is_active = ? WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$username, $email, $full_name, $role_id, (bool)$is_active, $user_id]);
    }
    
    public function getAllRoles() {
        $stmt = $this->pdo->query("SELECT role_id, role_name FROM roles");
        return $stmt->fetchAll();
    }

}
?>
