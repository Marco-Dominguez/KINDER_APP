<?php
// roles.php
session_start();
require 'config.php';

// only admin
if (!isset($_SESSION['loggedIn']) || $_SESSION['userRole'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$rolesQuery = $connection->query("SELECT * FROM roles");
$roles = $rolesQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Role Management</title>
</head>
<body>
    <h1>Role Management</h1>
    <a href="index.php">Back to Home</a>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Role Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
            <tr>
                <td><?php echo $role['role_id']; ?></td>
                <td><?php echo $role['role_name']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>