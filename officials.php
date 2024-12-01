<?php
require_once 'auth_check.php';
require_once 'config.php';

// Handle official submission
if (isset($_POST['add_official'])) {
    $stmt = $conn->prepare("INSERT INTO officials (position, first_name, last_name, contact_number, term_start, term_end) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss",
        $_POST['position'],
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['contact_number'],
        $_POST['term_start'],
        $_POST['term_end']
    );
    $stmt->execute();
}

// Get all active officials
$query = "SELECT * FROM officials WHERE status = 'Active' ORDER BY position";
$result = $conn->query($query);
$officials = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Officials - Barangay System</title>
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
        .official-card {
            transition: transform 0.2s;
        }
        .official-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/header.php'; ?>

            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h2>Barangay Officials</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfficialModal">
                            <i class="fas fa-plus"></i> Add Official
                        </button>
                    </div>
                </div>

                <!-- Officials Cards -->
                <div class="row">
                    <?php foreach ($officials as $official): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card official-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($official['position']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?= htmlspecialchars($official['first_name'] . ' ' . $official['last_name']) ?>
                                </h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Term: <?= date('M Y', strtotime($official['term_start'])) ?> - 
                                        <?= date('M Y', strtotime($official['term_end'])) ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-phone me-2"></i>
                                    <?= htmlspecialchars($official['contact_number']) ?>
                                </p>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" title="End Term">
                                        <i class="fas fa-user-times"></i> End Term
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Officials Table -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h3>Officials History</h3>
                        <div class="table-responsive">
                            <table id="officialsTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Term Start</th>
                                        <th>Term End</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($officials as $official): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($official['position']) ?></td>
                                        <td><?= htmlspecialchars($official['first_name'] . ' ' . $official['last_name']) ?></td>
                                        <td><?= htmlspecialchars($official['contact_number']) ?></td>
                                        <td><?= $official['term_start'] ?></td>
                                        <td><?= $official['term_end'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $official['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                                <?= $official['status'] ?>
                                            </span>
                                        </td>
                                        <td>
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
        </div>
    </div>

    <!-- Add Official Modal -->
    <div class="modal fade" id="addOfficialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Official</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Position</label>
                            <select name="position" class="form-control" required>
                                <option value="">Select Position</option>
                                <option value="Barangay Chairman">Barangay Chairman</option>
                                <option value="Barangay Secretary">Barangay Secretary</option>
                                <option value="Barangay Treasurer">Barangay Treasurer</option>
                                <option value="Kagawad">Kagawad</option>
                                <option value="SK Chairman">SK Chairman</option>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Term Start</label>
                                <input type="date" name="term_start" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Term End</label>
                                <input type="date" name="term_end" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_official" class="btn btn-primary">Add Official</button>
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
            $('#officialsTable').DataTable({
                order: [[3, 'desc']]
            });
        });
    </script>
</body>
</html>
