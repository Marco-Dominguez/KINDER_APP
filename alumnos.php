<?php
// alumnos.php
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
$editingStudent = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editQuery = $connection->prepare("SELECT * FROM alumnos WHERE id_alu = ?");
    $editQuery->execute([$editId]);
    $editingStudent = $editQuery->fetch(PDO::FETCH_ASSOC);
}

// process create update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['formAction'] ?? '';

    if ($formAction === 'create') {
        $selectedGroupId  = $_POST['groupId'];
        $selectedUserId   = $_POST['userId'] ?? null;
        $studentName      = $_POST['studentName'];
        $studentLastName  = $_POST['studentLastName'];

        // id_gpo exist validation
        $validateGroupQuery = $connection->prepare("SELECT COUNT(*) FROM grupo WHERE id_gpo = ?");
        $validateGroupQuery->execute([$selectedGroupId]);
        $groupExists = $validateGroupQuery->fetchColumn();

        if ($groupExists) {
            // insert student
            $insertQuery = $connection->prepare("INSERT INTO alumnos (id_gpo, id_usu, nombre_alu, apellidos_alu) VALUES (?, ?, ?, ?)");
            if ($insertQuery->execute([$selectedGroupId, $selectedUserId ?: null, $studentName, $studentLastName])) {
                header("Location: alumnos.php");
                exit();
            } else {
                $actionFeedback = "Error al guardar el alumno.";
            }
        } else {
            $actionFeedback = "Error de validación: El grupo seleccionado no existe.";
        }
    } elseif ($formAction === 'update') {
        $targetId        = (int)$_POST['targetStudentId'];
        $selectedGroupId = $_POST['groupId'];
        $selectedUserId  = $_POST['userId'] ?? null;
        $studentName     = $_POST['studentName'];
        $studentLastName = $_POST['studentLastName'];

        $updateQuery = $connection->prepare("UPDATE alumnos SET id_gpo = ?, id_usu = ?, nombre_alu = ?, apellidos_alu = ? WHERE id_alu = ?");
        if ($updateQuery->execute([$selectedGroupId, $selectedUserId ?: null, $studentName, $studentLastName, $targetId])) {
            header("Location: alumnos.php");
            exit();
        } else {
            $actionFeedback = "Error al actualizar el alumno.";
        }
    } elseif ($formAction === 'delete') {
        $targetStudentId = $_POST['targetStudentId'];
        
        // delete student
        $deleteQuery = $connection->prepare("DELETE FROM alumnos WHERE id_alu = ?");
        if ($deleteQuery->execute([$targetStudentId])) {
            header("Location: alumnos.php");
            exit();
        } else {
            $actionFeedback = "Error al eliminar el alumno.";
        }
    }
}

// get groups for dropdown
$fetchGroupsQuery = $connection->query("SELECT id_gpo, grupo_gpo FROM grupo");
$availableGroups  = $fetchGroupsQuery->fetchAll(PDO::FETCH_ASSOC);

// get students
$fetchAlumnoUsersQuery = $connection->query(
    "SELECT u.id_usu, u.usuario_usu FROM usuarios u
    JOIN roles r ON u.role_id = r.role_id
    WHERE r.role_name = 'Alumno'");
$alumnoUsers = $fetchAlumnoUsersQuery->fetchAll(PDO::FETCH_ASSOC);

// get students for table
if ($currentUserRole === 'Alumno') {
    $currentUserId = $_SESSION['userId'];
    $fetchStudentsQuery = $connection->prepare(
        "SELECT a.id_alu, a.id_gpo, a.id_usu, g.grupo_gpo, a.nombre_alu, a.apellidos_alu
        FROM alumnos a JOIN grupo g ON a.id_gpo = g.id_gpo
        WHERE a.id_usu = ?");
    $fetchStudentsQuery->execute([$currentUserId]);
} else {
    $fetchStudentsQuery = $connection->query(
        "SELECT a.id_alu, a.id_gpo, a.id_usu, g.grupo_gpo, a.nombre_alu, a.apellidos_alu
        FROM alumnos a JOIN grupo g ON a.id_gpo = g.id_gpo");
}
$currentStudents = $fetchStudentsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Alumnos</title>
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

    <h2><?= $editingStudent ? 'Editar Alumno' : 'Registrar Nuevo Alumno' ?></h2>
    
    <?php if ($actionFeedback): ?>
        <p class="feedback"><?= htmlspecialchars($actionFeedback) ?></p>
    <?php endif; ?>

    <?php if (in_array($currentUserRole, ['Admin', 'Docente'])): ?>
    <div class="card">
    <form action="alumnos.php" method="POST">
        <input type="hidden" name="formAction" value="<?= $editingStudent ? 'update' : 'create' ?>">
        <?php if ($editingStudent): ?>
            <input type="hidden" name="targetStudentId" value="<?= htmlspecialchars($editingStudent['id_alu']) ?>">
        <?php endif; ?>

        <label>Vincular a Cuenta de Usuario (Alumno):</label><br>
        <select name="userId">
            <option value="">-- Sin vincular --</option>
            <?php foreach ($alumnoUsers as $alumnoUser): ?>
                <option value="<?= htmlspecialchars($alumnoUser['id_usu']) ?>"
                    <?= ($editingStudent && $editingStudent['id_usu'] == $alumnoUser['id_usu']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($alumnoUser['usuario_usu']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Asignar a Grupo:</label><br>
        <select name="groupId" required>
            <option value="">-- Seleccionar Grupo --</option>
            <?php foreach ($availableGroups as $groupData): ?>
                <option value="<?= htmlspecialchars($groupData['id_gpo']) ?>"
                    <?= ($editingStudent && $editingStudent['id_gpo'] == $groupData['id_gpo']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($groupData['grupo_gpo']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label>Nombre(s):</label><br>
        <input type="text" name="studentName" value="<?= $editingStudent ? htmlspecialchars($editingStudent['nombre_alu']) : '' ?>" required><br>

        <label>Apellidos:</label><br>
        <input type="text" name="studentLastName" value="<?= $editingStudent ? htmlspecialchars($editingStudent['apellidos_alu']) : '' ?>" required><br>

        <button type="submit"><?= $editingStudent ? 'Actualizar Alumno' : 'Guardar Alumno' ?></button>
        <?php if ($editingStudent): ?>
            <a href="alumnos.php" class="cancel-link">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>
    <?php endif; ?>

    <div class="card">
    <h2><?= $currentUserRole === 'Alumno' ? 'Mis Datos' : 'Lista de Alumnos' ?></h2>
    <table>
        <thead>
            <tr>
                <th>ID Alumno</th>
                <th>Grupo</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <?php if (in_array($currentUserRole, ['Admin', 'Docente'])): ?>
                <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentStudents as $studentData): ?>
            <tr>
                <td><?= htmlspecialchars($studentData['id_alu']) ?></td>
                <td><?= htmlspecialchars($studentData['grupo_gpo']) ?></td>
                <td><?= htmlspecialchars($studentData['nombre_alu']) ?></td>
                <td><?= htmlspecialchars($studentData['apellidos_alu']) ?></td>
                <?php if (in_array($currentUserRole, ['Admin', 'Docente'])): ?>
                <td class="action-cell">
                    <a href="alumnos.php?edit=<?= htmlspecialchars($studentData['id_alu']) ?>" class="btn-edit">Editar</a>
                    <form action="alumnos.php" method="POST" onsubmit="return confirm('¿Eliminar este alumno?');">
                        <input type="hidden" name="formAction" value="delete">
                        <input type="hidden" name="targetStudentId" value="<?= htmlspecialchars($studentData['id_alu']) ?>">
                        <button type="submit" class="btn-danger">Eliminar</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>