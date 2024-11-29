<?php
require_once 'config.php';
require_once 'auth.php';

$success_message = '';
$error_message = '';

// Get current user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($current_password, $user_data['password'])) {
            // Check if username is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error_message = "Username is already taken.";
            } else {
                // Update profile
                if ($new_password !== '') {
                    // Validate new password
                    if ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                    } elseif (strlen($new_password) < 8) {
                        $error_message = "New password must be at least 8 characters long.";
                    } else {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                        $success = $stmt->execute([$username, $email, $hashed_password, $user_id]);
                    }
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $success = $stmt->execute([$username, $email, $user_id]);
                }

                if (!$error_message) {
                    if ($success) {
                        $success_message = "Profile updated successfully!";
                        // Update session username
                        $_SESSION['username'] = $username;
                        // Refresh user data
                        $user['username'] = $username;
                        $user['email'] = $email;
                    } else {
                        $error_message = "Error updating profile. Please try again.";
                    }
                }
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Barangay Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
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
                    <a href="forecast.php"><i class="fas fa-chart-line me-2"></i> Population Forecast</a>
                    <a href="account.php" class="active"><i class="fas fa-user-cog me-2"></i> Account Settings</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container py-4">
                    <h2 class="mb-4">Account Settings</h2>

                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    <div class="invalid-feedback">Please enter a username.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <hr class="my-4">
                                <h5 class="mb-3">Change Password</h5>

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <div class="invalid-feedback">Please enter your current password.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="8" placeholder="Leave blank to keep current password">
                                    <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    // Check if passwords match
                    var newPassword = document.getElementById('new_password')
                    var confirmPassword = document.getElementById('confirm_password')
                    if (newPassword.value !== '' && newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match')
                        event.preventDefault()
                        event.stopPropagation()
                    } else {
                        confirmPassword.setCustomValidity('')
                    }

                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
