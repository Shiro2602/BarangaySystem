<?php
require_once 'auth_check.php';
require_once 'config.php';

// Handle indigency request submission
if (isset($_POST['request_indigency'])) {
    $stmt = $pdo->prepare("INSERT INTO indigency (resident_id, purpose, issue_date, or_number) 
                          VALUES (?, ?, CURDATE(), ?)");
    $stmt->execute([
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['or_number']
    ]);
}

// Get all indigency requests
$stmt = $pdo->query("SELECT i.*, CONCAT(r.last_name, ', ', r.first_name, ' ', r.middle_name) as resident_name 
                     FROM indigency i 
                     JOIN residents r ON i.resident_id = r.id 
                     ORDER BY i.issue_date DESC");
$certificates = $stmt->fetchAll();

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
    <title>Certificate of Indigency - Barangay System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="css/print.css" rel="stylesheet">
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
                    <a href="clearance.php"><i class="fas fa-file-alt me-2"></i> Clearance</a>
                    <a href="indigency.php" class="active"><i class="fas fa-certificate me-2"></i> Indigency</a>
                    <a href="blotter.php"><i class="fas fa-book me-2"></i> Blotter</a>
                    <a href="officials.php"><i class="fas fa-user-tie me-2"></i> Officials</a>
                    <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Certificate of Indigency Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestIndigencyModal">
                            <i class="fas fa-plus"></i> Request Certificate
                        </button>
                    </div>
                </div>

                <!-- Indigency Table -->
                <div class="table-responsive">
                    <table id="indigencyTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Issue Date</th>
                                <th>OR Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                            <tr data-id="<?= $cert['id'] ?>">
                                <td><?= $cert['id'] ?></td>
                                <td class="resident-name"><?= $cert['resident_name'] ?></td>
                                <td class="purpose"><?= $cert['purpose'] ?></td>
                                <td class="issue-date"><?= $cert['issue_date'] ?></td>
                                <td class="or-number"><?= $cert['or_number'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $cert['status'] == 'Approved' ? 'success' : 
                                        ($cert['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $cert['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="printIndigency(<?= $cert['id'] ?>)" title="Print">
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

    <!-- Request Indigency Modal -->
    <div class="modal fade" id="requestIndigencyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Certificate of Indigency</h5>
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="request_indigency" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Template (Hidden) -->
    <div id="certificateTemplate" class="print-certificate">
        <div class="certificate-header">
            <h4>Republic of the Philippines</h4>
            <h5>Province of _______________</h5>
            <h5>Municipality of _______________</h5>
            <h4 class="mt-4">OFFICE OF THE BARANGAY CHAIRMAN</h4>
            <h3 class="mt-4">CERTIFICATE OF INDIGENCY</h3>
        </div>
        
        <div class="certificate-body">
            <p class="mb-4">TO WHOM IT MAY CONCERN:</p>
            
            <p>This is to certify that <span class="resident-name">_______________</span>, of legal age, Filipino Citizen is a bonafide resident of this Barangay and belongs to the indigent family in this Barangay.</p>
            
            <p>This certification is being issued upon the request of the above-named person for <span class="purpose">_______________</span> purposes.</p>
            
            <p>Issued this <span class="issue-date">_______________</span> at the Barangay Hall, _______________.</p>
            
            <p class="mb-4">OR No.: <span class="or-number">_______________</span></p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-line">
                Barangay Chairman
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#indigencyTable').DataTable();
        });

        function printIndigency(id) {
            // Get indigency data from the table row
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) return;
            
            // Update certificate template with data
            document.querySelector('.resident-name').textContent = row.querySelector('.resident-name').textContent;
            document.querySelector('.purpose').textContent = row.querySelector('.purpose').textContent;
            document.querySelector('.issue-date').textContent = new Date(row.querySelector('.issue-date').textContent).toLocaleDateString();
            document.querySelector('.or-number').textContent = row.querySelector('.or-number').textContent;
            
            // Print the certificate
            window.print();
        }
    </script>
</body>
</html>
