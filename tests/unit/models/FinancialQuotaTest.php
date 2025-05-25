<?php
// Use PHPUnit\Framework\TestCase;
// require_once __DIR__ . '/../../bootstrap.php'; // If running standalone

class FinancialQuotaTest /* extends TestCase */ {
    private static $pdo;
    private static $quotaModel;
    private static $userModel;
    private static $claimModel;
    private static $testUserId;
    private static $testQuotaId;
    private static $createdClaimIds = []; // To store IDs of claims created during tests

    public static function setUpBeforeClass(): void {
        global $pdo;
        if (!$pdo) { // Fallback
            require_once __DIR__ . '/../../bootstrap.php';
        }
        self::$pdo = $pdo;
        self::$quotaModel = new FinancialQuota(self::$pdo);
        self::$userModel = new User(self::$pdo);
        self::$claimModel = new Claim(self::$pdo);

        // Create a temporary user
        $testUsername = 'quotatestuser_' . time();
        try {
            // Clean up if a previous test run failed
            // Order of deletion matters due to foreign key constraints.
            // 1. Delete claims associated with any old test users
            $stmt_cleanup_claims = self::$pdo->prepare("DELETE FROM claims WHERE user_id IN (SELECT user_id FROM users WHERE username LIKE 'quotatestuser_%')");
            $stmt_cleanup_claims->execute();
            // 2. Delete financial_quotas associated with any old test users
            $stmt_cleanup_quotas = self::$pdo->prepare("DELETE FROM financial_quotas WHERE user_id IN (SELECT user_id FROM users WHERE username LIKE 'quotatestuser_%')");
            $stmt_cleanup_quotas->execute();
            // 3. Delete documents associated with old test claims (if any; documents table has ON DELETE CASCADE for claims)
            // No direct action needed here if ON DELETE CASCADE is reliable.

            // 4. Delete old test users themselves
            $stmt_cleanup_users = self::$pdo->prepare("DELETE FROM users WHERE username LIKE 'quotatestuser_%'");
            $stmt_cleanup_users->execute();


            if (self::$userModel->register($testUsername, 'Pass123', $testUsername.'@example.com', 'Quota Test User')) {
                $stmt = self::$pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                $stmt->execute([$testUsername]);
                $user = $stmt->fetch();
                if ($user) self::$testUserId = $user['user_id'];
            }
        } catch (Exception $e) {
             echo "Warning: Could not create test user for FinancialQuotaTest: " . $e->getMessage() . "
";
        }

        // Set up a quota for this user if user created
        if (self::$testUserId) {
            $fy_start = date('Y') . "-01-01"; // Current year
            $fy_end = date('Y') . "-12-31";   // Current year
            if (self::$quotaModel->setQuota(self::$testUserId, $fy_start, $fy_end, '1000.00')) {
                $quota = self::$quotaModel->getCurrentQuotaForUser(self::$testUserId, date('Y-m-d'));
                if ($quota) self::$testQuotaId = $quota['quota_id'];
            }
        }
    }

    public function testReimbursementFull() {
        if (!self::$testUserId || !self::$testQuotaId) {
             echo "Skipping testReimbursementFull: Test user or quota not set up.
"; return;
        }
        // Ensure utilized quota is 0 for this test
        self::$pdo->exec("UPDATE financial_quotas SET utilized_quota = '0.00' WHERE quota_id = " . self::$testQuotaId);

        // Create a claim that should be fully reimbursable
        $claim_id = self::$claimModel->createClaim(self::$testUserId, date('Y-m-d'), 'Full Reimb Test', '200.00');
        if (!$claim_id) { echo "testReimbursementFull: FAILED (could not create claim)
"; return; }
        self::$createdClaimIds[] = $claim_id;
        
        $reimbursement = self::$quotaModel->calculateReimbursement($claim_id);
        
        if ($reimbursement && isset($reimbursement['reimbursable_amount']) && $reimbursement['reimbursable_amount'] == '200.00') {
            echo "testReimbursementFull: PASSED
";
        } else {
            echo "testReimbursementFull: FAILED
"; print_r($reimbursement);
        }
    }

    public function testReimbursementPartial() {
        if (!self::$testUserId || !self::$testQuotaId) {
             echo "Skipping testReimbursementPartial: Test user or quota not set up.
"; return;
        }
        
        // Set utilized quota to 900.00 for this test
        self::$pdo->exec("UPDATE financial_quotas SET utilized_quota = '900.00' WHERE quota_id = " . self::$testQuotaId);

        // Now, create a claim that should be partially reimbursable (e.g., $200 claim, $100 available from $1000 total)
        $claim_id = self::$claimModel->createClaim(self::$testUserId, date('Y-m-d'), 'Partial Reimb Test', '200.00');
        if (!$claim_id) { echo "testReimbursementPartial: FAILED (could not create claim)
"; return; }
        self::$createdClaimIds[] = $claim_id;
        
        $reimbursement = self::$quotaModel->calculateReimbursement($claim_id);
        
        if ($reimbursement && isset($reimbursement['reimbursable_amount']) && $reimbursement['reimbursable_amount'] == '100.00') {
            echo "testReimbursementPartial: PASSED
";
        } else {
            echo "testReimbursementPartial: FAILED
"; print_r($reimbursement);
        }
    }
    
    public function testReimbursementNone() {
        if (!self::$testUserId || !self::$testQuotaId) {
             echo "Skipping testReimbursementNone: Test user or quota not set up.
"; return;
        }
        
        // Set utilized quota to 1000.00 (full quota)
        self::$pdo->exec("UPDATE financial_quotas SET utilized_quota = '1000.00' WHERE quota_id = " . self::$testQuotaId);

        $claim_id = self::$claimModel->createClaim(self::$testUserId, date('Y-m-d'), 'No Reimb Test', '50.00');
        if (!$claim_id) { echo "testReimbursementNone: FAILED (could not create claim)
"; return; }
        self::$createdClaimIds[] = $claim_id;
        
        $reimbursement = self::$quotaModel->calculateReimbursement($claim_id);
        
        if ($reimbursement && isset($reimbursement['reimbursable_amount']) && $reimbursement['reimbursable_amount'] == '0.00') {
            echo "testReimbursementNone: PASSED
";
        } else {
            echo "testReimbursementNone: FAILED
"; print_r($reimbursement);
        }
    }


    public static function tearDownAfterClass(): void {
        if (self::$pdo) { // Ensure $pdo is available
            try {
                if (!empty(self::$createdClaimIds)) {
                    $ids_placeholder = implode(',', array_fill(0, count(self::$createdClaimIds), '?'));
                    $stmt = self::$pdo->prepare("DELETE FROM claims WHERE claim_id IN ($ids_placeholder)");
                    $stmt->execute(array_values(self::$createdClaimIds));
                    self::$createdClaimIds = [];
                }
                if (self::$testQuotaId) {
                    self::$pdo->prepare("DELETE FROM financial_quotas WHERE quota_id = ?")->execute([self::$testQuotaId]);
                    self::$testQuotaId = null;
                }
                if (self::$testUserId) {
                    // Also delete any other claims associated with test user if not caught by claim_id list
                    $stmt_user_claims = self::$pdo->prepare("DELETE FROM claims WHERE user_id = ?");
                    $stmt_user_claims->execute([self::$testUserId]);
                    
                    $stmt_user = self::$pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt_user->execute([self::$testUserId]);
                    self::$testUserId = null;
                }
                // Final cleanup for any users matching the pattern, in case some were not fully cleaned
                $stmt_cleanup_users = self::$pdo->prepare("DELETE FROM users WHERE username LIKE 'quotatestuser_%'");
                $stmt_cleanup_users->execute();
            } catch (PDOException $e) {
                 echo "Warning: PDOException during FinancialQuotaTest tearDownAfterClass: " . $e->getMessage() . "
";
            }
        }
    }
}
?>
