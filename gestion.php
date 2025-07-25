<?php
require_once 'auth.php';
requireLogin(); // Requiere estar logueado

$currentUser = getCurrentUser();

// Obtener ubicaciones del usuario actual (o todas si es admin)
if (isAdmin() && isset($_GET['view']) && $_GET['view'] === 'all') {
    $locations = getUserLocations('all');
    $viewingAll = true;
} else {
    $locations = getUserLocations();
    $viewingAll = false;
}

// Ordenar por fecha más reciente
usort($locations, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Manejar eliminación si se solicita
if (isset($_POST['delete']) && isset($_POST['id'])) {
    $idToDelete = $_POST['id'];
    
    // Verificar permisos
    $canDelete = false;
    $locationsFile = 'locations.json';
    $allLocations = json_decode(file_get_contents($locationsFile), true) ?: [];
    
    foreach ($allLocations as $loc) {
        if ($loc['id'] === $idToDelete) {
            // Puede eliminar si es su ubicación o si es admin
            if ($loc['user_id'] === $currentUser['id'] || isAdmin()) {
                $canDelete = true;
                // Eliminar foto si existe
                if (!empty($loc['photo']) && file_exists($loc['photo'])) {
                    unlink($loc['photo']);
                }
            }
            break;
        }
    }
    
    if ($canDelete) {
        $allLocations = array_filter($allLocations, function($loc) use ($idToDelete) {
            return $loc['id'] !== $idToDelete;
        });
        
        // Reindexar array y guardar
        $allLocations = array_values($allLocations);
        file_put_contents($locationsFile, json_encode($allLocations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Recargar página
        header('Location: gestion.php?deleted=true' . ($viewingAll ? '&view=all' : ''));
        exit;
    }
}

// Obtener estadísticas
$userStats = getUserStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ubicaciones GPS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-container {
            flex: 1;
            max-width: 400px;
            min-width: 250px;
        }

        .search-box {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            background: rgba(255,255,255,0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .search-box:focus {
            outline: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .search-wrapper {
            position: relative;
        }

        .header-nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            transition: all 0.3s;
            font-size: 14px;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .locations-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 20px;
        }

        .location-row {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }

        .location-row:hover {
            background: #f8f9fa;
        }

        .location-row:last-child {
            border-bottom: none;
        }

        .location-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .location-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }

        .location-details {
            flex: 1;
        }

        .location-name {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin-bottom: 2px;
        }

        .location-meta {
            font-size: 12px;
            color: #999;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .accuracy-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }

        .user-tag {
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }

        .location-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-share {
            background: #28a745;
            color: white;
        }

        .btn-share:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        /* Modal de detalles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            max-width: 500px;
            margin: 50px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: relative;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 20px;
        }

        .detail-section {
            margin-bottom: 20px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .detail-value {
            font-size: 16px;
            color: #333;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            word-break: break-word;
        }

        .detail-photo {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            cursor: pointer;
        }

        .modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions .btn-action {
            justify-content: center;
            padding: 12px 20px;
            font-size: 15px;
        }

        .btn-google {
            background: #4285f4;
            color: white;
        }

        .btn-apple {
            background: #000;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            grid-column: span 2;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .export-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .copy-toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            display: none;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, 20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .search-container {
                width: 100%;
                max-width: none;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .location-info {
                gap: 10px;
            }

            .location-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .modal-actions {
                grid-template-columns: 1fr;
            }

            .btn-delete {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>
                📍 Gestión de Ubicaciones 
                <?php if ($viewingAll): ?>
                    <span style="font-size: 18px; font-weight: normal; opacity: 0.9;">(Vista Global - Todos los usuarios)</span>
                <?php endif; ?>
            </h1>
            <div class="search-container">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-box" id="searchBox" placeholder="<?php echo $viewingAll ? 'Buscar por nombre, comentario o usuario...' : 'Buscar por nombre o comentario...'; ?>">
                </div>
            </div>
            <div class="header-nav">
                <a href="index.php" class="nav-link">📍 Guardar</a>
                <?php if (isAdmin()): ?>
                    <?php if ($viewingAll): ?>
                        <a href="gestion.php" class="nav-link">👤 Mis ubicaciones</a>
                    <?php else: ?>
                        <a href="gestion.php?view=all" class="nav-link">🌍 Ver todas</a>
                    <?php endif; ?>
                    <a href="admin.php" class="nav-link">🛡️ Administrar</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link">🚪 Salir</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'true'): ?>
            <div class="message">
                ✅ Ubicación eliminada correctamente
            </div>
        <?php endif; ?>

        <div class="stats-bar">
            <div class="stat">
                <div class="stat-number"><?php echo count($locations); ?></div>
                <div class="stat-label">
                    <?php echo $viewingAll ? 'Total Global' : 'Mis Ubicaciones'; ?>
                </div>
            </div>
            <div class="stat">
                <div class="stat-number">
                    <?php 
                    $today = date('Y-m-d');
                    $todayCount = count(array_filter($locations, function($loc) use ($today) {
                        return date('Y-m-d', strtotime($loc['timestamp'])) == $today;
                    }));
                    echo $todayCount;
                    ?>
                </div>
                <div class="stat-label">Guardadas Hoy</div>
            </div>
            <div class="stat">
                <div class="stat-number">
                    <?php 
                    $week = date('Y-m-d', strtotime('-7 days'));
                    $weekCount = count(array_filter($locations, function($loc) use ($week) {
                        return strtotime($loc['timestamp']) >= strtotime($week);
                    }));
                    echo $weekCount;
                    ?>
                </div>
                <div class="stat-label">Última Semana</div>
            </div>
            <div class="stat">
                <a href="export.php<?php echo $viewingAll ? '?all=true' : ''; ?>" class="export-btn">
                    📥 Exportar
                </a>
            </div>
        </div>

        <?php if (empty($locations)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📍</div>
                <h2>No hay ubicaciones guardadas</h2>
                <p>
                    <?php echo $viewingAll ? 'No hay ubicaciones en el sistema' : 'Comienza guardando tu primera ubicación'; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="locations-list" id="locationsList">
                <?php foreach ($locations as $location): ?>
                    <div class="location-row" 
                         data-name="<?php echo strtolower($location['name']); ?>" 
                         data-comment="<?php echo strtolower($location['comment'] ?? ''); ?>"
                         data-username="<?php echo strtolower($location['username'] ?? ''); ?>">
                        <div class="location-info">
                            <div class="location-icon">📍</div>
                            <div class="location-details">
                                <div class="location-name"><?php echo htmlspecialchars($location['name']); ?></div>
                                <div class="location-meta">
                                    <span>📅 <?php echo date('d/m/Y H:i', strtotime($location['timestamp'])); ?></span>
                                    <?php if (isset($location['accuracy'])): ?>
                                        <span class="accuracy-tag">±<?php echo $location['accuracy']; ?>m</span>
                                    <?php endif; ?>
                                    <?php if (!empty($location['photo']) && file_exists($location['photo'])): ?>
                                        <span>📸 Con foto</span>
                                    <?php endif; ?>
                                    <?php if ($viewingAll && isset($location['username'])): ?>
                                        <span class="user-tag">👤 <?php echo htmlspecialchars($location['username']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="location-actions">
                            <button class="btn-action btn-view" onclick="showDetails('<?php echo htmlspecialchars(json_encode($location), ENT_QUOTES); ?>')">
                                👁️ Ver
                            </button>
                            <button class="btn-action btn-share" onclick="shareLocation(<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>, '<?php echo htmlspecialchars($location['name'], ENT_QUOTES); ?>')">
                                📤 Compartir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de detalles -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Detalles de ubicación</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- El contenido se llenará dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Toast para copiar -->
    <div class="copy-toast" id="copyToast">Enlace copiado al portapapeles</div>

    <script>
        // Función de búsqueda
        document.getElementById('searchBox').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.location-row');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const comment = row.getAttribute('data-comment');
                const username = row.getAttribute('data-username');
                
                if (name.includes(searchTerm) || comment.includes(searchTerm) || username.includes(searchTerm)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Mostrar detalles
        function showDetails(locationJson) {
            const location = JSON.parse(locationJson);
            const modal = document.getElementById('detailsModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = location.name;
            
            let bodyHTML = '';
            
            // Coordenadas
            bodyHTML += `
                <div class="detail-section">
                    <div class="detail-label">📍 Coordenadas</div>
                    <div class="detail-value">${location.latitude}, ${location.longitude}</div>
                </div>
            `;
            
            // Precisión
            if (location.accuracy) {
                bodyHTML += `
                    <div class="detail-section">
                        <div class="detail-label">🎯 Precisión</div>
                        <div class="detail-value">±${location.accuracy} metros</div>
                    </div>
                `;
            }
            
            // Usuario (si es admin viendo todas)
            <?php if ($viewingAll): ?>
            if (location.username) {
                bodyHTML += `
                    <div class="detail-section">
                        <div class="detail-label">👤 Usuario</div>
                        <div class="detail-value">${location.username}</div>
                    </div>
                `;
            }
            <?php endif; ?>
            
            // Fecha
            bodyHTML += `
                <div class="detail-section">
                    <div class="detail-label">📅 Fecha y hora</div>
                    <div class="detail-value">${new Date(location.timestamp).toLocaleString('es-ES')}</div>
                </div>
            `;
            
            // Comentario
            if (location.comment) {
                bodyHTML += `
                    <div class="detail-section">
                        <div class="detail-label">💬 Comentario</div>
                        <div class="detail-value">${location.comment.replace(/\n/g, '<br>')}</div>
                    </div>
                `;
            }
            
            // Foto
            if (location.photo && location.photo !== 'null') {
                bodyHTML += `
                    <div class="detail-section">
                        <div class="detail-label">📸 Foto</div>
                        <img src="${location.photo}" alt="Foto de ${location.name}" class="detail-photo" onclick="window.open('${location.photo}', '_blank')">
                    </div>
                `;
            }
            
            // Botones de acción
            bodyHTML += `
                <div class="modal-actions">
                    <a href="https://www.google.com/maps/search/?api=1&query=${location.latitude},${location.longitude}" 
                       target="_blank" class="btn-action btn-google">
                        🗺️ Google Maps
                    </a>
                    <a href="https://maps.apple.com/?daddr=${location.latitude},${location.longitude}" 
                       target="_blank" class="btn-action btn-apple">
                        🍎 Apple Maps
                    </a>
                    <?php 
                    // Mostrar botón de eliminar solo si es el dueño o admin
                    if (isAdmin()): ?>
                        <form method="POST" style="grid-column: span 2;" onsubmit="return confirmDelete()">
                            <input type="hidden" name="id" value="${location.id}">
                            <button type="submit" name="delete" class="btn-action btn-delete">
                                🗑️ Eliminar ubicación
                            </button>
                        </form>
                    <?php else: ?>
                        ${location.user_id === '<?php echo $currentUser['id']; ?>' ? `
                            <form method="POST" style="grid-column: span 2;" onsubmit="return confirmDelete()">
                                <input type="hidden" name="id" value="${location.id}">
                                <button type="submit" name="delete" class="btn-action btn-delete">
                                    🗑️ Eliminar ubicación
                                </button>
                            </form>
                        ` : ''}
                    <?php endif; ?>
                </div>
            `;
            
            modalBody.innerHTML = bodyHTML;
            modal.style.display = 'block';
        }

        // Cerrar modal
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Confirmar eliminación
        function confirmDelete() {
            return confirm('¿Estás seguro de que quieres eliminar esta ubicación?\n\nEsta acción no se puede deshacer.');
        }

        // Compartir ubicación
        function shareLocation(lat, lon, name) {
            const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;
            const text = `📍 ${name}\nCoordenadas: ${lat}, ${lon}\n\nVer en Google Maps: ${googleMapsUrl}`;
            
            if (navigator.share) {
                // Si el navegador soporta Web Share API
                navigator.share({
                    title: `Ubicación: ${name}`,
                    text: text,
                    url: googleMapsUrl
                }).catch(err => {
                    // Si falla, copiar al portapapeles
                    copyToClipboard(text);
                });
            } else {
                // Si no soporta Web Share API, copiar al portapapeles
                copyToClipboard(text);
            }
        }

        // Copiar al portapapeles
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast();
                });
            } else {
                // Fallback para navegadores antiguos
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast();
            }
        }

        // Mostrar toast
        function showToast() {
            const toast = document.getElementById('copyToast');
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // Auto-ocultar mensaje
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            });
        }, 3000);
    </script>
</body>
</html>