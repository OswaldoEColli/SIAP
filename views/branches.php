<?php
// Iniciar sesión si es necesario
session_start();

// Verificar si el usuario está autenticado (comentado para desarrollo)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// Incluir configuración de base de datos
$branches = [];

if (file_exists('../config/db_config.php')) {
    require_once '../config/db_config.php';
    
    // Verificar si la tabla Sucursal existe
    $tableExists = false;
    if (isset($mysqli)) {
        $checkTable = $mysqli->query("SHOW TABLES LIKE 'Sucursal'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $tableExists = true;
        }
        
        // Obtener todas las sucursales de la base de datos
        if ($tableExists) {
            $query = "SELECT * FROM Sucursal WHERE status = 'Activo' ORDER BY nombre";
            $result = $mysqli->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $branches[] = [
                        'id' => $row['sucursalID'],
                        'name' => $row['nombre'],
                        'address' => $row['direccion'],
                        'city' => $row['ciudad'],
                        'state' => $row['estado'],
                        'phone' => $row['telefono'],
                        'email' => $row['email'],
                        'manager' => $row['gerente'],
                        'schedule' => $row['horario']
                    ];
                }
            }
        }
    }
}

// Si no hay sucursales, usar datos de ejemplo
if (empty($branches)) {
    $branches = [
        ['id' => 1, 'name' => 'Sucursal Polanco', 'address' => 'Av. Presidente Masaryk 111, Polanco, CDMX', 'phone' => '55 1234 5678', 'manager' => 'Juan Pérez'],
        ['id' => 2, 'name' => 'Sucursal Monterrey Centro', 'address' => 'Av. Constitución 350, Centro, Monterrey', 'phone' => '81 8765 4321', 'manager' => 'María López'],
        ['id' => 3, 'name' => 'Sucursal Guadalajara', 'address' => 'Av. López Mateos 555, Zapopan, Jalisco', 'phone' => '33 3698 7452', 'manager' => 'Roberto Gómez'],
        ['id' => 4, 'name' => 'Sucursal Cancún', 'address' => 'Blvd. Kukulcán km 9.5, Zona Hotelera, Cancún', 'phone' => '998 123 4567', 'manager' => 'Lucía Ramírez'],
        ['id' => 5, 'name' => 'Sucursal Puebla', 'address' => 'Calle 2 Sur 1902, Centro, Puebla', 'phone' => '222 987 6543', 'manager' => 'Carlos Ortiz']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAP - Mapa de Sucursales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&family=Work+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/branchesStyle.css">
    <style>
        /* Estilos adicionales para mensajes de estado */
        .status-message {
            margin-top: 10px;
            padding: 5px;
            text-align: center;
            font-style: italic;
            color: #666;
        }
        
        /* Spinner de carga */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: #007bff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toast notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 4px;
            color: white;
            background-color: #333;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s;
        }
        
        .toast-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .toast-notification.success {
            background-color: #28a745;
        }
        
        .toast-notification.error {
            background-color: #dc3545;
        }
        
        .toast-notification i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Estilos para el modal de confirmación */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .confirmation-modal .modal-content {
            max-width: 400px;
            text-align: center;
        }
        
        .btn-danger {
            background-color: #dc3545 !important;
            border: 1px solid #dc3545 !important;
            color: white !important;
        }
        
        .branch-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Estilo para mostrar cursor de mano en las sucursales */
        .branch-item {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            
        </div>

        <a href="home.php" class="menu-item">
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

        <a href="branches.php" class="menu-item active">
            <i class="fas fa-map-marker-alt"></i>
            <span>Sucursales</span>
        </a>

        <a href="settings.php" class="menu-item">
            <i class="fas fa-cog"></i>
            <span>Ajustes</span>
        </a>

        <button class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Salir</span>
        </button>
    </div>

    <!-- Logo flotante que se mueve con el sidebar -->
    <div class="floating-logo" id="floating-logo">
        <div class="logo">SIAP </div>
        <img src="../photos/logo 3.png" alt="Logo SIAP" class="logo-img">
    </div>

    <!-- Toast notification -->
    <div class="toast-notification" id="toast-notification">
        <i class="fas fa-check-circle"></i>
        <span id="notification-message">Mensaje de notificación</span>
    </div>

    <div class="main-content">
        <div class="content-area">
            <div class="branches-header">
                <h1>Mapa de Sucursales</h1>
                <button class="add-branch-btn" id="add-branch-btn">
                    <i class="fas fa-plus"></i>
                    <span>Nueva Sucursal</span>
                </button>
            </div>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-branch" placeholder="Buscar sucursal por nombre, ciudad o estado...">
            </div>

            <div class="map-container">
                <div class="branch-list">
                    <?php
                    // Generar la lista de sucursales
                    foreach($branches as $index => $branch) {
                        $activeClass = ($index === 0) ? 'active' : '';
                        echo '<div class="branch-item '.$activeClass.'" data-id="'.$branch['id'].'">';
                        echo '    <div class="branch-name">'.$branch['name'].'</div>';
                        echo '    <div class="branch-address">'.$branch['address'].'</div>';
                        if (isset($branch['city']) && isset($branch['state'])) {
                            echo '    <div class="branch-location">'.$branch['city'].', '.$branch['state'].'</div>';
                        }
                        echo '    <div class="branch-contact">';
                        echo '        <span><i class="fas fa-phone"></i> '.(isset($branch['phone']) ? $branch['phone'] : 'N/A').'</span>';
                        echo '        <span><i class="fas fa-user"></i> '.(isset($branch['manager']) ? $branch['manager'] : 'N/A').'</span>';
                        echo '    </div>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="map-view">
                    <div id="map" class="map-placeholder">
                        <img src="/api/placeholder/800/600" alt="Mapa de sucursales">
                    </div>
                    <div class="map-controls">
                        <button class="map-control-btn" title="Acercar">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="map-control-btn" title="Alejar">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="map-control-btn" title="Mi ubicación">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                    </div>
                    <div class="branch-info-panel" id="branch-info">
                        <h3>Sucursal Polanco</h3>
                        <div class="info-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Av. Presidente Masaryk 111, Polanco, CDMX</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-phone"></i>
                            <span>55 1234 5678</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-envelope"></i>
                            <span>polanco@siap.com.mx</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-user"></i>
                            <span>Gerente: Juan Pérez</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-clock"></i>
                            <span>Horario: Lun-Vie 9:00-18:00, Sáb 9:00-14:00</span>
                        </div>
                        <div class="branch-actions">
                            <button class="branch-action-btn btn-primary">
                                <i class="fas fa-directions"></i> Cómo llegar
                            </button>
                            <button class="branch-action-btn btn-secondary edit-selected-branch">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="branch-action-btn btn-danger delete-selected-branch">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar/editar sucursal -->
    <div class="branch-modal" id="branch-modal">
        <div class="modal-content">
            <h2 id="modal-title">Nueva Sucursal</h2>
            <form id="branch-form">
                <input type="hidden" id="sucursalID" name="sucursalID" value="">
                <input type="hidden" id="form-action" name="action" value="create">
                
                <div class="form-group">
                    <label for="branch-name">Nombre de la sucursal *</label>
                    <input type="text" id="branch-name" name="nombre" placeholder="Nombre de la sucursal" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="branch-city">Ciudad *</label>
                        <input type="text" id="branch-city" name="ciudad" placeholder="Ciudad" required>
                    </div>
                    <div class="form-group">
                        <label for="branch-state">Estado *</label>
                        <input type="text" id="branch-state" name="estado" placeholder="Estado" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="branch-address">Dirección completa *</label>
                    <input type="text" id="branch-address" name="direccion" placeholder="Dirección completa" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="branch-phone">Teléfono</label>
                        <input type="text" id="branch-phone" name="telefono" placeholder="Teléfono">
                    </div>
                    <div class="form-group">
                        <label for="branch-email">Correo electrónico</label>
                        <input type="email" id="branch-email" name="email" placeholder="Correo electrónico">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="branch-schedule">Horario</label>
                    <input type="text" id="branch-schedule" name="horario" placeholder="Ej: Lun-Vie 9:00-18:00, Sáb 9:00-14:00">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="branch-manager">Gerente</label>
                        <input type="text" id="branch-manager" name="gerente" placeholder="Nombre del gerente">
                    </div>
                    <div class="form-group">
                        <label for="branch-status">Estado</label>
                        <select id="branch-status" name="status">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" id="cancel-modal">Cancelar</button>
                    <button type="submit" id="save-branch">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="confirmation-modal" id="delete-confirmation-modal">
        <div class="modal-content">
            <h2>Confirmar eliminación</h2>
            <p>¿Estás seguro de que deseas eliminar esta sucursal? Esta acción no se puede deshacer.</p>
            
            <div class="modal-footer">
                <button type="button" id="cancel-delete">Cancelar</button>
                <button type="button" id="confirm-delete" class="btn-danger">Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let selectedBranchId = null;
        
        // Elementos principales
        const floatingLogo = document.getElementById('floating-logo');
        const sidebar = document.getElementById('sidebar');
        
        // Toast notification
        const toast = document.getElementById('toast-notification');
        const toastMessage = document.getElementById('notification-message');
        
        // Modales
        const branchModal = document.getElementById('branch-modal');
        const modalTitle = document.getElementById('modal-title');
        const branchForm = document.getElementById('branch-form');
        const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
        
        // Botones
        const addBranchBtn = document.getElementById('add-branch-btn');
        const cancelModalBtn = document.getElementById('cancel-modal');
        const cancelDeleteBtn = document.getElementById('cancel-delete');
        const confirmDeleteBtn = document.getElementById('confirm-delete');
        
        // Función para mostrar notificaciones toast
        function showToast(message, type = 'success') {
            toastMessage.textContent = message;
            toast.className = 'toast-notification ' + type;
            
            // Cambiar el ícono según el tipo
            const icon = toast.querySelector('i');
            if (icon) {
                icon.className = type === 'success' ? 'fas fa-check-circle' : 
                                 type === 'error' ? 'fas fa-exclamation-circle' : 
                                 'fas fa-info-circle';
            }
            
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
        }
            
        // Toggle sidebar con el logo flotante
        if (floatingLogo) {
            floatingLogo.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Funcionalidad de búsqueda
        document.getElementById('search-branch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const branchItems = document.querySelectorAll('.branch-item');
            
            branchItems.forEach(item => {
                const name = item.querySelector('.branch-name').textContent.toLowerCase();
                const address = item.querySelector('.branch-address').textContent.toLowerCase();
                const cityState = item.querySelector('.branch-location') ? 
                    item.querySelector('.branch-location').textContent.toLowerCase() : '';
                
                if (name.includes(searchTerm) || address.includes(searchTerm) || cityState.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Función para cargar los detalles de una sucursal seleccionada
        function loadBranchDetails(branchId) {
            // Mostrar el panel de información
            const branchInfoPanel = document.getElementById('branch-info');
            branchInfoPanel.style.display = 'block';
            
            const branchItem = document.querySelector(`.branch-item[data-id="${branchId}"]`);
            if (!branchItem) return;
            
            // Extraer información de la sucursal
            const branchName = branchItem.querySelector('.branch-name').textContent;
            const branchAddress = branchItem.querySelector('.branch-address').textContent;
            const locationEl = branchItem.querySelector('.branch-location');
            const cityState = locationEl ? locationEl.textContent : '';
            
            const contactSpans = branchItem.querySelectorAll('.branch-contact span');
            const phone = contactSpans[0].textContent.replace('󰁝 ', '').replace(/^.+: /, '');
            const manager = contactSpans[1].textContent.replace('󰀄 ', '').replace(/^.+: /, '');
            
            // Actualizar el panel de información
            document.querySelector('#branch-info h3').textContent = branchName;
            document.querySelector('#branch-info .info-row:nth-child(1) span').textContent = branchAddress;
            document.querySelector('#branch-info .info-row:nth-child(2) span').textContent = phone;
            document.querySelector('#branch-info .info-row:nth-child(4) span').textContent = 'Gerente: ' + manager;
            
            // Guardar el ID de la sucursal seleccionada
            selectedBranchId = branchId;
        }

        // Funcionalidad para los elementos de la lista de sucursales
        function setupBranchListeners() {
            const branchItems = document.querySelectorAll('.branch-item');
            branchItems.forEach(item => {
                item.addEventListener('click', function() {
                    branchItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    const branchId = this.getAttribute('data-id');
                    selectedBranchId = branchId;
                    
                    // Cargar detalles y mostrar panel de información
                    loadBranchDetails(branchId);
                });
                
                // Nuevo: Configurar doble clic para abrir directamente el modal de edición
                item.addEventListener('dblclick', function() {
                    const branchId = this.getAttribute('data-id');
                    editBranch(branchId);
                });
            });
        }
        
        // Inicializar listeners
        setupBranchListeners();

        // Mostrar panel de información al hacer clic en el mapa
        document.getElementById('map').addEventListener('click', function() {
            if (selectedBranchId) {
                document.getElementById('branch-info').style.display = 'block';
            }
        });

        // Funcionalidad para mostrar/ocultar el modal de sucursal
        addBranchBtn.addEventListener('click', function() {
            // Resetear formulario para nueva sucursal
            branchForm.reset();
            document.getElementById('sucursalID').value = '';
            document.getElementById('form-action').value = 'create';
            modalTitle.textContent = 'Nueva Sucursal';
            branchModal.style.display = 'flex';
        });

        cancelModalBtn.addEventListener('click', function() {
            branchModal.style.display = 'none';
        });

        // Funcionalidad para el botón editar en el panel de información
        document.querySelector('.edit-selected-branch').addEventListener('click', function() {
            if (selectedBranchId) {
                editBranch(selectedBranchId);
            }
        });

        // Funcionalidad para el botón eliminar en el panel de información
        document.querySelector('.delete-selected-branch').addEventListener('click', function() {
            if (selectedBranchId) {
                showDeleteConfirmation(selectedBranchId);
            }
        });

        // Función para mostrar el modal de edición con los datos de la sucursal
        function editBranch(branchId) {
            // Cambiar título y acción del formulario
            modalTitle.textContent = 'Editar Sucursal';
            document.getElementById('form-action').value = 'update';
            document.getElementById('sucursalID').value = branchId;
            
            // Mostrar indicador de carga en el modal
            const saveButton = document.getElementById('save-branch');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = 'Cargando... <div class="spinner"></div>';
            saveButton.disabled = true;
            
            // Mostrar el modal mientras se cargan los datos
            branchModal.style.display = 'flex';
            
            // Obtener datos de la sucursal seleccionada
            fetch('sucursal_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get&sucursalID=${branchId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o respuesta no válida');
                }
                return response.json();
            })
            .then(data => {
                // Restaurar botón
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
                
                if (data.success) {
                    // Rellenar el formulario con los datos
                    const branch = data.data;
                    document.getElementById('branch-name').value = branch.nombre;
                    document.getElementById('branch-city').value = branch.ciudad;
                    document.getElementById('branch-state').value = branch.estado;
                    document.getElementById('branch-address').value = branch.direccion;
                    document.getElementById('branch-phone').value = branch.telefono;
                    document.getElementById('branch-email').value = branch.email;
                    document.getElementById('branch-schedule').value = branch.horario;
                    document.getElementById('branch-manager').value = branch.gerente;
                    document.getElementById('branch-status').value = branch.status;
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                // Restaurar botón
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
                
                console.error('Error:', error);
                showToast('Error al cargar los datos de la sucursal', 'error');
                
                // Si hay error, cerramos el modal
                branchModal.style.display = 'none';
            });
        }

        // Función para mostrar confirmación de eliminación
        function showDeleteConfirmation(branchId) {
            selectedBranchId = branchId;
            deleteConfirmationModal.style.display = 'flex';
        }

        // Cancelar eliminación
        cancelDeleteBtn.addEventListener('click', function() {
            deleteConfirmationModal.style.display = 'none';
        });

        // Confirmar eliminación
        confirmDeleteBtn.addEventListener('click', function() {
            if (selectedBranchId) {
                deleteBranch(selectedBranchId);
            }
        });

        // Función para eliminar una sucursal
        function deleteBranch(branchId) {
            // Mostrar indicador de carga en el botón
            const deleteButton = confirmDeleteBtn;
            const originalText = deleteButton.textContent;
            deleteButton.innerHTML = 'Eliminando... <div class="spinner"></div>';
            deleteButton.disabled = true;
            
            fetch('sucursal_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&sucursalID=${branchId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red o respuesta no válida');
                }
                return response.json();
            })
            .then(data => {
                // Restaurar botón
                deleteButton.textContent = originalText;
                deleteButton.disabled = false;
                
                // Cerrar modal de confirmación
                deleteConfirmationModal.style.display = 'none';
                
                if (data.success) {
                    // Eliminar la sucursal del DOM
                    const branchItem = document.querySelector(`.branch-item[data-id="${branchId}"]`);
                    if (branchItem) {
                        branchItem.remove();
                    }
                    
                    // Ocultar el panel de información
                    document.getElementById('branch-info').style.display = 'none';
                    
                    // Mostrar notificación
                    showToast(data.message, 'success');
                    
                    // Resetear sucursal seleccionada
                    selectedBranchId = null;
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                // Restaurar botón
                deleteButton.textContent = originalText;
                deleteButton.disabled = false;
                
                // Cerrar modal de confirmación
                deleteConfirmationModal.style.display = 'none';
                
                console.error('Error:', error);
                showToast('Error al eliminar la sucursal', 'error');
            });
        }

        // Manejar envío del formulario
        branchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validación básica
            const name = document.getElementById('branch-name').value;
            const city = document.getElementById('branch-city').value;
            const state = document.getElementById('branch-state').value;
            const address = document.getElementById('branch-address').value;
            
            if (!name || !city || !state || !address) {
                showToast('Por favor completa los campos obligatorios: Nombre, Ciudad, Estado y Dirección', 'error');
                return;
            }
            
            // Mostrar estado de guardado
            const saveButton = document.getElementById('save-branch');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = 'Guardando... <div class="spinner"></div>';
            saveButton.disabled = true;
            
            // Recopilar datos del formulario
            const formData = new FormData(branchForm);
            
            // Enviar petición al servidor
            fetch('sucursal_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error de red: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Restaurar botón
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
                
                if (data.success) {
                    // Cerrar modal
                    branchModal.style.display = 'none';
                    
                    // Mostrar notificación
                    showToast(data.message, 'success');
                    
                    const action = document.getElementById('form-action').value;
                    
                    if (action === 'create') {
                        // Agregar la nueva sucursal a la lista
                        addNewBranchToList(data.data);
                    } else if (action === 'update') {
                        // Actualizar la sucursal en la lista
                        updateBranchInList(data.data);
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                // Restaurar botón
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
                
                console.error('Error:', error);
                showToast('Error de conexión al guardar la sucursal', 'error');
            });
        });
        
        // Función para agregar una nueva sucursal a la lista
        function addNewBranchToList(branch) {
            const branchList = document.querySelector('.branch-list');
            
            const branchItem = document.createElement('div');
            branchItem.className = 'branch-item';
            branchItem.setAttribute('data-id', branch.id);
            
            branchItem.innerHTML = `
                <div class="branch-name">${branch.nombre}</div>
                <div class="branch-address">${branch.direccion}</div>
                <div class="branch-location">${branch.ciudad}, ${branch.estado}</div>
                <div class="branch-contact">
                    <span><i class="fas fa-phone"></i> ${branch.telefono || 'N/A'}</span>
                    <span><i class="fas fa-user"></i> ${branch.gerente || 'N/A'}</span>
                </div>
            `;
            
            branchList.appendChild(branchItem);
            
            // Seleccionar automáticamente la nueva sucursal
            setTimeout(() => {
                const branchItems = document.querySelectorAll('.branch-item');
                branchItems.forEach(item => item.classList.remove('active'));
                branchItem.classList.add('active');
                branchItem.scrollIntoView({ behavior: 'smooth' });
                
                // Actualizar la información en el panel
                document.querySelector('#branch-info h3').textContent = branch.nombre;
                document.querySelector('#branch-info .info-row:nth-child(1) span').textContent = branch.direccion;
                document.querySelector('#branch-info .info-row:nth-child(2) span').textContent = branch.telefono || 'N/A';
                document.querySelector('#branch-info .info-row:nth-child(3) span').textContent = branch.email || 'N/A';
                document.querySelector('#branch-info .info-row:nth-child(4) span').textContent = 'Gerente: ' + (branch.gerente || 'N/A');
                document.querySelector('#branch-info .info-row:nth-child(5) span').textContent = 'Horario: ' + (branch.horario || 'Lun-Vie 9:00-18:00');
                
                document.getElementById('branch-info').style.display = 'block';
                
                // Actualizar ID de sucursal seleccionada
                selectedBranchId = branch.id;
            }, 100);
            
            // Actualizar listeners
            setupBranchListeners();
        }
        
        // Función para actualizar una sucursal en la lista
        function updateBranchInList(branch) {
            const branchItem = document.querySelector(`.branch-item[data-id="${branch.id}"]`);
            
            if (branchItem) {
                // Actualizar datos en la lista
                branchItem.querySelector('.branch-name').textContent = branch.nombre;
                branchItem.querySelector('.branch-address').textContent = branch.direccion;
                
                if (branchItem.querySelector('.branch-location')) {
                    branchItem.querySelector('.branch-location').textContent = `${branch.ciudad}, ${branch.estado}`;
                } else {
                    const locationDiv = document.createElement('div');
                    locationDiv.className = 'branch-location';
                    locationDiv.textContent = `${branch.ciudad}, ${branch.estado}`;
                    branchItem.insertBefore(locationDiv, branchItem.querySelector('.branch-contact'));
                }
                
                const contactSpans = branchItem.querySelectorAll('.branch-contact span');
                contactSpans[0].innerHTML = `<i class="fas fa-phone"></i> ${branch.telefono || 'N/A'}`;
                contactSpans[1].innerHTML = `<i class="fas fa-user"></i> ${branch.gerente || 'N/A'}`;
                
                // Si es la sucursal activa, actualizar también el panel de información
                if (branchItem.classList.contains('active')) {
                    document.querySelector('#branch-info h3').textContent = branch.nombre;
                    document.querySelector('#branch-info .info-row:nth-child(1) span').textContent = branch.direccion;
                    document.querySelector('#branch-info .info-row:nth-child(2) span').textContent = branch.telefono || 'N/A';
                    document.querySelector('#branch-info .info-row:nth-child(3) span').textContent = branch.email || 'N/A';
                    document.querySelector('#branch-info .info-row:nth-child(4) span').textContent = 'Gerente: ' + (branch.gerente || 'N/A');
                    document.querySelector('#branch-info .info-row:nth-child(5) span').textContent = 'Horario: ' + (branch.horario || 'Lun-Vie 9:00-18:00');
                }
                
                // Aunque la actualización fue exitosa, mostrar notificación de éxito
                showToast('Sucursal actualizada correctamente', 'success');
            }
        }

        // Cerrar los modales si se hace clic fuera de ellos
        window.addEventListener('click', function(event) {
            if (event.target === branchModal) {
                branchModal.style.display = 'none';
            }
            if (event.target === deleteConfirmationModal) {
                deleteConfirmationModal.style.display = 'none';
            }
        });

        // Funcionalidad para botones de control del mapa
        const mapControlBtns = document.querySelectorAll('.map-control-btn');
        mapControlBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // En un escenario real, aquí iría la lógica para controlar el mapa
                showToast('Función de mapa: ' + this.title, 'info');
            });
        });

        // Funcionalidad para botones de acción en el panel de información
        document.querySelector('.btn-primary').addEventListener('click', function() {
            // En un escenario real, aquí iría la lógica para mostrar direcciones
            showToast('Mostrando cómo llegar a la sucursal', 'info');
        });
         // Script específico para el botón de logout
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Inicializando script de logout");
        
        const logoutBtn = document.querySelector('.logout-btn');
        console.log("Botón logout encontrado:", logoutBtn);
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                console.log("Botón logout clickeado");
                if (confirm('¿Seguro que deseas cerrar sesión?')) {
                    window.location.href = '../controllers/logout.php';
                }
            });
        }
    });
    </script>
</body>
</html>