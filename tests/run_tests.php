<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output buffering to catch potential errors/warnings before headers are sent (if this were web-accessed)
// For CLI, it helps keep output clean until we explicitly print.
ob_start(); 

echo "<pre>"; // For browser output formatting, harmless for CLI

require_once __DIR__ . '/bootstrap.php';

// Ensure $pdo is globally available for tests if they rely on it.
// The bootstrap file should make $pdo available.
global $pdo;
if (!$pdo) {
    echo "CRITICAL ERROR: PDO object not available from bootstrap.php. Tests cannot run.
";
    echo "</pre>";
    exit;
}


// Include test classes
require_once __DIR__ . '/unit/models/UserTest.php';
require_once __DIR__ . '/unit/models/ClaimTest.php';
require_once __DIR__ . '/unit/models/FinancialQuotaTest.php';

echo "Running User Tests...
";
try {
    UserTest::setUpBeforeClass(); 
    $userTest = new UserTest();
    $userTest->testLoginSuccess();
    $userTest->testLoginWrongPassword();
    $userTest->testLoginNonExistentUser();
    UserTest::tearDownAfterClass(); 
} catch (Throwable $t) { // Catch any type of error/exception
    echo "Error during User Tests: " . $t->getMessage() . "
Stack Trace:
" . $t->getTraceAsString() . "
";
}
echo "User Tests Done.

";

echo "Running Claim Tests...
";
try {
    ClaimTest::setUpBeforeClass();
    $claimTest = new ClaimTest();
    $claimTest->testCreateClaimSuccess();
    $claimTest->testCreateClaimInvalidData();
    ClaimTest::tearDownAfterClass();
} catch (Throwable $t) {
    echo "Error during Claim Tests: " . $t->getMessage() . "
Stack Trace:
" . $t->getTraceAsString() . "
";
}
echo "Claim Tests Done.

";

echo "Running FinancialQuota Tests...
";
try {
    FinancialQuotaTest::setUpBeforeClass();
    $financialQuotaTest = new FinancialQuotaTest();
    $financialQuotaTest->testReimbursementFull();
    $financialQuotaTest->testReimbursementPartial();
    $financialQuotaTest->testReimbursementNone(); // Added this test as it was in my local file
    FinancialQuotaTest::tearDownAfterClass();
} catch (Throwable $t) {
    echo "Error during FinancialQuota Tests: " . $t->getMessage() . "
Stack Trace:
" . $t->getTraceAsString() . "
";
}
echo "FinancialQuota Tests Done.

";

echo "</pre>";
ob_end_flush(); // Send buffered output
?>
