<?php
echo "Checking PHPWord installation...\n\n";

// Check if composer.json exists
if (!file_exists(__DIR__ . '/composer.json')) {
    echo "ERROR: composer.json not found!\n";
} else {
    echo "Found composer.json\n";
}

// Check if vendor directory exists
if (!file_exists(__DIR__ . '/vendor')) {
    echo "ERROR: vendor directory not found! Please run 'composer install'\n";
} else {
    echo "Found vendor directory\n";
    
    // Check PHPWord directory structure
    $phpwordPath = __DIR__ . '/vendor/phpoffice/phpword';
    if (!file_exists($phpwordPath)) {
        echo "ERROR: PHPWord directory not found at: {$phpwordPath}\n";
    } else {
        echo "Found PHPWord directory\n";
        
        // Check src directory
        if (!file_exists($phpwordPath . '/src')) {
            echo "ERROR: PHPWord src directory not found!\n";
        } else {
            echo "Found PHPWord src directory\n";
            
            // Check TemplateProcessor file
            $templateProcessorPath = $phpwordPath . '/src/PhpWord/TemplateProcessor.php';
            if (!file_exists($templateProcessorPath)) {
                echo "ERROR: TemplateProcessor.php not found at expected location!\n";
            } else {
                echo "Found TemplateProcessor.php\n";
            }
        }
    }
}

// Check if autoload.php exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found! Please run 'composer install'\n";
} else {
    echo "Found vendor/autoload.php\n";
    
    // Try to include autoload.php
    try {
        require __DIR__ . '/vendor/autoload.php';
        echo "Successfully included autoload.php\n";
    } catch (Exception $e) {
        echo "ERROR loading autoload.php: " . $e->getMessage() . "\n";
    }
}

// Check if templates directory exists
if (!file_exists(__DIR__ . '/templates')) {
    echo "ERROR: templates directory not found!\n";
} else {
    echo "Found templates directory\n";
    
    // Check if template file exists
    if (!file_exists(__DIR__ . '/templates/clearance_template.docx')) {
        echo "ERROR: clearance_template.docx not found in templates directory!\n";
    } else {
        echo "Found clearance_template.docx\n";
    }
}

// Check if temp directory exists and is writable
$tempDir = __DIR__ . '/temp';
if (!file_exists($tempDir)) {
    echo "ERROR: temp directory not found!\n";
} else {
    echo "Found temp directory\n";
    if (!is_writable($tempDir)) {
        echo "ERROR: temp directory is not writable!\n";
    } else {
        echo "temp directory is writable\n";
    }
}

// Try to load PHPWord class
try {
    if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
        echo "ERROR: PHPWord class not found!\n";
    } else {
        echo "PHPWord class is available\n";
    }
    
    if (!class_exists('PhpOffice\PhpWord\TemplateProcessor')) {
        echo "ERROR: TemplateProcessor class not found!\n";
        
        // Check if the file exists in the expected location
        $templateProcessorFile = __DIR__ . '/vendor/phpoffice/phpword/src/PhpWord/TemplateProcessor.php';
        if (file_exists($templateProcessorFile)) {
            echo "TemplateProcessor.php file exists but class is not loadable\n";
            echo "File contents:\n";
            echo file_get_contents($templateProcessorFile) . "\n";
        }
    } else {
        echo "TemplateProcessor class is available\n";
    }
} catch (Exception $e) {
    echo "ERROR loading PHPWord: " . $e->getMessage() . "\n";
}

echo "\nChecking PHP extensions...\n";
$required_extensions = ['zip', 'xml', 'fileinfo'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "ERROR: Required PHP extension '{$ext}' is not loaded!\n";
    } else {
        echo "Found PHP extension: {$ext}\n";
    }
}

// Print PHP include path
echo "\nPHP Include Path:\n";
echo get_include_path() . "\n";

// Print loaded PHP modules
echo "\nLoaded PHP Modules:\n";
print_r(get_loaded_extensions());
