<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'auth_check.php';

$forecast_result = null;
$error_message = null;
$debug_info = '';

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
        
        $python_path = '"C:\\Users\\Encarnacion\\AppData\\Local\\Programs\\Python\\Python313\\python.exe"';
        $script_path = realpath("scripts/population_forecast.py");
        $data_path = realpath($target_file);
        
        $command = "$python_path \"$script_path\" \"$data_path\" $forecast_years 2>&1";
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
            
            if (!file_exists($script_path)) {
                $debug_info .= "Script file not found at: $script_path\n";
            }
            if (!file_exists($data_path)) {
                $debug_info .= "Data file not found at: $data_path\n";
            }
        }

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
    <title>Population Forecast - Barangay Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white text-center mb-4">Barangay System</h3>
                <nav>
                    <a href="index.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a href="residents.php"><i class="fas fa-users me-2"></i> Residents</a>
                    <a href="clearance.php"><i class="fas fa-file-alt me-2"></i> Clearance</a>
                    <a href="indigency.php"><i class="fas fa-certificate me-2"></i> Indigency</a>
                    <a href="blotter.php"><i class="fas fa-book me-2"></i> Blotter</a>
                    <a href="officials.php"><i class="fas fa-user-tie me-2"></i> Officials</a>
                    <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a href="forecast.php" class="active"><i class="fas fa-chart-line me-2"></i> Population Forecast</a>
                </nav>
            </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($forecast_result && !isset($forecast_result['error'])): ?>
    <script>
        const historicalData = <?php echo json_encode($forecast_result['historical']); ?>;
        const forecastData = <?php echo json_encode($forecast_result['forecast']); ?>;

        const labels = [...historicalData.map(d => d.year), ...forecastData.map(d => d.year)];
        const historicalValues = [...historicalData.map(d => d.population), ...Array(forecastData.length).fill(null)];
        const forecastValues = [...Array(historicalData.length).fill(null), ...forecastData.map(d => d.population)];
        const ctx = document.getElementById('forecastChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Historical Population',
                    data: historicalValues,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    fill: true
                },
                {
                    label: 'Forecast Population',
                    data: forecastValues,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderDash: [5, 5],
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Population Forecast'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Population'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Year'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
