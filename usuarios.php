<?php
// usuarios.php
session_start();

// validate active session
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

ob_start();
require 'config.php';

$actionFeedback = "";

// load info in the form if edit
$editingUser = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editQuery = $connection->prepare(
        "SELECT u.id_usu, u.usuario_usu, u.role_id, r.role_name
        FROM usuarios u JOIN roles r ON u.role_id = r.role_id
        WHERE u.id_usu = ?");
    $editQuery->execute([$editId]);
    $editingUser = $editQuery->fetch(PDO::FETCH_ASSOC);
}

// process create update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['formAction'] ?? '';

    if ($formAction === 'create') {
        $inputUsername = $_POST['username'];
        $inputPassword = $_POST['password'];
        $inputRole     = $_POST['role'];

        // username unique validation
        $checkUserQuery = $connection->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario_usu = ?");
        $checkUserQuery->execute([$inputUsername]);
        
        if ($checkUserQuery->fetchColumn() > 0) {
            $actionFeedback = "Error: El nombre de usuario ya está en uso.";
        } else {
            // hash password and insert new user
            $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT);
            $insertQuery = $connection->prepare("INSERT INTO usuarios (usuario_usu, password_usu, role_id) VALUES (?, ?, ?)");
            if ($insertQuery->execute([$inputUsername, $hashedPassword, $inputRole])) {
                header("Location: usuarios.php");
                exit();
            } else {
                $actionFeedback = "Error al guardar el usuario.";
            }
        }
    } elseif ($formAction === 'update') {
        $targetId      = (int)$_POST['targetUserId'];
        $inputUsername = $_POST['username'];
        $inputRole     = $_POST['role'];

        $updateQuery = $connection->prepare("UPDATE usuarios SET usuario_usu = ?, role_id = ? WHERE id_usu = ?");
        if ($updateQuery->execute([$inputUsername, $inputRole, $targetId])) {
            header("Location: usuarios.php");
            exit();
        } else {
            $actionFeedback = "Error al actualizar el usuario.";
        }
    } elseif ($formAction === 'delete') {
        $targetUserId = $_POST['targetUserId'];
        
        // delete user
        try {
            $deleteQuery = $connection->prepare("DELETE FROM usuarios WHERE id_usu = ?");
            if ($deleteQuery->execute([$targetUserId])) {
                header("Location: usuarios.php");
                exit();
            } else {
                $actionFeedback = "Error al eliminar el usuario.";
            }
        } catch (PDOException $exception) {
            $actionFeedback = "Error: No se puede eliminar este usuario porque tiene personal o grupos vinculados.";
        }
    }
}

// get users for table display
$fetchUsersQuery = $connection->query(
    "SELECT u.id_usu, u.usuario_usu, r.role_name
    FROM usuarios u JOIN roles r ON u.role_id = r.role_id");
$currentUsers = $fetchUsersQuery->fetchAll(PDO::FETCH_ASSOC);

// get roles for dropdown
$fetchRolesQuery = $connection->query("SELECT role_id, role_name FROM roles");
$availableRoles  = $fetchRolesQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: sans-serif; background-color: #f4f4f9; margin: 0; padding: 30px; }
        h2 { color: #333; margin-top: 0; }
        .card { background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 25px; }
        label { font-size: 0.9em; font-weight: bold; color: #444; }
        input[type="text"], input[type="email"], input[type="tel"], select {
            width: 100%; padding: 10px; margin: 6px 0 16px 0; border: 1px solid #ccc;
            border-radius: 4px; font-size: 0.95em;
        }
        input:focus, select:focus { outline: none; border-color: #0066cc; box-shadow: 0 0 0 2px rgba(0,102,204,0.15); }
        button[type="submit"] { padding: 10px 20px; background-color: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.95em; }
        button[type="submit"]:hover { background-color: #0055aa; }
        .btn-danger { padding: 6px 12px; background-color: #cc0000; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85em; }
        .btn-danger:hover { background-color: #aa0000; }
        .btn-edit { display: inline-block; padding: 6px 12px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .btn-edit:hover { background-color: #0055aa; }
        .cancel-link { margin-left: 10px; color: #0066cc; font-size: 0.9em; }
        .feedback { color: #cc0000; font-weight: bold; margin-bottom: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.95em; }
        th { background-color: #0066cc; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #eef4ff; }
        .nav-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #0066cc; font-weight: bold; font-size: 0.9em; }
        .nav-link:hover { text-decoration: underline; }
        .action-cell { display: flex; gap: 6px; align-items: center; }
    </style>
</head>
<body>
    <a href="index.php" class="nav-link">&larr; Volver al Menú Principal</a>

    <div class="card">
    <h2><?= $editingUser ? 'Editar Usuario' : 'Registrar Nuevo Usuario' ?></h2>
    
    <?php if ($actionFeedback): ?>
        <p class="feedback"><?= htmlspecialchars($actionFeedback) ?></p>
    <?php endif; ?>

    <form action="usuarios.php" method="POST">
        <input type="hidden" name="formAction" value="<?= $editingUser ? 'update' : 'create' ?>">
        <?php if ($editingUser): ?>
            <input type="hidden" name="targetUserId" value="<?= htmlspecialchars($editingUser['id_usu']) ?>">
        <?php endif; ?>

        <label>Nombre de Usuario:</label><br>
        <input type="text" name="username" value="<?= $editingUser ? htmlspecialchars($editingUser['usuario_usu']) : '' ?>" required><br>

        <?php if (!$editingUser): ?>
        <label>Contraseña:</label><br>
        <input type="text" name="password" required><br>
        <?php endif; ?>

        <label>Rol del Sistema:</label><br>
        <select name="role" required>
            <option value="">-- Seleccionar Rol --</option>
            <?php foreach ($availableRoles as $roleItem): ?>
                <option value="<?= htmlspecialchars($roleItem['role_id']) ?>"
                    <?= ($editingUser && $editingUser['role_id'] == $roleItem['role_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($roleItem['role_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit"><?= $editingUser ? 'Actualizar Usuario' : 'Guardar Usuario' ?></button>
        <?php if ($editingUser): ?>
            <a href="usuarios.php" class="cancel-link">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

    <div class="card">
    <h2>Directorio de Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentUsers as $userData): ?>
            <tr>
                <td><?= htmlspecialchars($userData['id_usu']) ?></td>
                <td><?= htmlspecialchars($userData['usuario_usu']) ?></td>
                <td><?= htmlspecialchars($userData['role_name']) ?></td>
                <td class="action-cell">
                    <a href="usuarios.php?edit=<?= htmlspecialchars($userData['id_usu']) ?>" class="btn-edit">Editar</a>
                    <form action="usuarios.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                        <input type="hidden" name="formAction" value="delete">
                        <input type="hidden" name="targetUserId" value="<?= htmlspecialchars($userData['id_usu']) ?>">
                        <button type="submit" class="btn-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>