<?php
// Use PHPUnit\Framework\TestCase;
// require_once __DIR__ . '/../../bootstrap.php'; // If running standalone

class ClaimTest /* extends TestCase */ {
    private static $pdo;
    private static $claimModel;
    private static $userModel; // For creating a test user
    private static $testUserId;
    private static $createdClaimIds = [];

    public static function setUpBeforeClass(): void {
        global $pdo;
        if (!$pdo) {
            // Fallback if bootstrap didn't run or $pdo is not global
            require_once __DIR__ . '/../../bootstrap.php'; 
        }
        self::$pdo = $pdo;
        self::$claimModel = new Claim(self::$pdo);
        self::$userModel = new User(self::$pdo);

        // Create a temporary user for these tests
        $testUsername = 'claimtestuser_' . time();
        try {
            // Clean up if a previous test run failed
            // Order of deletion matters due to foreign key constraints.
            // 1. Delete claims associated with any old test users
            $stmt_cleanup_claims = self::$pdo->prepare("DELETE FROM claims WHERE user_id IN (SELECT user_id FROM users WHERE username LIKE 'claimtestuser_%')");
            $stmt_cleanup_claims->execute();
            // 2. Delete financial_quotas associated with any old test users
            $stmt_cleanup_quotas = self::$pdo->prepare("DELETE FROM financial_quotas WHERE user_id IN (SELECT user_id FROM users WHERE username LIKE 'claimtestuser_%')");
            $stmt_cleanup_quotas->execute();
             // 3. Delete documents associated with old test claims (if any; documents table has ON DELETE CASCADE for claims)
            // No direct action needed here if ON DELETE CASCADE is reliable.

            // 4. Delete old test users themselves
            $stmt_cleanup_users = self::$pdo->prepare("DELETE FROM users WHERE username LIKE 'claimtestuser_%'");
            $stmt_cleanup_users->execute();
            
            if (self::$userModel->register($testUsername, 'Pass123', $testUsername . '@example.com', 'Claim Test User')) {
                 $stmt = self::$pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                 $stmt->execute([$testUsername]);
                 $user = $stmt->fetch();
                 if ($user) self::$testUserId = $user['user_id'];
            }
        } catch (Exception $e) {
             echo "Warning: Could not create test user for ClaimTest: " . $e->getMessage() . "
";
        }
    }

    public function testCreateClaimSuccess() {
        if (!self::$testUserId) {
            echo "Skipping testCreateClaimSuccess: Test user not available.
";
            return;
        }
        $claimData = [
            'user_id' => self::$testUserId,
            'claim_date' => date('Y-m-d'),
            'description' => 'Test claim for medical expenses.',
            'total_amount' => '150.75'
        ];
        $claim_id = self::$claimModel->createClaim(
            $claimData['user_id'], 
            $claimData['claim_date'], 
            $claimData['description'], 
            $claimData['total_amount']
        );
        
        // In PHPUnit: $this->assertNotFalse($claim_id); $this->assertIsNumeric($claim_id);
        if ($claim_id !== false && is_numeric($claim_id)) {
            echo "testCreateClaimSuccess: PASSED (Claim ID: $claim_id)
";
            self::$createdClaimIds[] = $claim_id; // Store for cleanup
        } else {
            echo "testCreateClaimSuccess: FAILED
";
        }
    }
    
    public function testCreateClaimInvalidData() {
        if (!self::$testUserId) { // Ensure test user is available for consistent testing
            echo "Skipping testCreateClaimInvalidData: Test user not available.
";
            return;
        }
        // Example: Invalid date
        $claim_id = self::$claimModel->createClaim(self::$testUserId, 'invalid-date', 'Desc', '100');
        if ($claim_id === false) {
             echo "testCreateClaimInvalidData (Date): PASSED
";
        } else {
             echo "testCreateClaimInvalidData (Date): FAILED (Claim ID: $claim_id)
";
             if ($claim_id) self::$createdClaimIds[] = $claim_id;
        }
        
        // Example: Invalid amount
        $claim_id_amt = self::$claimModel->createClaim(self::$testUserId, date('Y-m-d'), 'Desc', '-50');
        if ($claim_id_amt === false) {
             echo "testCreateClaimInvalidData (Amount): PASSED
";
        } else {
             echo "testCreateClaimInvalidData (Amount): FAILED (Claim ID: $claim_id_amt)
";
             if ($claim_id_amt) self::$createdClaimIds[] = $claim_id_amt;
        }
    }


    public static function tearDownAfterClass(): void {
        if (self::$pdo) { // Ensure $pdo is available
            try {
                if (!empty(self::$createdClaimIds)) {
                    $ids_placeholder = implode(',', array_fill(0, count(self::$createdClaimIds), '?'));
                    $stmt = self::$pdo->prepare("DELETE FROM claims WHERE claim_id IN ($ids_placeholder)");
                    $stmt->execute(array_values(self::$createdClaimIds));
                    self::$createdClaimIds = []; // Clear the array after deleting
                }
                if (self::$testUserId) {
                    // Also delete any other claims associated with test user if not caught by claim_id list
                    $stmt_user_claims = self::$pdo->prepare("DELETE FROM claims WHERE user_id = ?");
                    $stmt_user_claims->execute([self::$testUserId]);
                    
                    // Delete the test user
                    $stmt_user = self::$pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt_user->execute([self::$testUserId]);
                    self::$testUserId = null; // Reset testUserId
                }
                 // Final cleanup for any users matching the pattern, in case some were not fully cleaned
                $stmt_cleanup_users = self::$pdo->prepare("DELETE FROM users WHERE username LIKE 'claimtestuser_%'");
                $stmt_cleanup_users->execute();
            } catch (PDOException $e) {
                 echo "Warning: PDOException during ClaimTest tearDownAfterClass: " . $e->getMessage() . "
";
            }
        }
    }
}
?>
