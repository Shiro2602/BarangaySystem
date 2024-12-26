<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'includes/permissions.php';

// Add Resident
if (isset($_POST['add_resident'])) {
    checkPermissionAndRedirect('create_resident');
    
    // Calculate age
    $birthDate = new DateTime($_POST['birthdate']);
    $today = new DateTime();
    $age = $birthDate->diff($today)->y;
    
    $stmt = $conn->prepare("INSERT INTO residents (first_name, middle_name, last_name, birthdate, gender, civil_status, address, contact_number, email, occupation, age) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssi", 
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['last_name'],
        $_POST['birthdate'],
        $_POST['gender'],
        $_POST['civil_status'],
        $_POST['address'],
        $_POST['contact_number'],
        $_POST['email'],
        $_POST['occupation'],
        $age
    );
    $stmt->execute();
}

// Edit Resident
if (isset($_POST['edit_resident'])) {
    checkPermissionAndRedirect('edit_resident');
    
    // Calculate age
    $birthDate = new DateTime($_POST['birthdate']);
    $today = new DateTime();
    $age = $birthDate->diff($today)->y;
    
    $stmt = $conn->prepare("UPDATE residents SET 
        first_name = ?, 
        middle_name = ?, 
        last_name = ?, 
        birthdate = ?, 
        gender = ?, 
        civil_status = ?, 
        address = ?, 
        contact_number = ?, 
        email = ?, 
        occupation = ?,
        age = ? 
        WHERE id = ?");
    $stmt->bind_param("ssssssssssii", 
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['last_name'],
        $_POST['birthdate'],
        $_POST['gender'],
        $_POST['civil_status'],
        $_POST['address'],
        $_POST['contact_number'],
        $_POST['email'],
        $_POST['occupation'],
        $age,
        $_POST['resident_id']
    );
    $stmt->execute();
}

// Delete Resident
if (isset($_POST['delete_resident'])) {
    checkPermissionAndRedirect('delete_resident');
    $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
    $stmt->bind_param("i", $_POST['resident_id']);
    $stmt->execute();
}

// Get Residents
$result = $conn->query("SELECT * FROM residents ORDER BY last_name, first_name");
$residents = $result->fetch_all(MYSQLI_ASSOC);
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
            <?php require_once __DIR__ . '/includes/header.php'; ?>
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Residents Management</h2>
                        <?php if (checkUserPermission('create_resident')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResidentModal">
                            <i class="fas fa-plus"></i> Add New Resident
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Residents Table -->
                <div class="table-responsive">
                    <table id="residentsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Birthdate</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($residents as $resident): ?>
                            <tr>
                                <td><?= $resident['last_name'] . ', ' . $resident['first_name'] . ' ' . $resident['middle_name'] ?></td>
                                <td><?= $resident['birthdate'] ?></td>
                                <td><?= $resident['age'] ?></td>
                                <td><?= $resident['gender'] ?></td>
                                <td><?= $resident['address'] ?></td>
                                <td><?= $resident['contact_number'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info view-resident" data-id="<?= $resident['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewResidentModal" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (checkUserPermission('edit_resident')): ?>
                                    <button class="btn btn-sm btn-warning edit-resident" data-id="<?= $resident['id'] ?>" data-bs-toggle="modal" data-bs-target="#editResidentModal" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (checkUserPermission('delete_resident')): ?>
                                    <button class="btn btn-sm btn-danger delete-resident" data-id="<?= $resident['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteResidentModal" title="Delete">
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

    <!-- View Resident Modal -->
    <div class="modal fade" id="viewResidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Resident Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>First Name</label>
                            <input type="text" id="view_first_name" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Middle Name</label>
                            <input type="text" id="view_middle_name" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Last Name</label>
                            <input type="text" id="view_last_name" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Birthdate</label>
                            <input type="date" id="view_birthdate" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Age</label>
                            <input type="text" id="view_age" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Gender</label>
                            <input type="text" id="view_gender" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Civil Status</label>
                            <input type="text" id="view_civil_status" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea id="view_address" class="form-control" readonly></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Contact Number</label>
                            <input type="text" id="view_contact_number" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Email</label>
                            <input type="email" id="view_email" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Occupation</label>
                            <input type="text" id="view_occupation" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Resident Modal -->
    <div class="modal fade" id="editResidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Resident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="resident_id" id="edit_resident_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>First Name</label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" id="edit_middle_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Last Name</label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Birthdate</label>
                                <input type="date" name="birthdate" id="edit_birthdate" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Age</label>
                                <input type="text" id="edit_age" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label>Gender</label>
                                <select name="gender" id="edit_gender" class="form-control" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="address" id="edit_address" class="form-control" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Contact Number</label>
                                <input type="text" name="contact_number" id="edit_contact_number" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Occupation</label>
                                <input type="text" name="occupation" id="edit_occupation" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_resident" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Resident Modal -->
    <div class="modal fade" id="deleteResidentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Resident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="resident_id" id="delete_resident_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this resident? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_resident" class="btn btn-danger">Delete</button>
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
            // Initialize DataTable
            var table = $('#residentsTable').DataTable({
                columnDefs: [
                    { targets: [1, 2, 3, 4, 5, 6], orderable: false }, // Added index 6 (actions column) to disable sorting
                ]
            });

            // View Resident
            $('.view-resident').click(function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                var resident = <?php echo json_encode($residents); ?>.find(r => r.id == id);
                
                if (resident) {
                    $('#view_first_name').val(resident.first_name);
                    $('#view_middle_name').val(resident.middle_name);
                    $('#view_last_name').val(resident.last_name);
                    $('#view_birthdate').val(resident.birthdate);
                    $('#view_age').val(resident.age);
                    $('#view_gender').val(resident.gender);
                    $('#view_civil_status').val(resident.civil_status);
                    $('#view_address').val(resident.address);
                    $('#view_contact_number').val(resident.contact_number);
                    $('#view_email').val(resident.email);
                    $('#view_occupation').val(resident.occupation);
                }
            });

            // Edit Resident
            $('.edit-resident').click(function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                var resident = <?php echo json_encode($residents); ?>.find(r => r.id == id);
                
                if (resident) {
                    $('#edit_resident_id').val(resident.id);
                    $('#edit_first_name').val(resident.first_name);
                    $('#edit_middle_name').val(resident.middle_name);
                    $('#edit_last_name').val(resident.last_name);
                    $('#edit_birthdate').val(resident.birthdate);
                    $('#edit_age').val(resident.age);
                    $('#edit_gender').val(resident.gender);
                    $('#edit_civil_status').val(resident.civil_status);
                    $('#edit_address').val(resident.address);
                    $('#edit_contact_number').val(resident.contact_number);
                    $('#edit_email').val(resident.email);
                    $('#edit_occupation').val(resident.occupation);
                }
            });

            // Delete Resident
            $('.delete-resident').click(function() {
                var id = $(this).data('id');
                $('#delete_resident_id').val(id);
            });
        });
    </script>
</body>
</html>
