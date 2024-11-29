<?php
require_once 'config.php';

// Handle clearance request submission
if (isset($_POST['request_clearance'])) {
    $stmt = $pdo->prepare("INSERT INTO clearances (resident_id, purpose, issue_date, expiry_date, or_number, amount) 
                          VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), ?, ?)");
    $stmt->execute([
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['or_number'],
        $_POST['amount']
    ]);
}

// Get all clearance requests
$stmt = $pdo->query("SELECT c.*, CONCAT(r.last_name, ', ', r.first_name, ' ', r.middle_name) as resident_name 
                     FROM clearances c 
                     JOIN residents r ON c.resident_id = r.id 
                     ORDER BY c.issue_date DESC");
$clearances = $stmt->fetchAll();

// Get residents for dropdown
$stmt = $pdo->query("SELECT id, CONCAT(last_name, ', ', first_name, ' ', middle_name) as full_name 
                     FROM residents ORDER BY last_name, first_name");
$residents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Clearance - Barangay System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        .print-certificate {
            width: 8.5in;
            height: 11in;
            padding: 0.5in;
            margin: auto;
            border: 1px solid #ddd;
            display: none;
        }
        @media print {
            .print-certificate {
                display: block;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar no-print">
                <h3 class="text-white text-center mb-4">Barangay System</h3>
                <nav>
                    <a href="index.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a href="residents.php"><i class="fas fa-users me-2"></i> Residents</a>
                    <a href="clearance.php" class="active"><i class="fas fa-file-alt me-2"></i> Clearance</a>
                    <a href="indigency.php"><i class="fas fa-certificate me-2"></i> Indigency</a>
                    <a href="blotter.php"><i class="fas fa-book me-2"></i> Blotter</a>
                    <a href="officials.php"><i class="fas fa-user-tie me-2"></i> Officials</a>
                    <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Barangay Clearance Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestClearanceModal">
                            <i class="fas fa-plus"></i> Request Clearance
                        </button>
                    </div>
                </div>

                <!-- Clearance Table -->
                <div class="table-responsive">
                    <table id="clearanceTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>OR Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clearances as $clearance): ?>
                            <tr>
                                <td><?= $clearance['id'] ?></td>
                                <td><?= $clearance['resident_name'] ?></td>
                                <td><?= $clearance['purpose'] ?></td>
                                <td><?= $clearance['issue_date'] ?></td>
                                <td><?= $clearance['expiry_date'] ?></td>
                                <td><?= $clearance['or_number'] ?></td>
                                <td>₱<?= number_format($clearance['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $clearance['status'] == 'Approved' ? 'success' : 
                                        ($clearance['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $clearance['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="printClearance(<?= $clearance['id'] ?>)" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Clearance Modal -->
    <div class="modal fade" id="requestClearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Barangay Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Resident</label>
                            <select name="resident_id" class="form-control" required>
                                <option value="">Select Resident</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Purpose</label>
                            <textarea name="purpose" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>OR Number</label>
                            <input type="text" name="or_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="request_clearance" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Template (Hidden) -->
    <div class="print-certificate">
        <div class="text-center">
            <h4>Republic of the Philippines</h4>
            <h5>Province of _______________</h5>
            <h5>Municipality of _______________</h5>
            <h3>OFFICE OF THE BARANGAY CHAIRMAN</h3>
            <h2 class="mt-5">BARANGAY CLEARANCE</h2>
        </div>
        <div class="content mt-5">
            <p>TO WHOM IT MAY CONCERN:</p>
            <p class="mt-4">This is to certify that <span id="resident_name" class="fw-bold">_______________</span>, 
               of legal age, <span id="civil_status">single</span>, Filipino Citizen is a bonafide resident of this 
               Barangay.</p>
            <p>This certification is being issued upon the request of the above-named person for 
               <span id="clearance_purpose" class="fw-bold">_______________</span> purposes.</p>
            <p>Issued this <span id="issue_date">_______________</span> at the Barangay Hall, 
               <span id="barangay_name">_______________</span>.</p>
        </div>
        <div class="row mt-5">
            <div class="col-6">
                <p>OR No.: <span id="or_number">_______________</span></p>
                <p>Amount: ₱<span id="amount">_______________</span></p>
                <p>Date Issued: <span id="date_issued">_______________</span></p>
            </div>
            <div class="col-6 text-center">
                <p class="mt-5">_______________________</p>
                <p>Barangay Chairman</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#clearanceTable').DataTable();
        });

        function printClearance(id) {
            // In a real implementation, you would fetch the clearance details via AJAX
            // and populate the print template before printing
            window.print();
        }
    </script>
</body>
</html>
