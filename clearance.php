<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'includes/permissions.php';

// Add Clearance
if (isset($_POST['add_clearance'])) {
    checkPermissionAndRedirect('create_clearance');
    
    // For new clearances, set dates to empty strings instead of NULL
    // MySQL will convert empty strings to NULL for DATE columns
    $issue_date = '';
    $expiry_date = '';
    
    $stmt = $conn->prepare("INSERT INTO clearances (resident_id, purpose, issue_date, expiry_date, or_number, amount, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssds",
        $_POST['resident_id'],
        $_POST['purpose'],
        $issue_date,
        $expiry_date,
        $_POST['or_number'],
        $_POST['amount'],
        $_POST['status']
    );
    $stmt->execute();
}

// Edit Clearance
if (isset($_POST['edit_clearance'])) {
    checkPermissionAndRedirect('edit_clearance');
    
    // Get the current status from the database
    $stmt = $conn->prepare("SELECT status FROM clearances WHERE id = ?");
    $stmt->bind_param("i", $_POST['clearance_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_status = $result->fetch_assoc()['status'];
    
    // Set dates based on status change
    $issue_date = '';
    $expiry_date = '';
    
    if ($_POST['status'] === 'Approved') {
        // If status is being changed to Approved
        if ($current_status !== 'Approved') {
            $issue_date = date('Y-m-d');
            $expiry_date = date('Y-m-d', strtotime('+1 year'));
        } else {
            // If already approved, keep existing dates
            $stmt = $conn->prepare("SELECT issue_date, expiry_date FROM clearances WHERE id = ?");
            $stmt->bind_param("i", $_POST['clearance_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $dates = $result->fetch_assoc();
            $issue_date = $dates['issue_date'] ?: '';
            $expiry_date = $dates['expiry_date'] ?: '';
        }
    }
    
    $stmt = $conn->prepare("UPDATE clearances SET 
        resident_id = ?, 
        purpose = ?, 
        issue_date = NULLIF(?, ''), 
        expiry_date = NULLIF(?, ''), 
        or_number = ?, 
        amount = ?, 
        status = ? 
        WHERE id = ?");
    $stmt->bind_param("issssdsi",
        $_POST['resident_id'],
        $_POST['purpose'],
        $issue_date,
        $expiry_date,
        $_POST['or_number'],
        $_POST['amount'],
        $_POST['status'],
        $_POST['clearance_id']
    );
    $stmt->execute();
}

// Delete Clearance
if (isset($_POST['delete_clearance'])) {
    checkPermissionAndRedirect('delete_clearance');
    $stmt = $conn->prepare("DELETE FROM clearances WHERE id = ?");
    $stmt->bind_param("i", $_POST['clearance_id']);
    $stmt->execute();
}

// Get all clearances with resident information
$query = "SELECT c.*, 
          CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) as resident_name,
          r.birthdate,
          r.civil_status,
          r.address,
          TIMESTAMPDIFF(YEAR, r.birthdate, CURDATE()) as age
          FROM clearances c 
          LEFT JOIN residents r ON c.resident_id = r.id 
          ORDER BY c.issue_date DESC";
$result = $conn->query($query);
$clearances = $result->fetch_all(MYSQLI_ASSOC);

// Get all residents for the dropdown
$residents_query = "SELECT 
    id,
    CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, '')) as full_name
    FROM residents 
    ORDER BY last_name, first_name";
$residents_result = $conn->query($residents_query);
$residents = $residents_result->fetch_all(MYSQLI_ASSOC);
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
        .select2-container {
            width: 100% !important;
        }
        /* Select2 Custom Styles */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }
        .select2-container--bootstrap-5 .select2-selection--single {
            height: 38px;
            padding: 0;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 5px 12px;
            line-height: 26px;
            color: #212529;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            position: absolute;
            right: 3px;
            width: 20px;
        }
        .select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
            padding: 6px 12px;
        }
        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            padding: 6px 12px;
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/header.php'; ?>
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Barangay Clearance Management</h2>
                        <?php if (checkUserPermission('create_clearance')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestClearanceModal">
                            <i class="fas fa-plus"></i> Request Clearance
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Clearance Table -->
                <div class="table-responsive">
                    <table id="clearanceTable" class="table table-striped">
                        <thead>
                            <tr>
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
                            <tr data-id="<?= $clearance['id'] ?>">
                                <td class="resident-name"><?= htmlspecialchars($clearance['resident_name']) ?></td>
                                <td class="purpose"><?= htmlspecialchars($clearance['purpose']) ?></td>
                                <td class="issue-date"><?= $clearance['issue_date'] ? $clearance['issue_date'] : '-' ?></td>
                                <td><?= $clearance['expiry_date'] ? $clearance['expiry_date'] : '-' ?></td>
                                <td class="or-number"><?= htmlspecialchars($clearance['or_number']) ?></td>
                                <td class="amount"><?= number_format($clearance['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $clearance['status'] == 'Approved' ? 'success' : 
                                        ($clearance['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars($clearance['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm view-clearance" 
                                            data-id="<?= $clearance['id'] ?>" 
                                            data-resident="<?= htmlspecialchars($clearance['resident_name']) ?>" 
                                            data-purpose="<?= htmlspecialchars($clearance['purpose']) ?>" 
                                            data-issue-date="<?= $clearance['issue_date'] ?>" 
                                            data-expiry-date="<?= $clearance['expiry_date'] ?>" 
                                            data-or-number="<?= htmlspecialchars($clearance['or_number']) ?>" 
                                            data-amount="<?= number_format($clearance['amount'], 2) ?>" 
                                            data-status="<?= htmlspecialchars($clearance['status']) ?>" 
                                            data-bs-toggle="modal" data-bs-target="#viewClearanceModal">
                                        <i class="fas fa-eye"></i> 
                                    </button>
                                    <?php if (checkUserPermission('print_clearance')): ?>
                                    <button type="button" class="btn btn-sm btn-info print-docx"
                                            data-resident="<?= htmlspecialchars($clearance['resident_name']) ?>" 
                                            data-age="<?= $clearance['age'] ?>"
                                            data-civil-status="<?= htmlspecialchars($clearance['civil_status']) ?>"
                                            data-address="<?= htmlspecialchars($clearance['address']) ?>"
                                            data-purpose="<?= htmlspecialchars($clearance['purpose']) ?>"
                                            data-issue-date="<?= $clearance['issue_date'] ?>">
                                        <i class="fas fa-file-word"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (checkUserPermission('edit_clearance')): ?>
                                    <button class="btn btn-sm btn-warning edit-clearance" 
                                        data-id="<?= $clearance['id'] ?>" 
                                        data-resident-id="<?= $clearance['resident_id'] ?>"
                                        data-purpose="<?= htmlspecialchars($clearance['purpose']) ?>"
                                        data-or-number="<?= htmlspecialchars($clearance['or_number']) ?>"
                                        data-amount="<?= $clearance['amount'] ?>"
                                        data-status="<?= htmlspecialchars($clearance['status']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editClearanceModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (checkUserPermission('delete_clearance')): ?>
                                    <button class="btn btn-sm btn-danger delete-clearance" data-id="<?= $clearance['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteClearanceModal" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
                            <select name="resident_id" class="form-select select2" required>
                                <option value="">Search for resident...</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?= htmlspecialchars($resident['id']) ?>">
                                        <?= htmlspecialchars($resident['full_name']) ?>
                                    </option>
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
                        <input type="hidden" name="status" value="Pending">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_clearance" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Clearance Modal -->
    <div class="modal fade" id="viewClearanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Clearance Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Resident Name</label>
                            <p class="view-resident-name"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose</label>
                            <p class="view-purpose"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Issue Date</label>
                            <p class="view-issue-date"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <p class="view-expiry-date"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">OR Number</label>
                            <p class="view-or-number"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <p class="view-amount"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <p class="view-status"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Clearance Modal -->
    <div class="modal fade" id="editClearanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="clearance_id" id="edit_clearance_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Resident</label>
                                <select name="resident_id" id="edit_resident_id" class="form-select select2" required>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= htmlspecialchars($resident['id']) ?>">
                                            <?= htmlspecialchars($resident['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Purpose</label>
                                <input type="text" name="purpose" id="edit_purpose" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>OR Number</label>
                                <input type="text" name="or_number" id="edit_or_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Amount</label>
                                <input type="number" name="amount" id="edit_amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_clearance" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Clearance Modal -->
    <div class="modal fade" id="deleteClearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="clearance_id" id="delete_clearance_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this clearance? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_clearance" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#clearanceTable').DataTable({
                columnDefs: [
                    { targets: [1, 2, 3, 4, 5, 6, 7], orderable: false }
                ],
                order: [[0, 'asc']],
                language: {
                    search: "Search Clearance:"
                }
            });

            // Initialize Select2
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(this).closest('.modal'),
                    placeholder: 'Search for resident...'
                });
            });

            // Reset form when modal is hidden
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
                $(this).find('.select2').val('').trigger('change');
            });

            // View Clearance
            $('.view-clearance').click(function() {
                var id = $(this).data('id');
                var resident = $(this).data('resident');
                var purpose = $(this).data('purpose');
                var issueDate = $(this).data('issue-date');
                var expiryDate = $(this).data('expiry-date');
                var orNumber = $(this).data('or-number');
                var amount = $(this).data('amount');
                var status = $(this).data('status');

                // Populate the view modal
                $('#viewClearanceModal .view-resident-name').text(resident);
                $('#viewClearanceModal .view-purpose').text(purpose);
                $('#viewClearanceModal .view-issue-date').text(issueDate);
                $('#viewClearanceModal .view-expiry-date').text(expiryDate);
                $('#viewClearanceModal .view-or-number').text(orNumber);
                $('#viewClearanceModal .view-amount').text(amount);
                $('#viewClearanceModal .view-status').text(status);
            });

            // Print DOCX certificate
            $('.print-docx').click(function() {
                var data = {
                    resident_name: $(this).data('resident'),
                    age: $(this).data('age'),
                    civil_status: $(this).data('civil-status'),
                    address: $(this).data('address'),
                    purpose: $(this).data('purpose'),
                    issue_date: $(this).data('issue-date')
                };

                // Send AJAX request to process_docx.php
                $.ajax({
                    url: 'process_docx.php',
                    type: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.file) {
                            window.location.href = 'download_clearance.php?file=' + response.file;
                        } else if (response.error) {
                            alert('Error: ' + response.error);
                        } else {
                            alert('Unknown error occurred');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });

            // Delete Clearance
            $('.delete-clearance').click(function() {
                var id = $(this).data('id');
                $('#delete_clearance_id').val(id);
            });

            // Edit Clearance
            $('.edit-clearance').click(function() {
                var button = $(this);
                $('#edit_clearance_id').val(button.data('id'));
                $('#edit_resident_id').val(button.data('resident-id')).trigger('change');
                $('#edit_purpose').val(button.data('purpose'));
                $('#edit_or_number').val(button.data('or-number'));
                $('#edit_amount').val(button.data('amount'));
                $('#edit_status').val(button.data('status'));
            });
        });
    </script>
</body>
</html>
