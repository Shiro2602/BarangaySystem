<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'auth_check.php';

use PhpOffice\PhpWord\TemplateProcessor;

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('No data received');
    }

    // Required fields
    $required_fields = ['resident_name', 'age', 'civil_status', 'address', 'purpose', 'issue_date'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Load the template
    $templatePath = __DIR__ . '/templates/indigency_template.docx';
    if (!file_exists($templatePath)) {
        throw new Exception('Template file not found');
    }

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

    // Generate unique filename
    $outputFilename = 'indigency_' . uniqid() . '.docx';
    $outputPath = __DIR__ . '/temp/' . $outputFilename;

    // Save the generated file
    $templateProcessor->saveAs($outputPath);

    // Return success response
    echo json_encode(['file' => $outputFilename]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
