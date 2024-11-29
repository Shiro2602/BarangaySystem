<?php
require_once 'config.php';

// Add Resident
if (isset($_POST['add_resident'])) {
    $stmt = $pdo->prepare("INSERT INTO residents (first_name, middle_name, last_name, birthdate, gender, civil_status, address, contact_number, email, occupation) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['last_name'],
        $_POST['birthdate'],
        $_POST['gender'],
        $_POST['civil_status'],
        $_POST['address'],
        $_POST['contact_number'],
        $_POST['email'],
        $_POST['occupation']
    ]);
}

// Get Residents
$stmt = $pdo->query("SELECT * FROM residents ORDER BY last_name, first_name");
$residents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents Management - Barangay System</title>
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
                    <a href="residents.php" class="active"><i class="fas fa-users me-2"></i> Residents</a>
                    <a href="clearance.php"><i class="fas fa-file-alt me-2"></i> Clearance</a>
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
                        <h2>Residents Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResidentModal">
                            <i class="fas fa-plus"></i> Add New Resident
                        </button>
                    </div>
                </div>

                <!-- Residents Table -->
                <div class="table-responsive">
                    <table id="residentsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Birthdate</th>
                                <th>Gender</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($residents as $resident): ?>
                            <tr>
                                <td><?= $resident['id'] ?></td>
                                <td><?= $resident['last_name'] . ', ' . $resident['first_name'] . ' ' . $resident['middle_name'] ?></td>
                                <td><?= $resident['birthdate'] ?></td>
                                <td><?= $resident['gender'] ?></td>
                                <td><?= $resident['address'] ?></td>
                                <td><?= $resident['contact_number'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Resident Modal -->
    <div class="modal fade" id="addResidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Resident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Birthdate</label>
                                <input type="date" name="birthdate" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Gender</label>
                                <select name="gender" class="form-control" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Civil Status</label>
                                <select name="civil_status" class="form-control" required>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Divorced">Divorced</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Contact Number</label>
                                <input type="text" name="contact_number" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_resident" class="btn btn-primary">Save Resident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#residentsTable').DataTable();
        });
    </script>
</body>
</html>
