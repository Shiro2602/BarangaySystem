<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'includes/permissions.php';

// Check if user has permission to view officials
checkPermissionAndRedirect('view_officials');

// Handle official submission
if (isset($_POST['add_official'])) {
    checkPermissionAndRedirect('create_official');
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

// Handle editing official
if (isset($_POST['edit_official'])) {
    checkPermissionAndRedirect('edit_official');
    $stmt = $conn->prepare("UPDATE officials SET position = ?, first_name = ?, last_name = ?, 
                           contact_number = ?, term_start = ?, term_end = ? WHERE id = ?");
    $stmt->bind_param("ssssssi",
        $_POST['position'],
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['contact_number'],
        $_POST['term_start'],
        $_POST['term_end'],
        $_POST['official_id']
    );
    $stmt->execute();
}

// Handle ending official's term
if (isset($_POST['end_term'])) {
    checkPermissionAndRedirect('end_official_term');
    $stmt = $conn->prepare("UPDATE officials SET status = 'Inactive' WHERE id = ?");
    $stmt->bind_param("i", $_POST['official_id']);
    $stmt->execute();
}

// Get all active officials
$query = "SELECT * FROM officials WHERE status = 'Active' ORDER BY position";
$result = $conn->query($query);
$officials = $result->fetch_all(MYSQLI_ASSOC);

// Get inactive officials for history
$history_query = "SELECT * FROM officials WHERE status = 'Inactive' ORDER BY term_end DESC";
$history_result = $conn->query($history_query);
$history_officials = $history_result->fetch_all(MYSQLI_ASSOC);
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
                        <?php if (checkUserPermission('create_official')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfficialModal">
                            <i class="fas fa-plus"></i> Add Official
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Officials Table -->
                <div class="row mt-4">
                    <div class="col-12">
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs" id="officialsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" 
                                        type="button" role="tab" aria-controls="current" aria-selected="true">
                                    Current Officials
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" 
                                        type="button" role="tab" aria-controls="history" aria-selected="false">
                                    Officials History
                                </button>
                            </li>
                        </ul>

                        <!-- Tabs Content -->
                        <div class="tab-content" id="officialsTabContent">
                            <!-- Current Officials Tab -->
                            <div class="tab-pane fade show active" id="current" role="tabpanel" aria-labelledby="current-tab">
                                <div class="card border-top-0">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="currentOfficialsTable" class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Position</th>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>Term Start</th>
                                                        <th>Term End</th>
                                                        <th>Status</th>
                                                        <?php if (checkUserPermission('edit_official') || checkUserPermission('end_official_term')): ?>
                                                            <th>Actions</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($officials as $official): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($official['position']) ?></td>
                                                        <td><?= htmlspecialchars($official['first_name'] . ' ' . $official['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($official['contact_number']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($official['term_start'])) ?></td>
                                                        <td><?= date('M d, Y', strtotime($official['term_end'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $official['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                                                <?= $official['status'] ?>
                                                            </span>
                                                        </td>
                                                        <?php if (checkUserPermission('edit_official') || checkUserPermission('end_official_term')): ?>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <?php if (checkUserPermission('edit_official')): ?>
                                                                    <button class="btn btn-sm btn-warning" 
                                                                            title="Edit" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editOfficialModal<?= $official['id'] ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if (checkUserPermission('end_official_term')): ?>
                                                                    <button class="btn btn-sm btn-danger" 
                                                                            title="End Term" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#endTermModal<?= $official['id'] ?>">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Officials History Tab -->
                            <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                                <div class="card border-top-0">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="historyOfficialsTable" class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Position</th>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>Term Start</th>
                                                        <th>Term End</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($history_officials as $official): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($official['position']) ?></td>
                                                        <td><?= htmlspecialchars($official['first_name'] . ' ' . $official['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($official['contact_number']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($official['term_start'])) ?></td>
                                                        <td><?= date('M d, Y', strtotime($official['term_end'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $official['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                                                <?= $official['status'] ?>
                                                            </span>
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

                <?php foreach ($officials as $official): ?>
                <!-- Edit Official Modal -->
                <div class="modal fade" id="editOfficialModal<?= $official['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Official</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label>Position</label>
                                        <select name="position" class="form-control" required>
                                            <option value="<?= $official['position'] ?>"><?= $official['position'] ?></option>
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
                                            <input type="text" name="first_name" value="<?= $official['first_name'] ?>" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Last Name</label>
                                            <input type="text" name="last_name" value="<?= $official['last_name'] ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label>Contact Number</label>
                                        <input type="text" name="contact_number" value="<?= $official['contact_number'] ?>" class="form-control" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Term Start</label>
                                            <input type="date" name="term_start" value="<?= $official['term_start'] ?>" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Term End</label>
                                            <input type="date" name="term_end" value="<?= $official['term_end'] ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <input type="hidden" name="official_id" value="<?= $official['id'] ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="edit_official" class="btn btn-primary">Edit Official</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php foreach ($officials as $official): ?>
                <!-- End Term Modal -->
                <div class="modal fade" id="endTermModal<?= $official['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">End Term</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <p>Are you sure you want to end the term of <?= $official['first_name'] . ' ' . $official['last_name'] ?>?</p>
                                    <input type="hidden" name="official_id" value="<?= $official['id'] ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="end_term" class="btn btn-danger">End Term</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
                <script>
                    $(document).ready(function() {
                        let currentTable = $('#currentOfficialsTable').DataTable({
                            order: [[3, 'desc']],
                            responsive: true
                        });

                        let historyTable = $('#historyOfficialsTable').DataTable({
                            order: [[3, 'desc']],
                            responsive: true
                        });

                        // Redraw tables when switching tabs to fix column widths
                        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                            currentTable.columns.adjust().draw();
                            historyTable.columns.adjust().draw();
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</body>
</html>
