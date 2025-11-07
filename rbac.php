<?php
// rbac.php
function canEditFulfillment() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'operator']);
}

function canEditAssurance() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'operator']);
}

function canEditBilling() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'operator']);
}
?>