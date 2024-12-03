<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'auth_check.php';

$forecast_result = null;
$error_message = null;
$debug_info = '';

// Function to find Python executable
function findPythonPath() {
    // Common Python installation paths on Windows
    $possible_paths = array(
        'C:\\Python313\\python.exe',
        'C:\\Python312\\python.exe',
        'C:\\Python311\\python.exe',
        'C:\\Python310\\python.exe',
        'C:\\Program Files\\Python313\\python.exe',
        'C:\\Program Files\\Python312\\python.exe',
        'C:\\Program Files\\Python311\\python.exe',
        'C:\\Program Files\\Python310\\python.exe',
        'C:\\Program Files (x86)\\Python313\\python.exe',
        'C:\\Program Files (x86)\\Python312\\python.exe',
        'C:\\Program Files (x86)\\Python311\\python.exe',
        'C:\\Program Files (x86)\\Python310\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python313\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python310\\python.exe'
    );

    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return '"' . $path . '"';
        }
    }
    return null;
}

if (isset($_POST['submit_forecast'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["population_data"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    if($fileType != "csv") {
        $error_message = "Sorry, only CSV files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk && move_uploaded_file($_FILES["population_data"]["tmp_name"], $target_file)) {
        $forecast_years = $_POST['forecast_years'] ?? 5;
        
        // Find Python path
        $python_path = findPythonPath();
        if ($python_path === null) {
            $error_message = "Python not found. Please follow these steps to install Python:<br><br>" .
                           "1. Download Python from <a href='https://www.python.org/downloads/' target='_blank'>python.org</a><br>" .
                           "2. Run the installer and make sure to check 'Add Python to PATH'<br>" .
                           "3. Open Command Prompt and run these commands:<br>" .
                           "<code>pip install statsmodels pandas numpy scikit-learn</code><br><br>" .
                           "After installation, refresh this page and try again.";
            $debug_info .= "Could not find Python in common installation locations\n";
        } else {
            $script_path = realpath("scripts/population_forecast.py");
            $data_path = realpath($target_file);
            
            if (!file_exists($script_path)) {
                $error_message = "Forecast script not found.";
                $debug_info .= "Script not found at: $script_path\n";
            } elseif (!file_exists($data_path)) {
                $error_message = "Data file not found.";
                $debug_info .= "Data file not found at: $data_path\n";
            } else {
                // Properly escape paths
                $script_path = '"' . $script_path . '"';
                $data_path = '"' . $data_path . '"';
                
                // First, test Python installation
                $test_cmd = $python_path . ' -c "import pandas; import numpy; import statsmodels.api; import sklearn.metrics" 2>&1';
                $test_output = shell_exec($test_cmd);
                
                if ($test_output !== null && strpos($test_output, 'ImportError') !== false) {
                    $error_message = "Required Python packages are missing. Please run this command in Command Prompt:<br>" .
                                   "<code>pip install statsmodels pandas numpy scikit-learn</code>";
                    $debug_info .= "Package test output: " . $test_output . "\n";
                } else {
                    $command = "$python_path $script_path $data_path $forecast_years 2>&1";
                    $debug_info .= "Command: " . $command . "\n";
                    
                    $output = shell_exec($command);
                    $debug_info .= "Raw output: " . ($output ?? "NULL") . "\n";
                    
                    if ($output !== null) {
                        $forecast_result = json_decode($output, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $error_message = "Error parsing JSON output: " . json_last_error_msg();
                            $debug_info .= "JSON Error: " . json_last_error_msg() . "\n";
                        } elseif (isset($forecast_result['error'])) {
                            $error_message = $forecast_result['error'];
                            $debug_info .= "Script Error: " . $forecast_result['error'] . "\n";
                            $forecast_result = null;
                        }
                    } else {
                        $error_message = "Error executing the forecast script.";
                        $debug_info .= "Shell exec returned null\n";
                    }
                }
            }
        }

        // Clean up the uploaded file
        if (file_exists($target_file)) {
            unlink($target_file);
        }
    } else {
        $error_message = "Sorry, there was an error uploading your file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Population Forecast</title>
    <style>
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .sample-csv {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/header.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container py-4">
                    <h2 class="mb-4">Population Forecast (ARIMA Model)</h2>

                    <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                        <?php if ($debug_info): ?>
                        <hr>
                        <pre class="mb-0"><code><?php echo htmlspecialchars($debug_info); ?></code></pre>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Upload Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Upload Population Data (CSV)</label>
                                    <input type="file" name="population_data" class="form-control" required accept=".csv">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Forecast Years</label>
                                    <input type="number" name="forecast_years" class="form-control" value="5" min="1" max="20">
                                </div>
                                <button type="submit" name="submit_forecast" class="btn btn-primary">
                                    <i class="fas fa-chart-line me-2"></i> Generate Forecast
                                </button>
                            </form>

                            <div class="sample-csv">
                                <h5>CSV Format Example:</h5>
                                <pre>year,population
2018,1500
2019,1550
2020,1600
2021,1650
2022,1700</pre>
                                <small class="text-muted">Note: CSV file must have 'year' and 'population' columns.</small>
                            </div>
                        </div>
                    </div>

                    <?php if ($forecast_result && !isset($forecast_result['error'])): ?>
                    <!-- Forecast Results -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4>Forecast Results</h4>
                            
                            <!-- Metrics -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6>Model Performance</h6>
                                            <p class="mb-1">Model Type: <?php echo $forecast_result['metrics']['model_type']; ?></p>
                                            <p class="mb-1">MAPE: <?php echo $forecast_result['metrics']['mape']; ?>%</p>
                                            <p class="mb-1">Total Growth: <?php echo $forecast_result['metrics']['total_growth_percent']; ?>%</p>
                                            <p class="mb-0">Avg. Annual Growth: <?php echo $forecast_result['metrics']['avg_annual_growth_percent']; ?>%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart -->
                            <canvas id="forecastChart"></canvas>

                            <!-- Data Tables -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h5>Historical Data</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Population</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($forecast_result['historical'] as $data): ?>
                                                <tr>
                                                    <td><?php echo $data['year']; ?></td>
                                                    <td><?php echo number_format($data['population']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Forecast Data</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Projected Population</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($forecast_result['forecast'] as $data): ?>
                                                <tr>
                                                    <td><?php echo $data['year']; ?></td>
                                                    <td><?php echo number_format($data['population']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($forecast_result && !isset($forecast_result['error'])): ?>
    <script>
        const historicalData = <?php echo json_encode($forecast_result['historical']); ?>;
        const forecastData = <?php echo json_encode($forecast_result['forecast']); ?>;

        const labels = [...historicalData.map(d => d.year), ...forecastData.map(d => d.year)];
        const historicalValues = [...historicalData.map(d => d.population), ...Array(forecastData.length).fill(null)];
        const forecastValues = [...Array(historicalData.length).fill(null), ...forecastData.map(d => d.population)];
        const lowerCI = [...Array(historicalData.length).fill(null), ...forecastData.map(d => d.lower_ci)];
        const upperCI = [...Array(historicalData.length).fill(null), ...forecastData.map(d => d.upper_ci)];

        const ctx = document.getElementById('forecastChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Historical Population',
                        data: historicalValues,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 2
                    },
                    {
                        label: 'Forecast Population',
                        data: forecastValues,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 2
                    },
                    {
                        label: 'Confidence Interval',
                        data: upperCI,
                        borderColor: 'rgba(255, 99, 132, 0.2)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        fill: '+1',  // Fill to the next dataset
                        pointRadius: 0,
                        borderWidth: 0
                    },
                    {
                        label: 'Lower Confidence Interval',
                        data: lowerCI,
                        borderColor: 'rgba(255, 99, 132, 0.2)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        fill: false,
                        pointRadius: 0,
                        borderWidth: 0,
                        hidden: true  // Hide this dataset from legend
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Population Forecast with Confidence Intervals',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            filter: function(legendItem, data) {
                                return !legendItem.hidden;  // Only show non-hidden datasets
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat().format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Population',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat().format(value);
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Year',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
