<?php
// login.php
session_start();
require 'config.php';

// user logged go to index
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
    header("Location: index.php");
    exit();
}

$loginFeedback = "";

// process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUsername = $_POST['username'] ?? '';
    $inputPassword = $_POST['password'] ?? '';

    // get user data
    $authQuery = $connection->prepare(
        "SELECT u.id_usu, u.usuario_usu, u.password_usu, r.role_name
        FROM usuarios u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.usuario_usu = ?");
    $authQuery->execute([$inputUsername]);
    $userData = $authQuery->fetch(PDO::FETCH_ASSOC);

    if ($userData && password_verify($inputPassword, $userData['password_usu'])) {
        $_SESSION['loggedIn'] = true;
        $_SESSION['userId']   = $userData['id_usu'];
        $_SESSION['username'] = $userData['usuario_usu'];
        $_SESSION['userRole'] = $userData['role_name'];
        header("Location: index.php");
        exit();
    } else {
        $loginFeedback = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f9; margin: 0; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        .login-container h2 { text-align: center; margin-bottom: 20px; }
        .login-container input[type="text"], .login-container input[type="password"] { width: 100%; padding: 10px; margin: 10px 0 20px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .login-container button { width: 100%; padding: 10px; background-color: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .login-container button:hover { background-color: #0055aa; }
        .feedback { color: red; text-align: center; font-weight: bold; font-size: 0.9em; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Acceso al Sistema</h2>
        
        <?php if ($loginFeedback): ?>
            <p class="feedback"><?= htmlspecialchars($loginFeedback) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>