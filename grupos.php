<?php
// grupos.php
session_start();

// validate active session
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

ob_start();
require 'config.php';

$currentUserRole = $_SESSION['userRole'] ?? '';
$actionFeedback = "";

// load info in the form if edit
$editingGroup = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editQuery = $connection->prepare("SELECT * FROM grupo WHERE id_gpo = ?");
    $editQuery->execute([$editId]);
    $editingGroup = $editQuery->fetch(PDO::FETCH_ASSOC);
}

// process create update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['formAction'] ?? '';

    if ($formAction === 'create') {
        $selectedUserId = $_POST['userId'];
        $groupName      = $_POST['groupName'];

        // id_usu exist validation
        $validateUserQuery = $connection->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usu = ?");
        $validateUserQuery->execute([$selectedUserId]);
        $userExists = $validateUserQuery->fetchColumn();

        if ($userExists) {
            // insert group
            $insertQuery = $connection->prepare("INSERT INTO grupo (id_usu, grupo_gpo) VALUES (?, ?)");
            if ($insertQuery->execute([$selectedUserId, $groupName])) {
                header("Location: grupos.php");
                exit();
            } else {
                $actionFeedback = "Error al guardar el grupo.";
            }
        } else {
            $actionFeedback = "Error de validación: El usuario seleccionado no existe.";
        }
    } elseif ($formAction === 'update') {
        $targetId       = (int)$_POST['targetGroupId'];
        $selectedUserId = $_POST['userId'];
        $groupName      = $_POST['groupName'];

        $updateQuery = $connection->prepare("UPDATE grupo SET id_usu = ?, grupo_gpo = ? WHERE id_gpo = ?");
        if ($updateQuery->execute([$selectedUserId, $groupName, $targetId])) {
            header("Location: grupos.php");
            exit();
        } else {
            $actionFeedback = "Error al actualizar el grupo.";
        }
    } elseif ($formAction === 'delete') {
        $targetGroupId = $_POST['targetGroupId'];
        
        // delete group
        try {
            $deleteQuery = $connection->prepare("DELETE FROM grupo WHERE id_gpo = ?");
            if ($deleteQuery->execute([$targetGroupId])) {
                header("Location: grupos.php");
                exit();
            } else {
                $actionFeedback = "Error al eliminar el grupo.";
            }
        } catch (PDOException $exception) {
            $actionFeedback = "Error: No se puede eliminar este grupo porque tiene alumnos asignados.";
        }
    }
}

// ger users for dropdown
$fetchUsersQuery = $connection->query("SELECT id_usu, usuario_usu FROM usuarios");
$availableUsers  = $fetchUsersQuery->fetchAll(PDO::FETCH_ASSOC);

// get groups for table
if ($currentUserRole === 'Alumno') {
    $currentUserId = $_SESSION['userId'];
    $fetchGroupsQuery = $connection->prepare(
        "SELECT g.id_gpo, g.id_usu, u.usuario_usu, g.grupo_gpo
        FROM grupo g
        JOIN usuarios u ON g.id_usu = u.id_usu
        JOIN alumnos a ON a.id_gpo = g.id_gpo
        WHERE a.id_usu = ?");
    $fetchGroupsQuery->execute([$currentUserId]);
} else {
    $fetchGroupsQuery = $connection->query(
        "SELECT g.id_gpo, g.id_usu, u.usuario_usu, g.grupo_gpo
        FROM grupo g
        JOIN usuarios u ON g.id_usu = u.id_usu");
}
$currentGroups = $fetchGroupsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Grupos</title>
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
    
    <?php if (in_array($currentUserRole, ['Admin', 'Docente'])): ?>
    <h2><?= $editingGroup ? 'Editar Grupo' : 'Crear Nuevo Grupo' ?></h2>
    
    <?php if ($actionFeedback): ?>
        <p class="feedback"><?= htmlspecialchars($actionFeedback) ?></p>
    <?php endif; ?>

    <form action="grupos.php" method="POST">
        <input type="hidden" name="formAction" value="<?= $editingGroup ? 'update' : 'create' ?>">
        <?php if ($editingGroup): ?>
            <input type="hidden" name="targetGroupId" value="<?= htmlspecialchars($editingGroup['id_gpo']) ?>">
        <?php endif; ?>

        <label>Asignar a Usuario (Docente):</label><br>
        <select name="userId" required>
            <option value="">-- Seleccionar Docente --</option>
            <?php foreach ($availableUsers as $userAccount): ?>
                <option value="<?= htmlspecialchars($userAccount['id_usu']) ?>"
                    <?= ($editingGroup && $editingGroup['id_usu'] == $userAccount['id_usu']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($userAccount['usuario_usu']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Nombre del Grupo:</label><br>
        <input type="text" name="groupName" value="<?= $editingGroup ? htmlspecialchars($editingGroup['grupo_gpo']) : '' ?>" required><br><br>

        <button type="submit"><?= $editingGroup ? 'Actualizar Grupo' : 'Guardar Grupo' ?></button>
        <?php if ($editingGroup): ?>
            <a href="grupos.php" style="margin-left:10px;">Cancelar</a>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <hr>

    <h2>Lista de Grupos</h2>
    <table>
        <thead>
            <tr>
                <th>ID Grupo</th>
                <th>Docente Asignado</th>
                <th>Nombre del Grupo</th>
                <?php if (in_array($currentUserRole, ['Admin', 'Docente'])): ?>
                <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentGroups as $groupData): ?>
            <tr>
                <td><?= htmlspecialchars($groupData['id_gpo']) ?></td>
                <td><?= htmlspecialchars($groupData['usuario_usu']) ?></td>
                <td><?= htmlspecialchars($groupData['grupo_gpo']) ?></td>
                <?php if (in_array($currentUserRole, ['Admin', 'Docente'])): ?>
                <td>
                    <a href="grupos.php?edit=<?= htmlspecialchars($groupData['id_gpo']) ?>" style="display:inline-block;margin-bottom:4px;padding:4px 8px;background:#0066cc;color:white;text-decoration:none;border-radius:4px;font-size:0.85em;">Editar</a><br>
                    <form action="grupos.php" method="POST" onsubmit="return confirm('¿Eliminar este grupo?');">
                        <input type="hidden" name="formAction" value="delete">
                        <input type="hidden" name="targetGroupId" value="<?= htmlspecialchars($groupData['id_gpo']) ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php ob_end_flush(); ?>