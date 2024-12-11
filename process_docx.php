<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

function generateClearanceFromTemplate($data) {
    try {
        // Path to your template file
        $templatePath = __DIR__ . '/templates/clearance_template.docx';
        
        // Check if template exists
        if (!file_exists($templatePath)) {
            throw new Exception('Template file not found');
        }

        // Create temp directory if it doesn't exist
        $tempDir = __DIR__ . '/temp';
        if (!file_exists($tempDir)) {
            if (!mkdir($tempDir, 0777, true)) {
                throw new Exception('Failed to create temp directory');
            }
        }

        // Check if temp directory is writable
        if (!is_writable($tempDir)) {
            throw new Exception('Temp directory is not writable');
        }
        
        // Create temporary file for output
        $outputPath = $tempDir . '/' . uniqid() . '_clearance.docx';
        
        // Load template
        $templateProcessor = new TemplateProcessor($templatePath);
        
        // Replace variables in the template
        $templateProcessor->setValue('resident_name', $data['resident_name']);
        $templateProcessor->setValue('age', $data['age']);
        $templateProcessor->setValue('civil_status', $data['civil_status']);
        $templateProcessor->setValue('address', $data['address']);
        $templateProcessor->setValue('purpose', $data['purpose']);
        
        // Format the date
        $date = new DateTime($data['issue_date']);
        $templateProcessor->setValue('issue_date', $date->format('F d, Y'));
        
        // Save the generated document
        $templateProcessor->saveAs($outputPath);
        
        if (!file_exists($outputPath)) {
            throw new Exception('Output file was not created');
        }
        
        return basename($outputPath);
    } catch (Exception $e) {
        error_log('Error in generateClearanceFromTemplate: ' . $e->getMessage());
        throw $e;
    }
}

// If this file is called directly via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Get JSON data
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data received');
        }
        
        // Required fields
        $requiredFields = ['resident_name', 'age', 'civil_status', 'address', 'purpose', 'issue_date'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $outputFile = generateClearanceFromTemplate($data);
        echo json_encode(['file' => $outputFile]);
        
    } catch (Exception $e) {
        error_log('Error processing DOCX: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
