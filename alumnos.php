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
        $selectedGroupId = $_POST['groupId'];
        $studentName     = $_POST['studentName'];
        $studentLastName = $_POST['studentLastName'];

        // id_gpo exist validation
        $validateGroupQuery = $connection->prepare("SELECT COUNT(*) FROM grupo WHERE id_gpo = ?");
        $validateGroupQuery->execute([$selectedGroupId]);
        $groupExists = $validateGroupQuery->fetchColumn();

        if ($groupExists) {
            // insert student
            $insertQuery = $connection->prepare("INSERT INTO alumnos (id_gpo, nombre_alu, apellidos_alu) VALUES (?, ?, ?)");
            if ($insertQuery->execute([$selectedGroupId, $studentName, $studentLastName])) {
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
        $studentName     = $_POST['studentName'];
        $studentLastName = $_POST['studentLastName'];

        $updateQuery = $connection->prepare("UPDATE alumnos SET id_gpo = ?, nombre_alu = ?, apellidos_alu = ? WHERE id_alu = ?");
        if ($updateQuery->execute([$selectedGroupId, $studentName, $studentLastName, $targetId])) {
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

// get students for table
$fetchStudentsQuery = $connection->query("
    SELECT a.id_alu, a.id_gpo, g.grupo_gpo, a.nombre_alu, a.apellidos_alu 
    FROM alumnos a 
    JOIN grupo g ON a.id_gpo = g.id_gpo
");
$currentStudents = $fetchStudentsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Alumnos</title>
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
    
    <h2><?= $editingStudent ? 'Editar Alumno' : 'Registrar Nuevo Alumno' ?></h2>
    
    <?php if ($actionFeedback): ?>
        <p class="feedback"><?= htmlspecialchars($actionFeedback) ?></p>
    <?php endif; ?>

    <form action="alumnos.php" method="POST">
        <input type="hidden" name="formAction" value="<?= $editingStudent ? 'update' : 'create' ?>">
        <?php if ($editingStudent): ?>
            <input type="hidden" name="targetStudentId" value="<?= htmlspecialchars($editingStudent['id_alu']) ?>">
        <?php endif; ?>

        <label>Asignar a Grupo:</label><br>
        <select name="groupId" required>
            <option value="">-- Seleccionar Grupo --</option>
            <?php foreach ($availableGroups as $groupData): ?>
                <option value="<?= htmlspecialchars($groupData['id_gpo']) ?>"
                    <?= ($editingStudent && $editingStudent['id_gpo'] == $groupData['id_gpo']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($groupData['grupo_gpo']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Nombre(s):</label><br>
        <input type="text" name="studentName" value="<?= $editingStudent ? htmlspecialchars($editingStudent['nombre_alu']) : '' ?>" required><br><br>

        <label>Apellidos:</label><br>
        <input type="text" name="studentLastName" value="<?= $editingStudent ? htmlspecialchars($editingStudent['apellidos_alu']) : '' ?>" required><br><br>

        <button type="submit"><?= $editingStudent ? 'Actualizar Alumno' : 'Guardar Alumno' ?></button>
        <?php if ($editingStudent): ?>
            <a href="alumnos.php" style="margin-left:10px;">Cancelar</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Alumnos</h2>
    <table>
        <thead>
            <tr>
                <th>ID Alumno</th>
                <th>Grupo</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($currentStudents as $studentData): ?>
            <tr>
                <td><?= htmlspecialchars($studentData['id_alu']) ?></td>
                <td><?= htmlspecialchars($studentData['grupo_gpo']) ?></td>
                <td><?= htmlspecialchars($studentData['nombre_alu']) ?></td>
                <td><?= htmlspecialchars($studentData['apellidos_alu']) ?></td>
                <td>
                    <a href="alumnos.php?edit=<?= htmlspecialchars($studentData['id_alu']) ?>" style="display:inline-block;margin-bottom:4px;padding:4px 8px;background:#0066cc;color:white;text-decoration:none;border-radius:4px;font-size:0.85em;">Editar</a><br>
                    <form action="alumnos.php" method="POST" onsubmit="return confirm('¿Eliminar este alumno?');">
                        <input type="hidden" name="formAction" value="delete">
                        <input type="hidden" name="targetStudentId" value="<?= htmlspecialchars($studentData['id_alu']) ?>">
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