<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: views/login.php");
    exit;
}

// Obtener información del usuario
$nombre = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
$apellidos = isset($_SESSION["apellidos"]) ? $_SESSION["apellidos"] : "";
$tipoUsuario = isset($_SESSION["tipoUsuario"]) ? $_SESSION["tipoUsuario"] : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIAP - Sistema de Inventario de Asociación Pepsi</title>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../styles/homeStyle.css">
  <style>
    /* Estilos específicos para las tarjetas */
    .sales-card {
      background-color: #9f84ff;
      background-image: linear-gradient(45deg, #9f84ff, #7a5fff);
    }

    .customers-card {
      background-color: #6b9fff;
      background-image: linear-gradient(45deg, #6b9fff, #4e7fff);
    }

    .inventory-card {
      background-color: #ff9f7a;
      background-image: linear-gradient(45deg, #ff9f7a, #ff7a5f);
    }
  </style>
</head>
<body>
  <!-- Sidebar Navigation -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      
    </div>

    <a href="home.php" class="menu-item active">
      <i class="fas fa-home"></i>
      <span>Inicio</span>
    </a>

    <a href="sales.php" class="menu-item">
      <i class="fas fa-dollar-sign"></i>
      <span>Ventas</span>
    </a>

    <a href="customer.php" class="menu-item">
      <i class="fas fa-users"></i>
      <span>Clientes</span>
    </a>

    <a href="reports.php" class="menu-item">
      <i class="fas fa-chart-bar"></i>
      <span>Reportes</span>
    </a>

    <a href="inventory.php" class="menu-item">
      <i class="fas fa-boxes"></i>
      <span>Inventario</span>
    </a>

    <a href="branches.php" class="menu-item">
      <i class="fas fa-map-marker-alt"></i>
      <span>Sucursales</span>
    </a>

    
    <a href="settings.php" class="menu-item">
      <i class="fas fa-cog"></i>
      <span>Ajustes</span>
    </a>
    

    <button class="logout-btn" id="logout-btn">
      <i class="fas fa-sign-out-alt"></i>
      <span>Salir</span>
    </button>
  </div>

  <!-- Logo flotante que se mueve con el sidebar pero siempre deja una parte visible -->
  <div class="floating-logo" id="floating-logo">
    <div class="logo">SIAP </div>
    <img src="../photos/logo 3.png" alt="Logo SIAP" class="logo-img">
  </div>

  <!-- Main Content Area -->
  <div class="main-content">
    <div class="header">
      <h1 class="title">SISTEMA DE INVENTARIO DE ASOCIACIÓN PEPSI</h1>
      <p class="subtitle">Distribuidora Pepsi Palizada</p>
      <p class="user-welcome">Bienvenido, <?php echo htmlspecialchars($nombre . " " . $apellidos); ?></p>
    </div>

    <div class="dashboard">
      <div class="card sales-card">
        <div>
          <h2 class="card-title">Vender</h2>
          <p class="card-subtitle">Registrar ventas</p>
        </div>
        <img src="../photos/Pepsi-PNG-Picture.png" alt="Pepsi Products" class="card-image">
      </div>

      <div class="card customers-card">
        <div>
          <h2 class="card-title">Clientes</h2>
          <p class="card-subtitle">Gestión de clientes</p>
        </div>
        <img src="../photos/Pepsi_Fridge.png" alt="Pepsi Fridge" class="card-image">
      </div>

      <div class="card inventory-card">
        <div>
          <h2 class="card-title">Inventario</h2>
          <p class="card-subtitle">Control de productos</p>
        </div>
        <img src="../photos/Products.png" alt="Pepsi Products Group" class="card-image">
      </div>
    </div>
  </div>

  <script>
    // Elementos principales
    const floatingLogo = document.getElementById('floating-logo');
    const sidebar = document.getElementById('sidebar');
    const themeToggle = document.querySelector('.theme-toggle');
    
    // Toggle sidebar con el logo flotante
    if (floatingLogo) {
      floatingLogo.addEventListener('click', function() {
        sidebar.classList.toggle('active');
      });
    }
  
    // Cambio de tema
    if (themeToggle) {
      const body = document.body;
      themeToggle.addEventListener('click', function(e) {
        // Evita que el clic en el botón de tema active el sidebar
        e.stopPropagation();
        
        body.classList.toggle('light-mode');
        themeToggle.innerHTML = body.classList.contains('light-mode')
          ? '<i class="fas fa-moon"></i>'
          : '<i class="fas fa-sun"></i>';
      });
    }
  
    // Interacción con las tarjetas
    const cards = document.querySelectorAll('.card');
    if (cards.length > 0) {
      cards.forEach(card => {
        card.addEventListener('click', function() {
          const cardTitle = this.querySelector('.card-title').textContent;
          console.log('Card clicked:', cardTitle);
          
          // Redireccionar según la tarjeta
          switch(cardTitle) {
            case 'Vender':
              window.location.href = 'sales.php';
              break;
            case 'Clientes':
              window.location.href = 'customer.php';
              break;
            case 'Inventario':
              window.location.href = 'inventory.php';
              break;
          }
        });
      });
    }
  
    // Marcar menú activo
    const menuItems = document.querySelectorAll('.menu-item');
    if (menuItems.length > 0) {
      menuItems.forEach(item => {
        item.addEventListener('click', function() {
          menuItems.forEach(i => i.classList.remove('active'));
          this.classList.add('active');
  
          if (window.innerWidth <= 768) {
            sidebar.classList.remove('active');
          }
        });
      });
    }
  
    // Botón de cerrar sesión
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', function() {
        if (confirm('¿Seguro que deseas cerrar sesión?')) {
          window.location.href = '../controllers/logout.php';
        }
      });
    }
    
    // Inicializar para dispositivos móviles
    if (window.innerWidth <= 768) {
      // En móviles, sidebar comienza oculto
      sidebar.classList.add('active');
    }
  </script>
</body>
</html>