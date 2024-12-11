<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'includes/permissions.php';

// Check if user has permission to print clearance
checkPermissionAndRedirect('print_clearance');

if (!isset($_GET['file'])) {
    die('No file specified');
}

$filename = basename($_GET['file']);
$filepath = __DIR__ . '/temp/' . $filename;

// Basic security check
if (!file_exists($filepath) || strpos(realpath($filepath), realpath(__DIR__ . '/temp/')) !== 0) {
    die('Invalid file');
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="barangay_clearance.docx"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($filepath);

// Delete the temporary file
unlink($filepath);
