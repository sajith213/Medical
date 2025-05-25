<?php
// Assuming PHPUnit is used: vendor/bin/phpunit tests/unit/models/UserTest.php
// require_once dirname(dirname(dirname(__FILE__))) . '/bootstrap.php'; // Adjust path to bootstrap

// For this environment, we assume bootstrap.php is included by the (conceptual) test runner.
// If running standalone: require_once __DIR__ . '/../../bootstrap.php';

// Use PHPUnit\Framework\TestCase; // Would be used if PHPUnit was installed

class UserTest /* extends TestCase */ { // PHPUnit extends TestCase
    private static $pdo;
    private static $userModel;
    private static $testUserIds = []; // To store IDs of users created for tests

    public static function setUpBeforeClass(): void {
        global $pdo; // From bootstrap.php
        if (!$pdo) {
            // Fallback if bootstrap didn't run or $pdo is not global
            // This is not ideal but a fallback for non-PHPUnit environment
            require_once __DIR__ . '/../../bootstrap.php'; 
        }
        self::$pdo = $pdo;
        self::$userModel = new User(self::$pdo);

        // Optional: Create a test user specifically for login tests
        // For simplicity, we might rely on existing users or skip this if DB is pre-populated for tests.
        // Or, create one and store its ID.
        // For this example, we'll assume manual setup or specific test users.
        // Let's try creating one test user for the login test.
        try {
            // Clean up if a previous test run failed
            $stmt_cleanup = self::$pdo->prepare("DELETE FROM users WHERE username LIKE 'testloginuser_%'");
            $stmt_cleanup->execute();
            
            $testUsername = 'testloginuser_' . time();
            $testEmail = $testUsername . '@example.com';
            if (self::$userModel->register($testUsername, 'TestPass123', $testEmail, 'Test Login User')) {
                $stmt = self::$pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                $stmt->execute([$testUsername]);
                $user = $stmt->fetch();
                if ($user) self::$testUserIds['login_user'] = $user['user_id'];
            }
        } catch (Exception $e) {
            // Could not create test user, tests might be unreliable.
            echo "Warning: Could not create test user for UserTest: " . $e->getMessage() . "
";
        }
    }

    public function testLoginSuccess() {
        if (!isset(self::$testUserIds['login_user'])) {
            echo "Skipping testLoginSuccess: Test user not created.
";
            return; // Or mark as skipped in PHPUnit
        }
        // Fetch the username from the database using the stored ID to ensure it's correct
        $userData = self::$userModel->findById(self::$testUserIds['login_user']);
        if(!$userData){
             echo "Skipping testLoginSuccess: Test user data could not be fetched.
";
            return;
        }
        $username = $userData['username'];
        $user = self::$userModel->login($username, 'TestPass123');
        
        // In PHPUnit: $this->assertIsArray($user);
        // $this->assertArrayHasKey('user_id', $user);
        // $this->assertEquals($username, $user['username']);
        if (is_array($user) && isset($user['user_id']) && $user['username'] === $username) {
            echo "testLoginSuccess: PASSED
";
        } else {
            echo "testLoginSuccess: FAILED
";
            print_r($user); // Print actual result for debugging
        }
    }

    public function testLoginWrongPassword() {
         if (!isset(self::$testUserIds['login_user'])) {
            echo "Skipping testLoginWrongPassword: Test user not created.
";
            return; 
        }
        $userData = self::$userModel->findById(self::$testUserIds['login_user']);
        if(!$userData){
             echo "Skipping testLoginWrongPassword: Test user data could not be fetched.
";
            return;
        }
        $username = $userData['username'];
        $user = self::$userModel->login($username, 'WrongPassword123');
        // In PHPUnit: $this->assertFalse($user);
        if ($user === false) {
            echo "testLoginWrongPassword: PASSED
";
        } else {
            echo "testLoginWrongPassword: FAILED
";
        }
    }

    public function testLoginNonExistentUser() {
        $user = self::$userModel->login('nonexistentuser12345', 'anypassword');
        // In PHPUnit: $this->assertFalse($user);
        if ($user === false) {
            echo "testLoginNonExistentUser: PASSED
";
        } else {
            echo "testLoginNonExistentUser: FAILED
";
        }
    }
    
    public static function tearDownAfterClass(): void {
        // Clean up created test users
        if (!empty(self::$testUserIds) && self::$pdo) { // Added self::$pdo check
            try {
                $ids_placeholder = implode(',', array_fill(0, count(self::$testUserIds), '?'));
                $stmt = self::$pdo->prepare("DELETE FROM users WHERE user_id IN ($ids_placeholder)");
                $stmt->execute(array_values(self::$testUserIds));
            } catch (PDOException $e) {
                 echo "Warning: PDOException during UserTest tearDownAfterClass (specific IDs): " . $e->getMessage() . "
";
            }
        }
        // Also clean any user created by username pattern if ID wasn't captured
        if (self::$pdo) { // Added self::$pdo check
            try {
                $stmt_cleanup = self::$pdo->prepare("DELETE FROM users WHERE username LIKE 'testloginuser_%'");
                $stmt_cleanup->execute();
            } catch (PDOException $e) {
                echo "Warning: PDOException during UserTest tearDownAfterClass (pattern cleanup): " . $e->getMessage() . "
";
            }
        }
    }
}
?>
