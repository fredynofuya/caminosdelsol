<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = clean_input($_POST['usuario']);
    $contrasena = md5(clean_input($_POST['contrasena']));
    
    $sql = "SELECT * FROM usuarios WHERE usuario = ? AND contrasena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['usuario_nombre'] = $row['nombre'];
        $_SESSION['usuario_rol'] = $row['rol'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agencia de Viajes - Iniciar Sesión</title>
    <link rel="stylesheet" href="/css/login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="logo-container">
            <img src="/img/LogoCS.png" alt="Logo Agencia de Viajes" class="logo">
        </div>
        <h1>Bienvenido a Caminos del Sol 🌞</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>