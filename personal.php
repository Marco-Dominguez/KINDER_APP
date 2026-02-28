<?php
// personal.php
session_start();

// validate session
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

ob_start();
require 'config.php';

$currentUserRole = $_SESSION['userRole'] ?? '';
$actionFeedback = "";

// load info in the form if edit
$editingStaff = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editQuery = $connection->prepare("SELECT * FROM personal WHERE id_per = ?");
    $editQuery->execute([$editId]);
    $editingStaff = $editQuery->fetch(PDO::FETCH_ASSOC);
}

// process create update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['formAction'] ?? '';

    if ($formAction === 'create') {
        $selectedUserId = $_POST['userId'];
        $staffName      = $_POST['staffName'];
        $staffEmail     = $_POST['staffEmail'];
        $staffPhone     = $_POST['staffPhone'];

        // id_usu exist validation
        $validateUserQuery = $connection->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usu = ?");
        $validateUserQuery->execute([$selectedUserId]);
        $userExists = $validateUserQuery->fetchColumn();

        if ($userExists) {
            // insert staff record
            $insertQuery = $connection->prepare("INSERT INTO personal (id_usu, maestra_per, correo_per, cel_per) VALUES (?, ?, ?, ?)");
            if ($insertQuery->execute([$selectedUserId, $staffName, $staffEmail, $staffPhone])) {
                header("Location: personal.php");
                exit();
            } else {
                $actionFeedback = "Error al guardar el registro.";
            }
        } else {
            $actionFeedback = "Error de validación: La cuenta de usuario seleccionada no existe.";
        }
    } elseif ($formAction === 'update') {
        $targetId       = (int)$_POST['targetStaffId'];
        $selectedUserId = $_POST['userId'];
        $staffName      = $_POST['staffName'];
        $staffEmail     = $_POST['staffEmail'];
        $staffPhone     = $_POST['staffPhone'];

        $updateQuery = $connection->prepare("UPDATE personal SET id_usu = ?, maestra_per = ?, correo_per = ?, cel_per = ? WHERE id_per = ?");
        if ($updateQuery->execute([$selectedUserId, $staffName, $staffEmail, $staffPhone, $targetId])) {
            header("Location: personal.php");
            exit();
        } else {
            $actionFeedback = "Error al actualizar el registro.";
        }
    } elseif ($formAction === 'delete') {
        $targetStaffId = $_POST['targetStaffId'];
        
        // delete staff record
        $deleteQuery = $connection->prepare("DELETE FROM personal WHERE id_per = ?");
        if ($deleteQuery->execute([$targetStaffId])) {
            header("Location: personal.php");
            exit();
        } else {
            $actionFeedback = "Error al eliminar el registro.";
        }
    }
}

// get available users for dropdown
$fetchUsersQuery = $connection->query("SELECT id_usu, usuario_usu FROM usuarios");
$availableUsers  = $fetchUsersQuery->fetchAll(PDO::FETCH_ASSOC);

// get staff records for table display
$fetchStaffQuery = $connection->query("
    SELECT p.id_per, p.id_usu, u.usuario_usu, p.maestra_per, p.correo_per, p.cel_per 
    FROM personal p 
    JOIN usuarios u ON p.id_usu = u.id_usu
");
$currentStaff = $fetchStaffQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Personal</title>
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
    <h2><?= $editingStaff ? 'Editar Personal' : 'Registrar Personal' ?></h2>
    
    <?php if ($actionFeedback): ?>
        <p class="feedback"><?= htmlspecialchars($actionFeedback) ?></p>
    <?php endif; ?>

    <form action="personal.php" method="POST">
        <input type="hidden" name="formAction" value="<?= $editingStaff ? 'update' : 'create' ?>">
        <?php if ($editingStaff): ?>
            <input type="hidden" name="targetStaffId" value="<?= htmlspecialchars($editingStaff['id_per']) ?>">
        <?php endif; ?>

        <label>Cuenta de Usuario del Sistema:</label><br>
        <select name="userId" required>
            <option value="">-- Seleccionar Usuario --</option>
            <?php foreach ($availableUsers as $userAccount): ?>
                <option value="<?= htmlspecialchars($userAccount['id_usu']) ?>"
                    <?= ($editingStaff && $editingStaff['id_usu'] == $userAccount['id_usu']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($userAccount['usuario_usu']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Nombre Completo:</label><br>
        <input type="text" name="staffName" value="<?= $editingStaff ? htmlspecialchars($editingStaff['maestra_per']) : '' ?>" required><br>

        <label>Correo Electrónico:</label><br>
        <input type="email" name="staffEmail" value="<?= $editingStaff ? htmlspecialchars($editingStaff['correo_per']) : '' ?>" required><br>

        <label>Teléfono Celular:</label><br>
        <input type="tel" name="staffPhone" value="<?= $editingStaff ? htmlspecialchars($editingStaff['cel_per']) : '' ?>" required><br>

        <button type="submit"><?= $editingStaff ? 'Actualizar Registro' : 'Guardar Registro' ?></button>
        <?php if ($editingStaff): ?>
            <a href="personal.php" class="cancel-link">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

    <div class="card">
    <h2>Directorio de Personal</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cuenta Vinculada</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Celular</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentStaff as $staffMember): ?>
            <tr>
                <td><?= htmlspecialchars($staffMember['id_per']) ?></td>
                <td><?= htmlspecialchars($staffMember['usuario_usu']) ?></td>
                <td><?= htmlspecialchars($staffMember['maestra_per']) ?></td>
                <td><?= htmlspecialchars($staffMember['correo_per']) ?></td>
                <td><?= htmlspecialchars($staffMember['cel_per']) ?></td>
                <td class="action-cell">
                    <a href="personal.php?edit=<?= htmlspecialchars($staffMember['id_per']) ?>" class="btn-edit">Editar</a>
                    <form action="personal.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                        <input type="hidden" name="formAction" value="delete">
                        <input type="hidden" name="targetStaffId" value="<?= htmlspecialchars($staffMember['id_per']) ?>">
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