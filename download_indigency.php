<?php
require_once 'config.php';
require_once 'auth_check.php';

// Get the filename from the query string
$filename = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($filename)) {
    die('No file specified');
}

// Validate filename to prevent directory traversal
$filename = basename($filename);
$filepath = __DIR__ . '/temp/' . $filename;

if (!file_exists($filepath)) {
    die('File not found');
}

// Set headers for DOCX download
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="barangay_indigency.docx"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: max-age=0');

// Output file
readfile($filepath);

// Delete the temporary file
unlink($filepath);
