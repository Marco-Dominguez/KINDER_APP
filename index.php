<?php
// index.php
session_start();

// validate active session
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

// get user role
$currentUserRole = $_SESSION['userRole'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .menu-container { border: 1px solid #ccc; padding: 20px; width: 350px; border-radius: 8px; }
        .menu-list { list-style-type: none; padding: 0; }
        .menu-list li { margin-bottom: 15px; }
        .menu-list a { text-decoration: none; color: #0066cc; font-weight: bold; display: block; padding: 10px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee; }
        .menu-list a:hover { background: #e9e9e9; }
        .logout-btn { display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #cc0000; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .logout-btn:hover { background-color: #aa0000; }
        .user-info { margin-bottom: 20px; font-size: 0.9em; color: #555; }
    </style>
</head>
<body>
    <div class="menu-container">
        <h2>Menú Principal</h2>
        
        <div class="user-info">
            Has iniciado sesión como: <strong><?= htmlspecialchars($currentUserRole) ?></strong>
        </div>

        <ul class="menu-list">
            <?php if ($currentUserRole === 'admin'): ?>
                <li><a href="usuarios.php">Gestión de Usuarios</a></li>
                <li><a href="personal.php">Gestión de Personal</a></li>
                <li><a href="grupos.php">Gestión de Grupos</a></li>
                <li><a href="alumnos.php">Gestión de Alumnos</a></li>
            
            <?php elseif ($currentUserRole === 'docente'): ?>
                <li><a href="grupos.php">Mis Grupos</a></li>
                <li><a href="alumnos.php">Mis Alumnos</a></li>
            <?php endif; ?>
        </ul>

        <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
    </div>
</body>
</html>