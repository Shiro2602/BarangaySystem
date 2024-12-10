<?php
// Function to check if user has permission for an action
function checkUserPermission($action) {
    if (!isset($_SESSION['role'])) {
        return false;
    }

    $role = $_SESSION['role'];
    
    // Define permissions for different roles
    $permissions = [
        'admin' => [
            'create_resident' => true,
            'edit_resident' => true,
            'delete_resident' => true,
            'view_resident' => true,
            'create_household' => true,
            'edit_household' => true,
            'delete_household' => true,
            'view_household' => true,
            'create_indigency' => true,
            'edit_indigency' => true,
            'delete_indigency' => true,
            'view_indigency' => true,
            'print_indigency' => true,
            'create_clearance' => true,
            'edit_clearance' => true,
            'delete_clearance' => true,
            'view_clearance' => true,
            'print_clearance' => true,
            'view_officials' => true,
            'create_official' => true,
            'edit_official' => true,
            'end_official_term' => true,
            'create_blotter' => true,
            'edit_blotter' => true,
            'delete_blotter' => true,
            'view_blotter' => true,
            'change_blotter_status' => true
        ],
        'secretary' => [
            'create_resident' => false,
            'edit_resident' => false,
            'delete_resident' => false,
            'view_resident' => true,
            'create_household' => false,
            'edit_household' => false,
            'delete_household' => false,
            'view_household' => true,
            'create_indigency' => true,
            'edit_indigency' => false,
            'delete_indigency' => false,
            'view_indigency' => true,
            'print_indigency' => false,
            'create_clearance' => true,
            'edit_clearance' => false,
            'delete_clearance' => false,
            'view_clearance' => true,
            'print_clearance' => false,
            'view_officials' => false,
            'create_official' => false,
            'edit_official' => false,
            'end_official_term' => false,
            'create_blotter' => true,
            'edit_blotter' => true,
            'delete_blotter' => false,
            'view_blotter' => true,
            'change_blotter_status' => false
        ]
    ];

    return isset($permissions[$role][$action]) ? $permissions[$role][$action] : false;
}

// Function to check permission and redirect if not allowed
function checkPermissionAndRedirect($action) {
    if (!checkUserPermission($action)) {
        $_SESSION['error'] = "You don't have permission to perform this action.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
