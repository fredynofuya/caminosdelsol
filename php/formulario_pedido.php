<?php
include 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_rol = $_SESSION['usuario_rol'];
$editar_id = isset($_GET['editar']) ? intval($_GET['editar']) : null;
$pedido = null;

// Si estamos editando y el usuario es admin, obtener datos del pedido
if ($editar_id && $usuario_rol == 'admin') {
    $sql = "SELECT * FROM pedidos_viajes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $editar_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
}

// Procesar formulario al enviarlo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = isset($_POST['nombre']) ? clean_input($_POST['nombre']) : '';
    $correo = isset($_POST['correo']) ? clean_input($_POST['correo']) : '';
    $telefono = isset($_POST['telefono']) ? clean_input($_POST['telefono']) : '';
    $destino = isset($_POST['destino']) ? clean_input($_POST['destino']) : '';
    $fecha_salida = clean_input($_POST['fecha_salida']);
    $fecha_regreso = clean_input($_POST['fecha_regreso']);
    $tipo_transporte = isset($_POST['tipo_transporte']) ? clean_input($_POST['tipo_transporte']) : '';
    $alojamiento = isset($_POST['alojamiento']) ? clean_input($_POST['alojamiento']) : '';
    $preferencias = isset($_POST['preferencias']) ? clean_input($_POST['preferencias']) : '';
    $tipo_viaje = isset($_POST['tipo_viaje']) ? clean_input($_POST['tipo_viaje']) : '';
    $voluntariado = isset($_POST['voluntariado']) ? clean_input($_POST['voluntariado']) : 'No';
    $tipo_voluntariado = isset($_POST['tipo_voluntariado']) ? clean_input($_POST['tipo_voluntariado']) : '';
    $id_usuario = $_SESSION['usuario_id'];

    if ($editar_id && $usuario_rol == 'admin') {
        // Actualizar pedido existente
        $sql = "UPDATE pedidos_viajes 
                SET nombre_completo = ?, correo_electronico = ?, numero_telefonico = ?,
                    destino = ?, fecha_salida = ?, fecha_regreso = ?, 
                    tipo_transporte = ?, alojamiento = ?, preferencias = ?,
                    tipo_viaje = ?, voluntariado = ?, tipo_voluntariado = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssssii",
            $nombre, $correo, $telefono,
            $destino, $fecha_salida, $fecha_regreso,
            $tipo_transporte, $alojamiento, $preferencias,
            $tipo_viaje, $voluntariado, $tipo_voluntariado, $editar_id
        );
    } else {
        // Crear nuevo pedido
        $sql = "INSERT INTO pedidos_viajes 
                (id, id_usuario, nombre_completo, correo_electronico, numero_telefonico,
                 destino, fecha_salida, fecha_regreso, tipo_transporte, alojamiento, preferencias,
                 tipo_viaje, voluntariado, tipo_voluntariado)
                VALUES (null, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssssssssss",
            $id_usuario, $nombre, $correo, $telefono,
            $destino, $fecha_salida, $fecha_regreso,
            $tipo_transporte, $alojamiento, $preferencias,
            $tipo_viaje, $voluntariado, $tipo_voluntariado
        );
    }

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Error al guardar el pedido: " . $conn->error;
    }
}
?>

<!DOCTYPE html> 
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $editar_id ? 'Editar Pedido' : 'Nuevo Pedido'; ?> - Agencia de Viajes</title>
  <link rel="stylesheet" href="/css/login.css" />
  <script>
    function toggleVoluntariado(valor) {
      const campo = document.getElementById('voluntariado_opciones');
      campo.style.display = (valor === 'Si') ? 'block' : 'none';
    }
    // Al cargar la página, verificamos el valor seleccionado en voluntariado para mostrar u ocultar el campo
    window.onload = function() {
      <?php if(isset($pedido) && $pedido['voluntariado'] == 'Si'): ?>
        toggleVoluntariado('Si');
      <?php else: ?>
        toggleVoluntariado('No');
      <?php endif; ?>
    }
  </script>
</head>

<body>
  <header>
    <div class="header-container">
      <img src="/img/LogoCS.png" alt="Logo Agencia de Viajes" class="logo" />
      <nav>
        <ul>
          <li><a href="dashboard.php">Inicio</a></li>
          <li><a href="logout.php">Cerrar Sesión</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main>
    <div class="form-container">
      <h1><?php echo $editar_id ? 'Editar Pedido de Viaje' : 'Nuevo Pedido de Viaje'; ?></h1>
      <?php if(isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
      <?php endif; ?>
      <form method="POST" action="">
        <div class="form-group">
          <label for="nombre">Nombres y Apellidos:</label>
          <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($pedido['nombre_completo'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="correo">Correo Electrónico:</label>
          <input type="email" id="correo" name="correo" required value="<?php echo htmlspecialchars($pedido['correo_electronico'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="telefono">Número Telefónico:</label>
          <input type="tel" id="telefono" name="telefono" required value="<?php echo htmlspecialchars($pedido['numero_telefonico'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="destino">Destino:</label>
          <input type="text" id="destino" name="destino" required value="<?php echo htmlspecialchars($pedido['destino'] ?? ''); ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="fecha_salida">Fecha de Inicio del Viaje:</label>
            <input type="date" id="fecha_salida" name="fecha_salida" required value="<?php echo htmlspecialchars($pedido['fecha_salida'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label for="fecha_regreso">Fecha de Regreso del Viaje:</label>
            <input type="date" id="fecha_regreso" name="fecha_regreso" required value="<?php echo htmlspecialchars($pedido['fecha_regreso'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="tipo_transporte">Tipo de Transporte:</label>
          <input type="text" id="tipo_transporte" name="tipo_transporte" required value="<?php echo htmlspecialchars($pedido['tipo_transporte'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="alojamiento">Alojamiento:</label>
          <input type="text" id="alojamiento" name="alojamiento" required value="<?php echo htmlspecialchars($pedido['alojamiento'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="preferencias">Preferencias:</label>
          <textarea id="preferencias" name="preferencias"><?php echo htmlspecialchars($pedido['preferencias'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
          <label>Tipo de Viaje:</label>
          <div class="radio-group">
            <label class="radio-inline">
              <input type="radio" name="tipo_viaje" value="Estudiante" required <?php echo (isset($pedido['tipo_viaje']) && $pedido['tipo_viaje'] == 'Estudiante') ? 'checked' : ''; ?>> Estudiante
            </label>
            <label class="radio-inline">
              <input type="radio" name="tipo_viaje" value="Mochilero" <?php echo (isset($pedido['tipo_viaje']) && $pedido['tipo_viaje'] == 'Mochilero') ? 'checked' : ''; ?>> Mochilero
            </label>
          </div>
        </div>

        <div class="form-group">
          <label>¿Quieres realizar un voluntariado?</label>
          <div class="radio-group">
            <label class="radio-inline">
              <input type="radio" name="voluntariado" value="Si" onclick="toggleVoluntariado('Si')" <?php echo (isset($pedido['voluntariado']) && $pedido['voluntariado'] == 'Si') ? 'checked' : ''; ?>> Sí
            </label>
            <label class="radio-inline">
              <input type="radio" name="voluntariado" value="No" onclick="toggleVoluntariado('No')" <?php echo (!isset($pedido['voluntariado']) || $pedido['voluntariado'] == 'No') ? 'checked' : ''; ?>> No
            </label>
          </div>
        </div>

        <div class="form-group" id="voluntariado_opciones" style="display: none;">
          <label for="tipo_voluntariado">Tipo de Voluntariado:</label>
          <select id="tipo_voluntariado" name="tipo_voluntariado">
            <option value="">Seleccione...</option>
            <option value="Reforestación de áreas degradadas con especies nativas" <?php echo (isset($pedido['tipo_voluntariado']) && $pedido['tipo_voluntariado'] == 'Reforestación de áreas degradadas con especies nativas') ? 'selected' : ''; ?>>Reforestación de áreas degradadas con especies nativas</option>
            <option value="Educación ambiental en comunidades rurales y escuelas" <?php echo (isset($pedido['tipo_voluntariado']) && $pedido['tipo_voluntariado'] == 'Educación ambiental en comunidades rurales y escuelas') ? 'selected' : ''; ?>>Educación ambiental en comunidades rurales y escuelas</option>
            <option value="Apoyo en viveros forestales comunitarios" <?php echo (isset($pedido['tipo_voluntariado']) && $pedido['tipo_voluntariado'] == 'Apoyo en viveros forestales comunitarios') ? 'selected' : ''; ?>>Apoyo en viveros forestales comunitarios</option>
            <option value="Limpieza de senderos y protección de áreas naturales" <?php echo (isset($pedido['tipo_voluntariado']) && $pedido['tipo_voluntariado'] == 'Limpieza de senderos y protección de áreas naturales') ? 'selected' : ''; ?>>Limpieza de senderos y protección de áreas naturales</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-submit">
            <?php echo $editar_id ? 'Actualizar Pedido' : 'Enviar Pedido'; ?>
          </button>
        </div>
      </form>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Caminos del Sol 🌞 - Agencia de Viajes. Todos los derechos reservados.</p>
  </footer>
</body>
</html>
