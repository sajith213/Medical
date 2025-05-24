<?php
class FinancialQuota {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Sets or updates a financial quota for a user for a specific financial year.
     *
     * @param int $user_id
     * @param string $financial_year_start YYYY-MM-DD format
     * @param string $financial_year_end YYYY-MM-DD format
     * @param string $total_quota Decimal string
     * @return bool True on success, false on failure.
     */
    public function setQuota($user_id, $financial_year_start, $financial_year_end, $total_quota) {
        if (!is_numeric($user_id) || !is_numeric($total_quota) || (float)$total_quota < 0) {
            error_log("Invalid user_id or total_quota for setQuota.");
            return false;
        }
        if (!$this->isValidDate($financial_year_start) || !$this->isValidDate($financial_year_end)) {
            error_log("Invalid date format for financial year start/end for setQuota.");
            return false;
        }
        if ($financial_year_start >= $financial_year_end) {
            error_log("Financial year end must be after start for setQuota.");
            return false;
        }

        // Check if quota for this user and financial year start already exists
        $sql_check = "SELECT quota_id FROM financial_quotas 
                      WHERE user_id = :user_id AND financial_year_start = :financial_year_start";
        $stmt_check = $this->pdo->prepare($sql_check);
        $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_check->bindParam(':financial_year_start', $financial_year_start, PDO::PARAM_STR);
        $stmt_check->execute();
        $existing_quota = $stmt_check->fetch();

        if ($existing_quota) {
            // Update existing quota
            $sql = "UPDATE financial_quotas 
                    SET financial_year_end = :financial_year_end, total_quota = :total_quota, updated_at = NOW()
                    WHERE quota_id = :quota_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':quota_id', $existing_quota['quota_id'], PDO::PARAM_INT);
        } else {
            // Insert new quota
            $sql = "INSERT INTO financial_quotas (user_id, financial_year_start, financial_year_end, total_quota, utilized_quota)
                    VALUES (:user_id, :financial_year_start, :financial_year_end, :total_quota, '0.00')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':financial_year_start', $financial_year_start, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':financial_year_end', $financial_year_end, PDO::PARAM_STR);
        $stmt->bindParam(':total_quota', $total_quota, PDO::PARAM_STR); // Bind as string for precision

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error setting/updating quota: " . $e->getMessage());
            // Check for unique constraint violation if not handled by prior check (e.g. race condition or different unique key)
            // The unique key name in schema.sql is uq_user_financial_year
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'uq_user_financial_year') !== false) {
                error_log("Duplicate quota entry attempted for user $user_id, year $financial_year_start despite check: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Retrieves the current quota for a user based on a given date (e.g., claim date).
     *
     * @param int $user_id
     * @param string $date YYYY-MM-DD format (e.g., claim_date)
     * @return array|false Quota data or false if not found/applicable.
     */
    public function getCurrentQuotaForUser($user_id, $date) {
        if (!is_numeric($user_id) || !$this->isValidDate($date)) {
            error_log("Invalid user_id or date for getCurrentQuotaForUser.");
            return false;
        }
        $sql = "SELECT * FROM financial_quotas 
                WHERE user_id = :user_id 
                AND :date BETWEEN financial_year_start AND financial_year_end
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Retrieves a specific quota by its ID.
     * @param int $quota_id
     * @return array|false
     */
    public function getQuotaById($quota_id) {
        if (!is_numeric($quota_id)) return false;
        $stmt = $this->pdo->prepare("SELECT fq.*, u.username, u.full_name FROM financial_quotas fq JOIN users u ON fq.user_id = u.user_id WHERE fq.quota_id = :quota_id");
        $stmt->bindParam(':quota_id', $quota_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all quotas (for Admin).
     * @return array
     */
    public function getAllQuotas() {
        // Consider pagination for large datasets in a real application
        $stmt = $this->pdo->query(
            "SELECT fq.*, u.username, u.full_name 
             FROM financial_quotas fq 
             JOIN users u ON fq.user_id = u.user_id 
             ORDER BY fq.financial_year_start DESC, u.username ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Updates the utilized quota for a specific quota ID.
     * This is typically called when a claim is approved or its status changes.
     *
     * @param int $quota_id
     * @param string $amount_change Decimal string (positive for utilization, negative for reversal)
     * @return bool True on success, false on failure.
     */
    public function updateUtilizedQuota($quota_id, $amount_change) {
        if (!is_numeric($quota_id) || !is_numeric($amount_change)) {
             error_log("Invalid quota_id or amount_change for updateUtilizedQuota.");
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE financial_quotas 
                    SET utilized_quota = utilized_quota + CAST(:amount_change AS DECIMAL(10,2)), updated_at = NOW()
                    WHERE quota_id = :quota_id";
            $stmt = $this->pdo->prepare($sql);
            // Bind as string, but ensure DB knows it's a number for arithmetic. Casting in SQL helps.
            $stmt->bindParam(':amount_change', $amount_change, PDO::PARAM_STR); 
            $stmt->bindParam(':quota_id', $quota_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            
            if ($success) {
                $updated_quota = $this->getQuotaById($quota_id); // Re-fetch to check (within transaction)
                if ($updated_quota && (float)$updated_quota['utilized_quota'] > (float)$updated_quota['total_quota']) {
                     error_log("Warning: Utilized quota (".$updated_quota['utilized_quota'].") exceeds total (".$updated_quota['total_quota'].") for quota_id $quota_id.");
                }
                if ($updated_quota && (float)$updated_quota['utilized_quota'] < 0) {
                     error_log("Warning: Utilized quota is negative (".$updated_quota['utilized_quota'].") for quota_id $quota_id. This might indicate an issue.");
                }
            }
            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating utilized quota: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recalculates and sets the utilized quota for a given quota_id based on all approved claims
     * within that quota's financial year for the specific user.
     *
     * @param int $quota_id The ID of the financial quota to recalculate.
     * @return bool True on success, false on failure or if quota not found.
     */
    public function recalculateUtilizedQuota($quota_id) {
        $quota = $this->getQuotaById($quota_id);
        if (!$quota) {
            error_log("Quota not found for recalculation: $quota_id");
            return false;
        }

        $sql_sum_approved = "SELECT SUM(c.total_amount) as total_approved
                             FROM claims c
                             WHERE c.user_id = :user_id
                             AND c.status = 'Approved'
                             AND c.claim_date BETWEEN :fy_start AND :fy_end";
        
        $stmt_sum = $this->pdo->prepare($sql_sum_approved);
        $stmt_sum->bindParam(':user_id', $quota['user_id'], PDO::PARAM_INT);
        $stmt_sum->bindParam(':fy_start', $quota['financial_year_start'], PDO::PARAM_STR);
        $stmt_sum->bindParam(':fy_end', $quota['financial_year_end'], PDO::PARAM_STR);
        $stmt_sum->execute();
        $result = $stmt_sum->fetch(PDO::FETCH_ASSOC);

        $total_approved_amount = $result['total_approved'] ?? '0.00';
        if ($total_approved_amount === null) $total_approved_amount = '0.00'; // Ensure it's a string for binding

        $sql_update = "UPDATE financial_quotas 
                       SET utilized_quota = :total_approved_amount, updated_at = NOW()
                       WHERE quota_id = :quota_id";
        $stmt_update = $this->pdo->prepare($sql_update);
        $stmt_update->bindParam(':total_approved_amount', $total_approved_amount, PDO::PARAM_STR);
        $stmt_update->bindParam(':quota_id', $quota_id, PDO::PARAM_INT);
        
        try {
            return $stmt_update->execute();
        } catch (PDOException $e) {
            error_log("Error recalculating utilized quota for quota_id $quota_id: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Calculates the reimbursement amount for a given claim, considering the user's available quota.
     *
     * @param int $claim_id
     * @return array ['reimbursable_amount' => string, 'notes' => string array] or false on error.
     */
    public function calculateReimbursement($claim_id) {
        $claimSql = "SELECT * FROM claims WHERE claim_id = :claim_id";
        $claimStmt = $this->pdo->prepare($claimSql);
        $claimStmt->bindParam(':claim_id', $claim_id, PDO::PARAM_INT);
        $claimStmt->execute();
        $claim = $claimStmt->fetch(PDO::FETCH_ASSOC);

        if (!$claim) {
            error_log("Claim not found for reimbursement calculation: $claim_id");
            return false;
        }

        $notes = [];
        // Note: This calculation is typically done *before* a claim is approved to see the impact,
        // or *after* approval to confirm the amount.
        // The utilized_quota should reflect amounts from *other* already approved claims.

        $quota = $this->getCurrentQuotaForUser($claim['user_id'], $claim['claim_date']);
        if (!$quota) {
            $notes[] = "No financial quota found for the user covering the claim date ({$claim['claim_date']}).";
            return ['reimbursable_amount' => '0.00', 'notes' => $notes, 'quota_details' => null];
        }

        $total_quota = (float)$quota['total_quota'];
        $utilized_quota_before_this_claim = (float)$quota['utilized_quota'];
        
        // If the claim being calculated IS ALREADY 'Approved' and its amount IS ALREADY IN `utilized_quota`,
        // we need to temporarily subtract it from `utilized_quota_before_this_claim` to correctly assess
        // the available quota *for this specific claim*.
        if ($claim['status'] === 'Approved') {
            // This logic assumes that if a claim is 'Approved', its amount is part of the `utilized_quota`.
            // This is a common scenario if `recalculateUtilizedQuota` is run after each approval.
            // For a robust system, it's better if `utilized_quota` always reflects the sum of *other* approved claims.
            // Let's assume `utilized_quota` might include the current claim if it's already approved.
            // To be safe and avoid double counting or incorrect exclusion:
            // The most accurate utilized_quota_before_this_claim is the sum of *other* approved claims.
            // This can be fetched by recalculating utilized for the period *excluding* the current claim_id.
            // For this function's scope, we will use the $quota['utilized_quota'] as is,
            // and add a note if the claim is already approved.
            $notes[] = "Claim status is '{$claim['status']}'. The 'Utilized Quota' shown reflects the total including other approved claims for the period.";
        } else {
             $notes[] = "Claim status is '{$claim['status']}'. Calculation projects reimbursement if approved.";
        }


        $claim_amount = (float)$claim['total_amount'];
        $available_quota = $total_quota - $utilized_quota_before_this_claim;
        
        if ($available_quota < 0) { // This can happen if utilized_quota somehow exceeds total_quota due to direct updates or manual error
            $notes[] = "Warning: Utilized quota currently exceeds total quota. Available quota considered as 0.";
            $available_quota = 0;
        }

        $reimbursable_amount_val = 0.00;
        if ($claim_amount <= $available_quota) {
            $reimbursable_amount_val = $claim_amount;
            $notes[] = "Full claim amount is within available quota.";
        } else {
            $reimbursable_amount_val = $available_quota > 0 ? $available_quota : 0.00;
            $notes[] = ($available_quota > 0) ? "Claim amount exceeds available quota. Partial reimbursement calculated." : "No available quota remaining for this period.";
        }
        
        $reimbursable_amount_str = number_format($reimbursable_amount_val, 2, '.', '');

        $notes[] = "Total Quota: " . number_format($total_quota, 2) . 
                   ", Utilized (from quota record): " . number_format($utilized_quota_before_this_claim, 2) . 
                   ", Calculated Available for this claim: " . number_format($available_quota, 2) . 
                   ", Claim Amount: " . number_format($claim_amount, 2) . ".";

        return [
            'reimbursable_amount' => $reimbursable_amount_str,
            'notes' => $notes,
            'quota_details' => $quota // For context
        ];
    }
    
    /**
     * Deletes a financial quota by its ID.
     * @param int $quota_id
     * @return bool
     */
    public function deleteQuota($quota_id) {
        if (!is_numeric($quota_id)) return false;
        // Add check: ensure no approved claims are tied to this quota period if strict deletion is needed.
        // For now, direct deletion.
        $sql = "DELETE FROM financial_quotas WHERE quota_id = :quota_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':quota_id', $quota_id, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting financial quota $quota_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validates a date string.
     * @param string $dateString
     * @param string $format
     * @return bool
     */
    public static function isValidDate($dateString, $format = 'Y-m-d') { // Made public static for potential use in controller
        $d = \DateTime::createFromFormat($format, $dateString);
        return $d && $d->format($format) === $dateString;
    }
}
?>
