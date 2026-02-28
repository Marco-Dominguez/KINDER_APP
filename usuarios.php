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
    $editQuery = $connection->prepare("SELECT id_usu, usuario_usu, rol FROM usuarios WHERE id_usu = ?");
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
            $insertQuery = $connection->prepare("INSERT INTO usuarios (usuario_usu, password_usu, rol) VALUES (?, ?, ?)");
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

        $updateQuery = $connection->prepare("UPDATE usuarios SET usuario_usu = ?, rol = ? WHERE id_usu = ?");
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
$fetchUsersQuery = $connection->query("SELECT id_usu, usuario_usu, password_usu, rol FROM usuarios");
$currentUsers    = $fetchUsersQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .feedback { color: red; font-weight: bold; }
        .nav-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #0066cc; }
    </style>
</head>
<body>
    <a href="index.php" class="nav-link">&larr; Volver al Menú Principal</a>
    
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
        <input type="text" name="username" value="<?= $editingUser ? htmlspecialchars($editingUser['usuario_usu']) : '' ?>" required><br><br>

        <?php if (!$editingUser): ?>
        <label>Contraseña:</label><br>
        <input type="text" name="password" required><br><br>
        <?php endif; ?>

        <label>Rol del Sistema:</label><br>
        <select name="role" required>
            <option value="">-- Seleccionar Rol --</option>
            <option value="admin" <?= ($editingUser && $editingUser['rol'] === 'admin') ? 'selected' : '' ?>>Administrador (admin)</option>
            <option value="docente" <?= ($editingUser && $editingUser['rol'] === 'docente') ? 'selected' : '' ?>>Docente (docente)</option>
        </select><br><br>

        <button type="submit"><?= $editingUser ? 'Actualizar Usuario' : 'Guardar Usuario' ?></button>
        <?php if ($editingUser): ?>
            <a href="usuarios.php" style="margin-left:10px;">Cancelar</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Directorio de Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentUsers as $userData): ?>
            <tr>
                <td><?= htmlspecialchars($userData['id_usu']) ?></td>
                <td><?= htmlspecialchars($userData['usuario_usu']) ?></td>
                <td><?= htmlspecialchars($userData['password_usu']) ?></td>
                <td><?= htmlspecialchars($userData['rol']) ?></td>
                <td>
                    <a href="usuarios.php?edit=<?= htmlspecialchars($userData['id_usu']) ?>" style="display:inline-block;margin-bottom:4px;padding:4px 8px;background:#0066cc;color:white;text-decoration:none;border-radius:4px;font-size:0.85em;">Editar</a><br>
                    <form action="usuarios.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                        <input type="hidden" name="formAction" value="delete">
                        <input type="hidden" name="targetUserId" value="<?= htmlspecialchars($userData['id_usu']) ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php ob_end_flush(); ?>