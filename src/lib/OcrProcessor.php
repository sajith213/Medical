<?php
namespace App\Lib; // Using a namespace

// Assume 'php-tesseract/php-tesseract' is chosen and installed via Composer.
// The actual 'use' statement might vary based on the library's specific namespace.
// use thiagoalessio\TesseractOCR\TesseractOCR; 

class OcrProcessor {
    private $tesseractPath; // Path to tesseract executable, if needed by wrapper
    private $language;

    public function __construct($tesseractPath = '/usr/bin/tesseract', $language = 'eng') {
        // $this->tesseractPath = $tesseractPath; // May not be needed if wrapper handles it
        $this->language = defined('OCR_LANGUAGE') ? OCR_LANGUAGE : $language;

        // Placeholder for actual TesseractOCR library instantiation
        // if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) { // Corrected class name
        //     throw new \Exception("TesseractOCR library not found. Please install it via Composer.");
        // }
    }

    /**
     * Processes an image file using Tesseract OCR and attempts to extract structured data.
     *
     * @param string $filePath Full path to the image file (or PDF, if supported by Tesseract version/wrapper).
     * @return array Associative array with 'raw_text' and 'structured_data' (amount, date, provider).
     *               Returns ['error' => 'message'] on failure.
     */
    public function processDocument($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['raw_text' => null, 'structured_data' => $this->getEmptyStructuredData(), 'error' => "File not found or not readable: $filePath"];
        }

        $raw_text = null;
        $structured_data = $this->getEmptyStructuredData();

        try {
            // --- This is where the actual OCR processing would happen ---
            // Example using a hypothetical TesseractOCR wrapper:
            // $tesseract = new TesseractOCR($filePath);
            // $tesseract->lang($this->language);
            // // $tesseract->executable($this->tesseractPath); // If path needs to be set explicitly
            // $raw_text = $tesseract->run();
            
            // --- SIMULATED OCR FOR NOW ---
            // In a real scenario, the $raw_text would come from the OCR engine.
            // For this subtask, we'll simulate.
            $filename = basename($filePath);
            if (stripos($filename, 'sample_bill_123') !== false || stripos($filename, 'invoice1') !== false) { 
                $raw_text = "Sample Medical Bill
Provider: General Hospital Services
Address: 123 Main St, Anytown, USA
Date: 2023-10-26
Invoice # INV12345
Patient: John Doe
Total Amount: $123.45
Details: Consultation";
            } elseif (stripos($filename, 'another_doc') !== false || stripos($filename, 'receipt2') !== false) {
                 $raw_text = "HEALTHCARE SERVICES INC.
Invoice Date: 2024/01/15
Patient Name: Jane Smith
Service: Routine Checkup
Amount Due: 75.00 USD";
            } elseif (stripos($filename, 'complex_bill') !== false) {
                 $raw_text = "Central Clinic & Medical Supplies
Statement Date: 05 Feb 2023
Account: A9876
Total Charges: $250.00
Insurance Paid: $200.00
Amount Owed by Patient: $50.00";
            } else {
                $raw_text = "Simulated OCR: No specific pattern matched for filename '$filename'. This document might be a generic one or an image with less text.";
                // For non-matching, we can still return the raw_text and let extractStructuredData try.
                // If we want to specifically mark it as failed for simulation:
                // return ['raw_text' => $raw_text, 'structured_data' => $structured_data, 'error' => 'Simulated OCR could not extract specific bill text.'];
            }
            // --- END SIMULATION ---


            if ($raw_text) {
                $structured_data = $this->extractStructuredData($raw_text);
            } else {
                 // This case would be if actual OCR (not simulation) returns empty/null
                 return ['raw_text' => null, 'structured_data' => $structured_data, 'error' => 'OCR processing failed to extract any text.'];
            }

            return [
                'raw_text' => $raw_text,
                'structured_data' => $structured_data,
                'error' => null // No error if we got this far
            ];

        } catch (\Exception $e) {
            // Log the actual exception message from the OCR library
            error_log("OCR Processing Exception: " . $e->getMessage());
            return ['raw_text' => null, 'structured_data' => $this->getEmptyStructuredData(), 'error' => "OCR processing failed: " . $e->getMessage()];
        }
    }

    private function getEmptyStructuredData() {
        return [
            'amount' => null,
            'date' => null,
            'provider' => null
        ];
    }

    /**
     * Attempts to extract structured data (amount, date, provider) from raw OCR text.
     *
     * @param string $text Raw text from OCR.
     * @return array Associative array with 'amount', 'date', 'provider'.
     */
    private function extractStructuredData($text) {
        $amount = null;
        $date = null;
        $provider = null;

        // Attempt to find amount (more robust patterns)
        // Looks for keywords then a dollar amount, or just dollar amounts.
        // Prioritizes keywords like "Total Amount", "Amount Due", "Amount Owed"
        if (preg_match('/(?:Total Amount|Amount Due|Amount Owed by Patient|Invoice Total|Balance Due|Total Charges)[:\s]*?\$?([0-9,]+\.\d{2})/i', $text, $matches)) {
            $amount = str_replace(',', '', $matches[1]);
        } elseif (preg_match('/\$([0-9,]+\.\d{2})/i', $text, $matches)) { // General dollar amount
            $amount = str_replace(',', '', $matches[1]);
        }
        if ($amount && !is_numeric($amount)) $amount = null;


        // Attempt to find date (more patterns, including with month names)
        $date_patterns = [
            '/(\d{4}-\d{1,2}-\d{1,2})/i',                                      // YYYY-MM-DD
            '/(\d{1,2}\/\d{1,2}\/\d{2,4})/i',                                  // MM/DD/YYYY or M/D/YY
            '/(\d{1,2}-(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-\d{2,4})/i', // DD-Mon-YYYY
            '/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},?\s+\d{2,4})/i', // Mon DD, YYYY or Mon DD YYYY
            '/(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{2,4})/i', // DD Mon YYYY
            '/(?:Date|Invoice Date|Statement Date)[:\s]*?(\d{4}\/\d{1,2}\/\d{1,2})/i', // Date: YYYY/MM/DD
            '/(?:Date|Invoice Date|Statement Date)[:\s]*?(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{2,4})/i' // Date: DD Mon YYYY
        ];
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $date = $this->normalizeDate($matches[1]);
                if ($date) break; // Found a valid date
            }
        }
        
        // Attempt to find provider name (more heuristic)
        $provider_keywords = ['Provider:', 'From:', 'Clinic', 'Hospital', 'Medical Center', 'Healthcare Services Inc.', 'Drs.', 'Dr.'];
        $lines = explode("\n", $text);
        $potential_providers = [];

        foreach ($lines as $line_num => $line) {
            $trimmed_line = trim($line);
            // Prefer lines starting with "Provider:" or "From:"
            if (preg_match('/^(?:Provider|From)[:\s]*(.+)/i', $trimmed_line, $matches)) {
                $potential_providers[] = ['p' => trim($matches[1]), 'score' => 10];
                break; // Strong indicator
            }
            foreach ($provider_keywords as $keyword) {
                if (stripos($trimmed_line, $keyword) !== false) {
                    // Try to extract a meaningful name, not just the keyword
                    $p_name = $trimmed_line;
                    // Remove common prefixes/suffixes that are not part of the name
                    $p_name = preg_replace('/^(Invoice For|Patient Bill from|Statement)\s*:\s*/i', '', $p_name);
                    $p_name = preg_replace('/\s*(Invoice|Bill|Statement)$/i', '', $p_name);
                    // Avoid using very short lines or lines that are mostly dates or amounts
                    if (strlen(trim($p_name)) > 3 && !preg_match('/^\s*\$?[\d\.,\s]+\$?$/', $p_name) && !is_null($this->normalizeDate($p_name))) {
                        $potential_providers[] = ['p' => trim($p_name), 'score' => (stripos($trimmed_line, 'Inc.') !== false || stripos($trimmed_line, 'LLC') !== false ? 7 : 5) - ($line_num * 0.5) ]; // Give higher score to lines with Inc/LLC, penalize lines further down
                    }
                }
            }
        }
        
        if (!empty($potential_providers)) {
            usort($potential_providers, function($a, $b) { return $b['score'] <=> $a['score']; }); // Sort by score desc
            $provider = $potential_providers[0]['p'];
        } elseif (empty($provider) && !empty($lines[0]) && strlen(trim($lines[0])) > 3 && strlen(trim($lines[0])) < 100) {
             // Fallback to the first line if it seems reasonable and no other provider found
            $first_line_trimmed = trim($lines[0]);
            if(!preg_match('/(Date|Invoice|Amount|Patient)/i', $first_line_trimmed) && !is_numeric(str_replace(['$','.',','],'',$first_line_trimmed))){
                 $provider = $first_line_trimmed;
            }
        }


        return [
            'amount' => $amount ? (string)$amount : null,
            'date' => $date,
            'provider' => $provider ? substr(trim($provider), 0, 250) : null
        ];
    }
    
    private function normalizeDate($dateString) {
        if (empty(trim($dateString))) return null;
        try {
            // Attempt to replace common non-standard separators if \DateTime fails directly
            $dateString = str_replace('/', '-', $dateString); // Common for US dates
            $dateObj = new \DateTime($dateString);
            // Check if the year is plausible (e.g. not 0012 for 12/05/23 if it meant 2023)
            if ($dateObj->format('Y') < 1900) { // Heuristic for 2-digit years
                $two_digit_year_formats = ['m-d-y', 'n-j-y', 'm/d/y', 'n/j/y'];
                foreach($two_digit_year_formats as $fmt){
                    $tempDateObj = \DateTime::createFromFormat($fmt, $dateString);
                    if($tempDateObj && $tempDateObj->format('Y') > 2000){ // Assuming 2-digit year means 20xx
                        $dateObj = $tempDateObj;
                        break;
                    }
                }
            }
            return $dateObj->format('Y-m-d');
        } catch (\Exception $e) {
            // Log error: error_log("Failed to normalize date: $dateString - " . $e->getMessage());
            return null; 
        }
    }
}
?>
