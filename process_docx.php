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
            error_log('Template file not found at: ' . $templatePath);
            throw new Exception('Template file not found');
        }

        // Create temp directory if it doesn't exist
        $tempDir = __DIR__ . '/temp';
        if (!file_exists($tempDir)) {
            if (!mkdir($tempDir, 0777, true)) {
                error_log('Failed to create temp directory at: ' . $tempDir);
                throw new Exception('Failed to create temp directory');
            }
        }

        // Check if temp directory is writable
        if (!is_writable($tempDir)) {
            error_log('Temp directory is not writable: ' . $tempDir);
            throw new Exception('Temp directory is not writable');
        }
        
        // Create temporary file for output
        $outputPath = $tempDir . '/' . uniqid() . '_clearance.docx';
        
        // Load template
        try {
            $templateProcessor = new TemplateProcessor($templatePath);
        } catch (Exception $e) {
            error_log('Failed to load template: ' . $e->getMessage());
            throw new Exception('Failed to load template: ' . $e->getMessage());
        }
        
        // Log the data being processed
        error_log('Processing data: ' . print_r($data, true));
        
        // Replace variables in the template
        try {
            $templateProcessor->setValue('resident_name', $data['resident_name']);
            $templateProcessor->setValue('age', $data['age']);
            $templateProcessor->setValue('civil_status', $data['civil_status']);
            $templateProcessor->setValue('address', $data['address']);
            $templateProcessor->setValue('purpose', $data['purpose']);
            
            // Format the date
            $date = new DateTime($data['issue_date']);
            $templateProcessor->setValue('issue_date', $date->format('F d, Y'));
        } catch (Exception $e) {
            error_log('Failed to set template values: ' . $e->getMessage());
            throw new Exception('Failed to set template values: ' . $e->getMessage());
        }
        
        // Save the generated document
        try {
            $templateProcessor->saveAs($outputPath);
        } catch (Exception $e) {
            error_log('Failed to save document: ' . $e->getMessage());
            throw new Exception('Failed to save document: ' . $e->getMessage());
        }
        
        if (!file_exists($outputPath)) {
            error_log('Output file was not created at: ' . $outputPath);
            throw new Exception('Output file was not created');
        }
        
        return $outputPath;
    } catch (Exception $e) {
        error_log('Error in generateClearanceFromTemplate: ' . $e->getMessage());
        return false;
    }
}

// If this file is called directly via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Log the raw input
    error_log('Raw input: ' . file_get_contents('php://input'));
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        error_log('Failed to decode JSON data');
        echo json_encode(['error' => 'Invalid data received']);
        exit;
    }
    
    // Log the decoded data
    error_log('Decoded data: ' . print_r($data, true));
    
    $outputPath = generateClearanceFromTemplate($data);
    
    if ($outputPath) {
        // Return the path to the generated file
        echo json_encode(['file' => basename($outputPath)]);
    } else {
        echo json_encode(['error' => 'Failed to generate document']);
    }
    exit;
}
