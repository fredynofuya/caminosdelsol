<?php
include 'config.php';


// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario_nombre'];
$usuario_rol = $_SESSION['usuario_rol'];

// Eliminar pedido si se solicita y el usuario es admin
if (isset($_GET['eliminar']) && $usuario_rol === 'admin') {
    $id_eliminar = intval($_GET['eliminar']);
    $sql_eliminar = "DELETE FROM pedidos_viajes WHERE id = ?";
    $stmt_eliminar = $conn->prepare($sql_eliminar);
    $stmt_eliminar->bind_param("i", $id_eliminar);
    if ($stmt_eliminar->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Error al eliminar el pedido.');</script>";
    }
}

// Configuración de paginación
$registros_por_pagina = 3;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta pedidos
$sql_pedidos = "SELECT p.* FROM pedidos_viajes p JOIN usuarios u ON p.id_usuario = u.id";
if ($usuario_rol == 'usuario') {
    $sql_pedidos .= " WHERE p.id_usuario = ?";
}
$sql_pedidos .= " ORDER BY p.fecha_salida DESC";

$stmt_pedidos = $conn->prepare($sql_pedidos);
if ($usuario_rol == 'usuario') {
    $stmt_pedidos->bind_param("i", $usuario_id);
}
$stmt_pedidos->execute();
$result_pedidos = $stmt_pedidos->get_result();

// Obtener todos los pedidos
$pedidos = [];
while ($pedido = $result_pedidos->fetch_assoc()) {
    $pedidos[] = $pedido;
}
$total_pedidos = count($pedidos);
$total_paginas = ceil($total_pedidos / $registros_por_pagina);

// Obtener solo los pedidos para la página actual
$pedidos_pagina = array_slice($pedidos, $offset, $registros_por_pagina);

// Estadísticas
$sql_destinos = "SELECT destino, COUNT(*) as cantidad FROM pedidos_viajes GROUP BY destino";
$sql_tipo_viaje = "SELECT tipo_viaje, COUNT(*) as cantidad FROM pedidos_viajes GROUP BY tipo_viaje";
$sql_fechas = "SELECT DATE_FORMAT(fecha_salida, '%Y-%m') as mes, COUNT(*) as cantidad FROM pedidos_viajes GROUP BY DATE_FORMAT(fecha_salida, '%Y-%m') ORDER BY mes";

$result_destinos = $conn->query($sql_destinos);
$result_tipo_viaje = $conn->query($sql_tipo_viaje);
$result_fechas = $conn->query($sql_fechas);

$destinos_data = [];
$tipo_viaje_data = [];
$fechas_data = [];

while ($row = $result_destinos->fetch_assoc()) {
    $destinos_data[] = $row;
}
while ($row = $result_tipo_viaje->fetch_assoc()) {
    $tipo_viaje_data[] = $row;
}
while ($row = $result_fechas->fetch_assoc()) {
    $fechas_data[] = $row;
}

// El resto del HTML y scripts permanece como en tu archivo original
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Caminos del Sol 🌞</title>
  <link rel="stylesheet" href="/css/login.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .acciones {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    }

    .btn-edit,
    .btn-delete {
    display: inline-block;
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    text-align: center;
    color: white;
    }

    .btn-edit {
    background-color: #f0ad4e; /* naranja */
    }

    .btn-delete {
    background-color: #d9534f; /* rojo */
    }

    .btn-edit:hover {
    background-color: #ec971f;
    }

    .btn-delete:hover {
    background-color: #c9302c;
    }

    .search-box {
      margin: 20px 0;
    }
    .search-box input {
      width: 100%;
      padding: 8px;
      font-size: 16px;
    }
    .pagination {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
    }
    .pagination-info {
      font-style: italic;
      text-align: center;
      margin: 10px 0;
    }
    .pagination-button {
      padding: 8px 15px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .pagination-button:disabled {
      background-color: #cccccc;
      cursor: not-allowed;
    }
    .hidden {
      display: none;
    }
    .charts-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: space-between;
    }
    .chart-box {
      flex: 1;
      min-width: 300px;
      height: 300px;
    }
    .no-results {
      text-align: center;
      padding: 20px;
      font-style: italic;
      color: #666;
    }
  </style>
</head>
<body>
  <header>
    <div class="header-container">
      <img src="/img/LogoCS.png" alt="Logo Agencia de Viajes" class="logo">
      <nav>
        <ul>
          <li><a href="dashboard.php">Inicio</a></li>
          <?php if ($usuario_rol == 'admin'): ?>
            <li><a href="formulario_pedido.php">Nuevo Pedido</a></li>
          <?php endif; ?>
          <li><a href="logout.php">Cerrar Sesión</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main>
    <div class="welcome-section">
      <h1>Bienvenido, <?php echo htmlspecialchars($usuario_nombre); ?></h1>
      <p>Panel de control de Caminos del Sol 🌞</p>
    </div>

    <div class="dashboard-container">
      <section class="stats-section">
        <h2>Estadísticas de Viajes</h2>
        <div class="charts-container">
          <div class="chart-box"><canvas id="destinosChart"></canvas></div>
          <div class="chart-box"><canvas id="tipoViajeChart"></canvas></div>
          <div class="chart-box"><canvas id="fechasChart"></canvas></div>
        </div>
      </section>

      <section class="orders-section">
        <h2 id="pedidos">Pedidos de Viajes</h2>

        <!-- BUSCADOR -->
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Buscar pedidos...">
        </div>

        <div class="table-container">
          <table id="pedidosTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Correo electrónico</th>
                <th>Teléfono</th>
                <th>Tipo de Viaje</th>
                <th>Voluntariado</th>
                <th>Tipo de Voluntariado</th>
                <th>Destino</th>
                <th>Fecha Salida</th>
                <th>Fecha Regreso</th>
                <?php if ($usuario_rol == 'admin'): ?>
                  <th>Acciones</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody id="tableBody">
              <?php foreach ($pedidos_pagina as $pedido): ?>
                <tr>
                  <td><?php echo htmlspecialchars($pedido['id']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['nombre_completo']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['correo_electronico']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['numero_telefonico']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['tipo_viaje']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['voluntariado']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['tipo_voluntariado']); ?></td>
                  <td><?php echo htmlspecialchars($pedido['destino']); ?></td>
                  <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pedido['fecha_salida']))); ?></td>
                  <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pedido['fecha_regreso']))); ?></td>
                  <?php if ($usuario_rol == 'admin'): ?>
                    <td style="white-space: nowrap;">
                        <a href="formulario_pedido.php?editar=<?php echo $pedido['id']; ?>" class="btn-edit">Editar</a>
                        <a href="dashboard.php?eliminar=<?php echo $pedido['id']; ?>" class="btn-delete" onclick="return confirm('¿Estás seguro de eliminar este pedido?');">Eliminar</a>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div id="noResults" class="no-results hidden">No se encontraron resultados</div>
          <div class="pagination-info">
            Mostrando registros <?php echo $offset + 1; ?>-<?php echo min($offset + $registros_por_pagina, $total_pedidos); ?> de <?php echo $total_pedidos; ?>
          </div>
          <div class="pagination">
            <button class="pagination-button" id="prevButton" <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>>Anterior</button>
            <button class="pagination-button" id="nextButton" <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>>Siguiente</button>
          </div>
        </div>
      </section>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Caminos del Sol 🌞 - Agencia de Viajes. Todos los derechos reservados.</p>
  </footer>

  <script>
    // Datos para el buscador (todos los registros)
    const allPedidos = <?php echo json_encode($pedidos); ?>;
    const registrosPorPagina = <?php echo $registros_por_pagina; ?>;
    let paginaActual = <?php echo $pagina_actual; ?>;
    const totalPaginas = <?php echo $total_paginas; ?>;
    let resultadosBusqueda = null;


    // GRÁFICOS
    const destinosData = <?php echo json_encode($destinos_data); ?>;
    const tipoViajeData = <?php echo json_encode($tipo_viaje_data); ?>;
    const fechasData = <?php echo json_encode($fechas_data); ?>;

    document.addEventListener('DOMContentLoaded', function() {
      // Gráfico de destinos (barras)
      new Chart(document.getElementById('destinosChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: destinosData.map(item => item.destino),
          datasets: [{
            label: 'Pedidos por Destino',
            data: destinosData.map(item => item.cantidad),
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });

      // Gráfico de tipo de viaje (barras)
      new Chart(document.getElementById('tipoViajeChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: tipoViajeData.map(item => item.tipo_viaje),
          datasets: [{
            label: 'Pedidos por Tipo de Viaje',
            data: tipoViajeData.map(item => item.cantidad),
            backgroundColor: 'rgba(255, 159, 64, 0.7)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });

      // Gráfico de fechas (barras)
      new Chart(document.getElementById('fechasChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: fechasData.map(item => item.mes),
          datasets: [{
            label: 'Pedidos por Mes',
            data: fechasData.map(item => item.cantidad),
            backgroundColor: [
              'rgba(255, 99, 132, 0.7)',
              'rgba(54, 162, 235, 0.7)',
              'rgba(255, 206, 86, 0.7)',
              'rgba(75, 192, 192, 0.7)',
              'rgba(153, 102, 255, 0.7)',
              'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
              'rgba(255, 99, 132, 1)',
              'rgba(54, 162, 235, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)',
              'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Cantidad de Pedidos'
              }
            },
            x: {
              title: {
                display: true,
                text: 'Meses'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `Pedidos: ${context.raw}`;
                }
              }
            }
          }
        }
      });

      // Configurar botones de paginación
      document.getElementById('prevButton').addEventListener('click', function() {
        if (paginaActual > 1) {
          const searchTerm = document.getElementById('searchInput').value;
          if (searchTerm) {
            // Si hay una búsqueda activa, mantenemos el filtro
            window.location.href = `dashboard.php?pagina=${paginaActual - 1}#pedidos & search=${encodeURIComponent(searchTerm)}`;
          } else {
            window.location.href = `dashboard.php?pagina=${paginaActual - 1}#pedidos`;
          }
        }
      });

      document.getElementById('nextButton').addEventListener('click', function() {
        if (paginaActual < totalPaginas) {
          const searchTerm = document.getElementById('searchInput').value;
          if (searchTerm) {
            // Si hay una búsqueda activa, mantenemos el filtro
            window.location.href = `dashboard.php?pagina=${paginaActual + 1}#pedidos & search=${encodeURIComponent(searchTerm)}`;
          } else {
            window.location.href = `dashboard.php?pagina=${paginaActual + 1}#pedidos`;
          }
        }
      });
    });

    // BUSCADOR - busca en todos los registros
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('tableBody');
    const noResults = document.getElementById('noResults');
    const paginationInfo = document.querySelector('.pagination-info');
    const prevButton = document.getElementById('prevButton');
    const nextButton = document.getElementById('nextButton');

    // Función para mostrar los resultados
    function mostrarResultados(resultados, pagina = 1) {
      tableBody.innerHTML = '';
      noResults.classList.add('hidden');
      
      if (resultados.length === 0) {
        noResults.classList.remove('hidden');
        paginationInfo.textContent = 'Mostrando 0 resultados';
        prevButton.disabled = true;
        nextButton.disabled = true;
        return;
      }

      const totalPaginasResultados = Math.ceil(resultados.length / registrosPorPagina);
      const inicio = (pagina - 1) * registrosPorPagina;
      const fin = pagina * registrosPorPagina;
      const resultadosPagina = resultados.slice(inicio, fin);

      resultadosPagina.forEach(pedido => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
          <td>${pedido.id}</td>
          <td>${pedido.nombre_completo}</td>
          <td>${pedido.correo_electronico}</td>
          <td>${pedido.numero_telefonico}</td>
          <td>${pedido.tipo_viaje}</td>
          <td>${pedido.voluntariado}</td>
          <td>${pedido.tipo_voluntariado}</td>
          <td>${pedido.destino}</td>
          <td>${new Date(pedido.fecha_salida).toLocaleDateString('es-ES')}</td>
          <td>${new Date(pedido.fecha_regreso).toLocaleDateString('es-ES')}</td>
          <?php if ($usuario_rol == 'admin'): ?>
            <td>
                <a href="formulario_pedido.php?editar=${pedido.id}" class="btn-edit">Editar</a>
                <a href="dashboard.php?eliminar=<?php echo $pedido['id']; ?>" class="btn-delete" onclick="return confirm('¿Estás seguro de eliminar este pedido?');">Eliminar</a>
            </td>
            
          <?php endif; ?>
        `;
        
        tableBody.appendChild(row);
      });

      // Actualizar información de paginación
      paginationInfo.textContent = 
        `Mostrando registros ${inicio + 1}-${Math.min(fin, resultados.length)} de ${resultados.length}`;
      
      // Actualizar estado de los botones
      prevButton.disabled = pagina <= 1;
      nextButton.disabled = pagina >= totalPaginasResultados;
    }

    // Función para filtrar los pedidos
    function filtrarPedidos(termino) {
      if (!termino) {
        resultadosBusqueda = null;
        mostrarResultados(allPedidos, paginaActual);
        return;
      }

      const terminoLower = termino.toLowerCase();
      resultadosBusqueda = allPedidos.filter(pedido => {
        return Object.values(pedido).some(val => 
          String(val).toLowerCase().includes(terminoLower)
        );
      });

      mostrarResultados(resultadosBusqueda, 1);
    }

    // Evento de búsqueda
    searchInput.addEventListener('keyup', function() {
      filtrarPedidos(this.value);
    });

    // Cargar búsqueda si viene en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');
    if (searchTerm) {
      searchInput.value = searchTerm;
      filtrarPedidos(searchTerm);
    }
  </script>
</body>
</html>