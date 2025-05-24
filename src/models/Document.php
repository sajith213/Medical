<?php
class Document {
    private $pdo;
    private $upload_dir;
    private $allowed_file_types;
    private $max_file_size;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Ensure config constants are loaded.
        // This might require `require_once dirname(dirname(__DIR__)) . '/config/config.php';`
        // if this class is instantiated outside of a context where config.php is already loaded.
        // For this structure, assume config.php is loaded by the entry script (e.g., in public/*.php).
        
        if (!defined('UPLOAD_DIR') || !defined('ALLOWED_FILE_TYPES') || !defined('MAX_FILE_SIZE')) {
            // This is a critical configuration error.
            $error_msg = "UPLOAD_DIR, ALLOWED_FILE_TYPES, or MAX_FILE_SIZE constants are not defined. Check config.php.";
            error_log($error_msg);
            // Throwing an exception is appropriate here as the class cannot function.
            throw new \Exception($error_msg);
        }

        $this->upload_dir = UPLOAD_DIR;
        $this->allowed_file_types = ALLOWED_FILE_TYPES;
        $this->max_file_size = MAX_FILE_SIZE;

        if (!is_dir($this->upload_dir)) {
            // Attempt to create it if it doesn't exist
            if (!mkdir($this->upload_dir, 0755, true)) { // true for recursive creation
                 $error_msg = "Upload directory " . $this->upload_dir . " does not exist and could not be created.";
                 error_log($error_msg);
                 throw new \Exception($error_msg);
            }
        } elseif (!is_writable($this->upload_dir)) {
            $error_msg = "Upload directory " . $this->upload_dir . " is not writable.";
            error_log($error_msg);
            throw new \Exception($error_msg);
        }
    }

    /**
     * Handles the upload of multiple files for a claim, saves them, and records them in the database.
     *
     * @param int $claim_id The ID of the claim these documents belong to.
     * @param int $user_id The ID of the user uploading the documents.
     * @param array $files_array The $_FILES superglobal array for the uploaded documents.
     * @return array ['success' => array of saved document details, 'errors' => array of error messages]
     */
    public function uploadAndSaveDocuments($claim_id, $user_id, $files_array) {
        $saved_documents = [];
        $upload_errors = [];

        if (empty($files_array) || !isset($files_array['name']) || !is_array($files_array['name'])) {
            // No files or malformed files array
            return ['success' => $saved_documents, 'errors' => $upload_errors];
        }
        
        $file_count = count($files_array['name']);

        for ($i = 0; $i < $file_count; $i++) {
            // Check if a file was actually uploaded for this specific index
            if ($files_array['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip if no file was uploaded for this input in the array
            }

            // Check for upload errors first
            if ($files_array['error'][$i] !== UPLOAD_ERR_OK) {
                $upload_errors[] = "File '" . htmlspecialchars($files_array['name'][$i]) . "': " . $this->mapUploadError($files_array['error'][$i]);
                continue;
            }

            $original_filename = basename($files_array['name'][$i]);
            $tmp_filepath = $files_array['tmp_name'][$i];
            $file_size = $files_array['size'][$i];
            
            // Get MIME type from the uploaded file itself, not trusting client header
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($finfo, $tmp_filepath);
            finfo_close($finfo);

            // Validate file size
            if ($file_size > $this->max_file_size) {
                $upload_errors[] = "File '" . htmlspecialchars($original_filename) . "' (" . round($file_size / 1024) . "KB) exceeds maximum size of " . ($this->max_file_size / 1024 / 1024) . "MB.";
                continue;
            }

            // Validate file type
            if (!in_array($file_type, $this->allowed_file_types)) {
                $upload_errors[] = "File type '" . htmlspecialchars($file_type) . "' for '" . htmlspecialchars($original_filename) . "' is not allowed.";
                continue;
            }
            
            $safe_original_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $original_filename);
             // Prevent filenames that are just dots or empty after sanitization
            if (empty($safe_original_filename) || $safe_original_filename === '.' || $safe_original_filename === '..') {
                $safe_original_filename = "document_" . bin2hex(random_bytes(2));
            }


            $file_extension = strtolower(pathinfo($safe_original_filename, PATHINFO_EXTENSION));
            if (empty($file_extension)) { // If no extension after sanitizing, try to get from original or MIME
                $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                if (empty($file_extension)) { // Still no extension, try to guess from MIME
                    $mime_parts = explode('/', $file_type);
                    if (count($mime_parts) === 2 && $mime_parts[1] !== 'plain') { // e.g. image/jpeg -> jpeg
                        $file_extension = $mime_parts[1];
                    } else { // fallback
                        $upload_errors[] = "Could not determine a valid file extension for '" . htmlspecialchars($original_filename) . "'.";
                        continue;
                    }
                }
            }
            // Ensure a dot is present if extension exists
            $filename_base = pathinfo($safe_original_filename, PATHINFO_FILENAME);
            $safe_original_filename_with_ext = $filename_base . '.' . $file_extension;


            $stored_filename = $claim_id . "_" . $user_id . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
            $destination_path = rtrim($this->upload_dir, '/') . '/' . $stored_filename;

            // **Encryption Placeholder**:
            // For example: encryptFile($tmp_filepath, $encrypted_destination_path, $encryption_key);
            // $stored_filename would then point to the encrypted file.

            if (move_uploaded_file($tmp_filepath, $destination_path)) {
                $db_data = [
                    'claim_id' => $claim_id,
                    'original_filename' => $original_filename, 
                    'stored_filename' => $stored_filename,
                    'file_type' => $file_type,
                    'version' => 1 
                ];
                
                $doc_id = $this->addDocumentRecord($db_data);
                if ($doc_id) {
                    $saved_documents[] = [
                        'document_id' => $doc_id,
                        'original_filename' => $original_filename,
                        'stored_filename' => $stored_filename,
                        'file_type' => $file_type,
                        'full_server_path' => $destination_path 
                    ];
                } else {
                    $upload_errors[] = "File '" . htmlspecialchars($original_filename) . "' uploaded but failed to record in database.";
                    if (file_exists($destination_path)) unlink($destination_path);
                }
            } else {
                $upload_errors[] = "Could not move uploaded file '" . htmlspecialchars($original_filename) . "' to destination. Check permissions and path: " . $destination_path;
            }
        }
        return ['success' => $saved_documents, 'errors' => $upload_errors];
    }

    private function addDocumentRecord($data) {
        // file_path column in DB stores the 'stored_filename'
        $sql = "INSERT INTO documents (claim_id, file_name, file_path, file_type, version, ocr_status, created_at, updated_at) 
                VALUES (:claim_id, :original_filename, :stored_filename, :file_type, :version, :ocr_status, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->bindParam(':claim_id', $data['claim_id'], PDO::PARAM_INT);
            $stmt->bindParam(':original_filename', $data['original_filename'], PDO::PARAM_STR);
            $stmt->bindParam(':stored_filename', $data['stored_filename'], PDO::PARAM_STR);
            $stmt->bindParam(':file_type', $data['file_type'], PDO::PARAM_STR);
            $stmt->bindParam(':version', $data['version'], PDO::PARAM_INT);
            
            $ocr_default_status = 'Pending'; 
            // These are the types that OcrProcessor is likely to handle based on typical OCR libraries
            $ocr_compatible_types = ['image/jpeg', 'image/png', 'image/tiff', 'application/pdf']; 
            if (!in_array($data['file_type'], $ocr_compatible_types)) {
                $ocr_default_status = 'NotApplicable';
            }
            $stmt->bindParam(':ocr_status', $ocr_default_status, PDO::PARAM_STR);

            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding document record: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDocumentsByClaimId($claim_id) {
        if (!is_numeric($claim_id)) return [];
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE claim_id = :claim_id ORDER BY uploaded_at DESC, version DESC");
        $stmt->bindParam(':claim_id', $claim_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocumentById($document_id) {
        if (!is_numeric($document_id)) return false;
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE document_id = :document_id");
        $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteDocument($document_id) {
        if (!is_numeric($document_id)) return false;
        
        $doc = $this->getDocumentById($document_id);
        if (!$doc) {
            error_log("Document not found for deletion: $document_id");
            return false;
        }

        // 'file_path' column stores the 'stored_filename'
        $filepath_on_server = rtrim($this->upload_dir, '/') . '/' . $doc['file_path']; 

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM documents WHERE document_id = :document_id");
            $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
            $db_deleted = $stmt->execute();

            if ($db_deleted) {
                $file_deleted_successfully = true;
                if (file_exists($filepath_on_server)) {
                    if (is_writable($filepath_on_server)) { // Check writability before unlinking
                        if (!unlink($filepath_on_server)) {
                             error_log("Failed to delete file from server: $filepath_on_server. DB record was deleted.");
                             $file_deleted_successfully = false; // File system error
                             // Depending on policy, might rollback. Here we log and proceed.
                        }
                    } else {
                         error_log("File $filepath_on_server is not writable. Cannot delete. DB record was deleted.");
                         $file_deleted_successfully = false; // Permission error
                    }
                } else {
                    error_log("File not found on server for deletion: $filepath_on_server. DB record was deleted.");
                    // This might be acceptable if the file was already removed manually or by another process.
                }
                $this->pdo->commit();
                return true; // DB record deleted, file deletion attempted/logged.
            } else {
                $this->pdo->rollBack();
                error_log("Failed to delete document record from DB: $document_id");
                return false;
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error deleting document $document_id: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateDocumentWithOcrData($document_id, $raw_text, $amount, $date, $provider, $status = 'Processed') {
        if (!is_numeric($document_id)) return false;
        
        $sql = "UPDATE documents 
                SET ocr_raw_text = :raw_text, 
                    ocr_extracted_amount = :amount, 
                    ocr_extracted_date = :date, 
                    ocr_provider_name = :provider, 
                    ocr_status = :status,
                    updated_at = NOW() /* Also update updated_at timestamp */
                WHERE document_id = :document_id";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->bindParam(':raw_text', $raw_text, PDO::PARAM_STR);
            $stmt->bindParam(':amount', $amount, ($amount === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindParam(':date', $date, ($date === null ? PDO::PARAM_NULL : PDO::PARAM_STR)); 
            $stmt->bindParam(':provider', $provider, ($provider === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating document with OCR data for doc_id $document_id: " . $e->getMessage());
            return false;
        }
    }
    
    private function mapUploadError($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE: return "File is larger than allowed by server (php.ini upload_max_filesize).";
            case UPLOAD_ERR_FORM_SIZE: return "File is larger than allowed by form (MAX_FILE_SIZE in HTML form).";
            case UPLOAD_ERR_PARTIAL: return "File was only partially uploaded.";
            case UPLOAD_ERR_NO_FILE: return "No file was uploaded (should be caught earlier).";
            case UPLOAD_ERR_NO_TMP_DIR: return "Missing a temporary folder for upload on server.";
            case UPLOAD_ERR_CANT_WRITE: return "Failed to write file to disk on server.";
            case UPLOAD_ERR_EXTENSION: return "A PHP extension stopped the file upload.";
            default: return "Unknown upload error code: $error_code.";
        }
    }
}
?>
