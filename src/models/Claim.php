<?php
class Claim {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new claim.
     *
     * @param int $user_id The ID of the user submitting the claim.
     * @param string $claim_date The date of the claim (YYYY-MM-DD).
     * @param string $description Description of the claim.
     * @param string $total_amount The total amount claimed (as a string to preserve precision).
     * @param string $status Initial status, defaults to 'Submitted'.
     * @return int|false The ID of the newly created claim on success, false on failure.
     */
    public function createClaim($user_id, $claim_date, $description, $total_amount, $status = 'Submitted') {
        if (!is_numeric($user_id)) {
            error_log("Invalid user_id type for createClaim: " . $user_id);
            return false;
        }
        // Validate date format
        $d = DateTime::createFromFormat('Y-m-d', $claim_date);
        if (!$d || $d->format('Y-m-d') !== $claim_date) {
            error_log("Invalid date format for claim_date: $claim_date");
            return false;
        }
        if (!is_numeric($total_amount) || (float)$total_amount < 0) {
             error_log("Invalid total_amount for createClaim: " . $total_amount);
             return false;
        }

        $sql = "INSERT INTO claims (user_id, claim_date, description, total_amount, status) 
                VALUES (:user_id, :claim_date, :description, :total_amount, :status)";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':claim_date', $claim_date, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR); 
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            
            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating claim: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a single claim by its ID, joining with user details.
     *
     * @param int $claim_id The ID of the claim.
     * @return array|false The claim data as an associative array, or false if not found.
     */
    public function getClaimById($claim_id) {
        $sql = "SELECT c.*, u.username, u.full_name AS user_full_name, 
                       hr.full_name AS hr_staff_full_name
                FROM claims c 
                JOIN users u ON c.user_id = u.user_id
                LEFT JOIN users hr ON c.hr_staff_id = hr.user_id
                WHERE c.claim_id = :claim_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':claim_id', $claim_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all claims for a specific user.
     *
     * @param int $user_id The ID of the user.
     * @return array An array of claims, or an empty array if none found.
     */
    public function getClaimsByUserId($user_id) {
        $sql = "SELECT * FROM claims WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all claims (for HR/Admin), joining with user details.
     * @param string $status Optional status to filter by.
     * @return array An array of all claims.
     */
    public function getAllClaims($status = null) {
        $sql = "SELECT c.*, u.username, u.full_name AS user_full_name
                FROM claims c 
                JOIN users u ON c.user_id = u.user_id";
        if ($status) {
            $sql .= " WHERE c.status = :status";
        }
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Updates the status of a claim.
     *
     * @param int $claim_id The ID of the claim to update.
     * @param string $new_status The new status.
     * @param int|null $hr_staff_id The ID of the HR staff processing the claim.
     * @param string|null $comments Comments regarding the status change (optional).
     * @return bool True on success, false on failure.
     */
    public function updateClaimStatus($claim_id, $new_status, $hr_staff_id, $comments = null) {
        if (!in_array($new_status, self::getClaimStatuses())) {
            error_log("Invalid status for updateClaimStatus: $new_status");
            return false;
        }
        if (!is_numeric($claim_id) || ($hr_staff_id !== null && !is_numeric($hr_staff_id))) {
            error_log("Invalid ID for claim or HR staff in updateClaimStatus.");
            return false;
        }

        $sql = "UPDATE claims SET status = :status, hr_staff_id = :hr_staff_id, comments = :comments, processed_date = NOW(), updated_at = NOW()
                WHERE claim_id = :claim_id";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':hr_staff_id', $hr_staff_id, PDO::PARAM_INT); // PDO will handle null correctly if $hr_staff_id is null
            $stmt->bindParam(':comments', $comments, PDO::PARAM_STR); // PDO will handle null correctly if $comments is null
            $stmt->bindParam(':claim_id', $claim_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating claim status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates an existing claim's details by the user.
     *
     * @param int $claim_id The ID of the claim to update.
     * @param string $claim_date The date of the claim.
     * @param string $description Description of the claim.
     * @param string $total_amount The total amount claimed (as string).
     * @param int $user_id The ID of the user making the update (for verification).
     * @return bool True on success, false on failure.
     */
    public function updateClaimDetails($claim_id, $claim_date, $description, $total_amount, $user_id) {
        $d = DateTime::createFromFormat('Y-m-d', $claim_date);
        if (!$d || $d->format('Y-m-d') !== $claim_date) {
            error_log("Invalid date format for claim_date in updateClaimDetails: $claim_date");
            return false;
        }
        if (!is_numeric($total_amount) || (float)$total_amount < 0) {
             error_log("Invalid total_amount for updateClaimDetails: " . $total_amount);
             return false;
        }
        if (!is_numeric($claim_id) || !is_numeric($user_id)) {
            error_log("Invalid ID for claim or user in updateClaimDetails.");
            return false;
        }

        $existingClaim = $this->getClaimById($claim_id);
        if (!$existingClaim || $existingClaim['user_id'] != $user_id) {
            error_log("User $user_id attempted to update claim $claim_id not belonging to them or claim does not exist.");
            return false; 
        }
        // Allowing updates only for 'Submitted' or 'Needs Information' statuses by employee
        if (!in_array($existingClaim['status'], ['Submitted', 'Needs Information'])) {
           error_log("Claim $claim_id cannot be updated by user in its current status: " . $existingClaim['status']);
           return false;
        }

        $sql = "UPDATE claims 
                SET claim_date = :claim_date, description = :description, total_amount = :total_amount, updated_at = NOW()
                WHERE claim_id = :claim_id AND user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->bindParam(':claim_date', $claim_date, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
            $stmt->bindParam(':claim_id', $claim_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating claim details: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deletes a claim by ID, ensuring it belongs to the user or user is admin.
     *
     * @param int $claim_id The ID of the claim to delete.
     * @param int $user_id The ID of the user attempting deletion.
     * @param string $user_role The role of the user.
     * @return bool True on success, false on failure.
     */
    public function deleteClaim($claim_id, $user_id, $user_role) {
        if (!is_numeric($claim_id) || !is_numeric($user_id)) {
            error_log("Invalid ID for claim or user in deleteClaim.");
            return false;
        }
        
        $claim = $this->getClaimById($claim_id);
        if (!$claim) {
             error_log("Claim $claim_id not found for deletion.");
             return false; // Claim not found
        }

        // Check ownership or admin role
        if ($claim['user_id'] != $user_id && $user_role !== 'Admin') {
            error_log("User $user_id (Role: $user_role) attempted to delete claim $claim_id not belonging to them without Admin rights.");
            return false; // Not owner or admin
        }
        
        // Employees can only delete if status is 'Submitted' or 'Needs Information'. Admin can delete any.
        if ($user_role !== 'Admin' && !in_array($claim['status'], ['Submitted', 'Needs Information'])) {
           error_log("Claim $claim_id cannot be deleted by user $user_id in status " . $claim['status']);
           return false;
        }

        // Placeholder for deleting associated documents - to be implemented with Document model
        // $documentModel = new Document($this->pdo); // Assuming Document model exists
        // if ($documentModel) {
        //    $documentModel->deleteDocumentsByClaimId($claim_id);
        // } else {
        //    error_log("Document model not available for deleting documents associated with claim $claim_id");
        // }


        $sql = "DELETE FROM claims WHERE claim_id = :claim_id";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->bindParam(':claim_id', $claim_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Check for foreign key constraint violation if documents are not deleted first
            if ($e->getCode() == '23000') { // Integrity constraint violation
                 error_log("Error deleting claim $claim_id: Likely due to existing documents. Ensure documents are deleted first. " . $e->getMessage());
            } else {
                error_log("Error deleting claim $claim_id: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Get defined claim statuses.
     * @return array List of claim statuses.
     */
    public static function getClaimStatuses() {
        return ['Submitted', 'Under Review', 'Approved', 'Rejected', 'Needs Information'];
    }
}
?>
