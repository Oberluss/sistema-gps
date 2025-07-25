<?php
require_once 'auth.php';
requireLogin(); // Requiere estar logueado

$currentUser = getCurrentUser();
$userStats = getUserStats();

// Mostrar mensaje si viene de guardar
$message = '';
if (isset($_GET['saved']) && $_GET['saved'] == 'true') {
    $message = 'success';
} elseif (isset($_GET['error'])) {
    $message = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Guardar Ubicación GPS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .header-nav {
            background: rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #667eea;
        }

        .nav-links {
            display: flex;
            gap: 10px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .main-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            min-height: calc(100vh - 70px);
        }

        .container {
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        h1 {
            color: white;
            font-size: 24px;
            font-weight: 600;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .coordinates-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px dashed #ddd;
            transition: all 0.3s;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .coordinates-box.tracking {
            border-color: #ffc107;
            background: #fffbf0;
        }

        .coordinates-box.success {
            border-color: #28a745;
            background: #f0fdf4;
        }

        .coordinates-box p {
            margin: 5px 0;
            font-size: 14px;
        }

        .accuracy-meter {
            margin: 15px 0;
            position: relative;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
        }

        .accuracy-bar {
            height: 100%;
            background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #28a745 100%);
            border-radius: 15px;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-gps {
            background: #28a745;
            margin-bottom: 15px;
        }

        .btn-gps:hover {
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
            animation: slideDown 0.3s ease-out;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            padding-top: 10px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
        }

        .photo-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }

        .photo-input {
            display: none;
        }

        .photo-label {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .photo-label:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .photo-preview {
            margin-top: 15px;
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .photo-preview img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .remove-photo {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 15px;
            background: #dc3545;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .remove-photo:hover {
            background: #c82333;
        }

        .precision-settings {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .precision-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #667eea;
            border-radius: 5px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }

            .user-info {
                order: 2;
            }

            .nav-links {
                order: 1;
            }
        }
    </style>
</head>
<body>
    <div class="header-nav">
        <div class="nav-container">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">
                        <?php echo $currentUser['role'] === 'admin' ? '👑 Administrador' : '👤 Usuario'; ?>
                    </div>
                </div>
            </div>
            <div class="nav-links">
                <a href="gestion.php" class="nav-link">📋 Ver ubicaciones</a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php" class="nav-link">🛡️ Administrar</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link">🚪 Salir</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="logo">
                <div class="logo-icon">📍</div>
                <h1>Guardar Ubicación</h1>
            </div>

            <div class="form-card">
                <?php if ($message == 'success'): ?>
                    <div class="message success">
                        ✅ Ubicación guardada correctamente
                    </div>
                <?php elseif ($message == 'error'): ?>
                    <div class="message error">
                        ❌ Error al guardar la ubicación
                    </div>
                <?php endif; ?>

                <div class="stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $userStats['total']; ?></div>
                        <div class="stat-label">Mis ubicaciones</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $userStats['today']; ?></div>
                        <div class="stat-label">Hoy</div>
                    </div>
                </div>

                <div class="precision-settings">
                    <label>🎯 Precisión deseada: 
                        <input type="number" class="precision-input" id="desiredAccuracy" value="10" min="1" max="100"> metros
                    </label>
                </div>

                <form id="locationForm" method="POST" action="save_location.php" enctype="multipart/form-data">
                    <div class="coordinates-box" id="coordsDisplay">
                        <p><strong>📍 Esperando coordenadas GPS...</strong></p>
                        <p>Presiona el botón para obtener tu ubicación</p>
                    </div>

                    <button type="button" class="btn btn-gps" onclick="startTracking()">
                        📍 Obtener Mi Ubicación Actual
                    </button>

                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    <input type="hidden" id="accuracy" name="accuracy">

                    <div class="form-group">
                        <label for="name">📌 Nombre del lugar:</label>
                        <input type="text" id="name" name="name" required placeholder="Ej: Casa, Oficina, Restaurante...">
                    </div>

                    <div class="form-group">
                        <label for="comment">💬 Comentario (opcional):</label>
                        <textarea id="comment" name="comment" placeholder="Añade detalles sobre este lugar..."></textarea>
                    </div>

                    <div class="photo-section">
                        <p>📸 Añadir foto (opcional)</p>
                        <label for="photo" class="photo-label">
                            Tomar foto
                        </label>
                        <input type="file" id="photo" name="photo" class="photo-input" accept="image/*" capture="environment">
                        
                        <div id="photoPreview" style="display: none;">
                            <div class="photo-preview">
                                <img id="previewImage" src="" alt="Vista previa">
                            </div>
                            <span class="remove-photo" onclick="removePhoto()">Eliminar foto</span>
                        </div>
                    </div>

                    <button type="submit" class="btn" id="saveBtn" disabled>
                        💾 Guardar Ubicación
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let hasCoordinates = false;
        let watchId = null;
        let bestAccuracy = Infinity;

        function startTracking() {
            const coordsDisplay = document.getElementById('coordsDisplay');
            const desiredAccuracy = parseInt(document.getElementById('desiredAccuracy').value);
            
            coordsDisplay.className = 'coordinates-box tracking';
            coordsDisplay.innerHTML = '<p><strong>🔄 Rastreando ubicación...</strong></p><p>Buscando precisión de ' + desiredAccuracy + ' metros</p>';
            
            if (navigator.geolocation) {
                // Detener rastreo anterior si existe
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                }
                
                // Iniciar nuevo rastreo
                watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        updatePosition(position, desiredAccuracy);
                    },
                    showError,
                    {
                        enableHighAccuracy: true,
                        timeout: 30000,
                        maximumAge: 0
                    }
                );
            } else {
                coordsDisplay.innerHTML = '<p style="color: red;">❌ Tu navegador no soporta geolocalización</p>';
            }
        }

        function updatePosition(position, desiredAccuracy) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            // Actualizar valores ocultos
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lon;
            document.getElementById('accuracy').value = accuracy.toFixed(0);
            
            const coordsDisplay = document.getElementById('coordsDisplay');
            
            // Calcular porcentaje para la barra de precisión (invertido: 100% = mejor precisión)
            let accuracyPercent = Math.max(0, Math.min(100, (1 - accuracy / 100) * 100));
            
            if (accuracy <= desiredAccuracy) {
                // Precisión deseada alcanzada
                coordsDisplay.className = 'coordinates-box success';
                coordsDisplay.innerHTML = `
                    <p style="color: green;"><strong>✅ Ubicación obtenida con precisión deseada</strong></p>
                    <p><strong>📍 Coordenadas:</strong> ${lat.toFixed(6)}, ${lon.toFixed(6)}</p>
                    <p><strong>🎯 Precisión:</strong> ±${accuracy.toFixed(0)} metros</p>
                `;
                
                hasCoordinates = true;
                document.getElementById('saveBtn').disabled = false;
                
                // Detener el rastreo
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
            } else {
                // Aún buscando mejor precisión
                coordsDisplay.className = 'coordinates-box tracking';
                coordsDisplay.innerHTML = `
                    <p><strong>🔄 Mejorando precisión...</strong></p>
                    <p><strong>📍 Coordenadas actuales:</strong> ${lat.toFixed(6)}, ${lon.toFixed(6)}</p>
                    <p><strong>🎯 Precisión actual:</strong> ±${accuracy.toFixed(0)} metros</p>
                    <div class="accuracy-meter">
                        <div class="accuracy-bar" style="width: ${accuracyPercent}%">
                            ${accuracy.toFixed(0)}m
                        </div>
                    </div>
                    <p style="font-size: 12px;">Objetivo: ±${desiredAccuracy} metros</p>
                `;
                
                // Guardar si es la mejor precisión hasta ahora
                if (accuracy < bestAccuracy) {
                    bestAccuracy = accuracy;
                    hasCoordinates = true;
                    document.getElementById('saveBtn').disabled = false;
                }
            }
        }

        function showError(error) {
            const coordsDisplay = document.getElementById('coordsDisplay');
            let message = '';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = "❌ Has denegado el acceso a tu ubicación";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = "❌ La información de ubicación no está disponible";
                    break;
                case error.TIMEOUT:
                    message = "❌ La solicitud de ubicación ha excedido el tiempo límite";
                    break;
                case error.UNKNOWN_ERROR:
                    message = "❌ Ha ocurrido un error desconocido";
                    break;
            }
            
            coordsDisplay.className = 'coordinates-box';
            coordsDisplay.innerHTML = `<p style="color: red;">${message}</p>`;
            
            // Detener el rastreo
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        // Manejo de fotos
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('photoPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function removePhoto() {
            document.getElementById('photo').value = '';
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('previewImage').src = '';
        }

        // Validar formulario antes de enviar
        document.getElementById('locationForm').addEventListener('submit', function(e) {
            if (!hasCoordinates) {
                e.preventDefault();
                alert('Por favor, obtén tu ubicación GPS primero');
                return false;
            }
            
            const name = document.getElementById('name').value.trim();
            if (!name) {
                e.preventDefault();
                alert('Por favor, ingresa un nombre para la ubicación');
                return false;
            }
        });

        // Auto-ocultar mensajes
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