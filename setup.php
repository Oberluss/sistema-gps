<?php
/**
 * INSTALADOR COMPLETO - Sistema GPS
 * Este archivo contiene TODO el sistema y lo instalará automáticamente
 */

if (isset($_POST['install'])) {
    $results = [];
    $errors = [];
    
    // Crear directorios
    $dirs = ['photos', 'backups'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                $results[] = "✅ Directorio '$dir' creado";
            } else {
                $errors[] = "❌ Error al crear directorio '$dir'";
            }
        }
    }
    
    // TODOS LOS ARCHIVOS DEL SISTEMA
    $files = [];
    
    // 1. auth.php
    $files['auth.php'] = '<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Verificar si el usuario es admin
function isAdmin() {
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

// Requerir login (redirige si no está logueado)
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Requerir admin (redirige si no es admin)
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}

// Obtener información del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        "id" => $_SESSION["user_id"],
        "username" => $_SESSION["username"],
        "name" => $_SESSION["name"],
        "role" => $_SESSION["role"]
    ];
}

// Obtener todas las ubicaciones del usuario actual
function getUserLocations($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION["user_id"];
    }
    
    $locationsFile = "locations.json";
    $locations = [];
    
    if (file_exists($locationsFile)) {
        $allLocations = json_decode(file_get_contents($locationsFile), true) ?: [];
        
        // Si es admin, devolver todas las ubicaciones
        if (isAdmin() && $userId === "all") {
            return $allLocations;
        }
        
        // Filtrar por usuario
        foreach ($allLocations as $location) {
            if (isset($location["user_id"]) && $location["user_id"] === $userId) {
                $locations[] = $location;
            }
        }
    }
    
    return $locations;
}

// Obtener información de todos los usuarios (solo admin)
function getAllUsers() {
    if (!isAdmin()) {
        return [];
    }
    
    $usersFile = "users.json";
    if (file_exists($usersFile)) {
        return json_decode(file_get_contents($usersFile), true) ?: [];
    }
    
    return [];
}

// Obtener información de un usuario específico
function getUserById($userId) {
    $users = getAllUsers();
    foreach ($users as $user) {
        if ($user["id"] === $userId) {
            return $user;
        }
    }
    return null;
}

// Actualizar estado de usuario (solo admin)
function updateUserStatus($userId, $status) {
    if (!isAdmin()) {
        return false;
    }
    
    $usersFile = "users.json";
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
    
    foreach ($users as &$user) {
        if ($user["id"] === $userId) {
            $user["status"] = $status;
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;
        }
    }
    
    return false;
}

// Actualizar rol de usuario (solo admin)
function updateUserRole($userId, $role) {
    if (!isAdmin()) {
        return false;
    }
    
    $usersFile = "users.json";
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
    
    foreach ($users as &$user) {
        if ($user["id"] === $userId) {
            $user["role"] = $role;
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;
        }
    }
    
    return false;
}

// Contar estadísticas
function getUserStats($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION["user_id"];
    }
    
    $locations = getUserLocations($userId);
    $today = date("Y-m-d");
    $week = date("Y-m-d", strtotime("-7 days"));
    
    $stats = [
        "total" => count($locations),
        "today" => 0,
        "week" => 0,
        "with_photos" => 0
    ];
    
    foreach ($locations as $loc) {
        $locDate = date("Y-m-d", strtotime($loc["timestamp"]));
        
        if ($locDate === $today) {
            $stats["today"]++;
        }
        
        if (strtotime($loc["timestamp"]) >= strtotime($week)) {
            $stats["week"]++;
        }
        
        if (!empty($loc["photo"]) && file_exists($loc["photo"])) {
            $stats["with_photos"]++;
        }
    }
    
    return $stats;
}
?>';

    // 2. logout.php
    $files['logout.php'] = '<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: login.php");
exit;
?>';

    // 3. save_location.php
    $files['save_location.php'] = '<?php
require_once "auth.php";
requireLogin(); // Requiere estar logueado

// Verificar que sea una petición POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Obtener y validar los datos del formulario
$latitude = isset($_POST["latitude"]) ? floatval($_POST["latitude"]) : null;
$longitude = isset($_POST["longitude"]) ? floatval($_POST["longitude"]) : null;
$accuracy = isset($_POST["accuracy"]) ? intval($_POST["accuracy"]) : null;
$name = isset($_POST["name"]) ? trim($_POST["name"]) : "";
$comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";

// Validar que tengamos todos los datos necesarios
if (!$latitude || !$longitude || empty($name)) {
    header("Location: index.php?error=missing_data");
    exit;
}

// Crear directorio para fotos si no existe
$photosDir = "photos";
if (!file_exists($photosDir)) {
    mkdir($photosDir, 0755, true);
}

// Manejar la foto si se subió
$photoPath = null;
if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES["photo"];
    
    // Validar que sea una imagen
    $allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
    $fileType = mime_content_type($uploadedFile["tmp_name"]);
    
    if (in_array($fileType, $allowedTypes)) {
        // Generar nombre único para la foto
        $extension = pathinfo($uploadedFile["name"], PATHINFO_EXTENSION);
        $photoName = uniqid("photo_") . "_" . time() . "." . $extension;
        $photoPath = $photosDir . "/" . $photoName;
        
        // Mover la foto al directorio
        if (!move_uploaded_file($uploadedFile["tmp_name"], $photoPath)) {
            $photoPath = null; // Si falla, no guardar ruta
        }
    }
}

// Archivo donde guardaremos las ubicaciones
$locationsFile = "locations.json";

// Leer ubicaciones existentes
$locations = [];
if (file_exists($locationsFile)) {
    $jsonContent = file_get_contents($locationsFile);
    $locations = json_decode($jsonContent, true);
    
    // Si el archivo está corrupto o vacío, inicializar como array vacío
    if (!is_array($locations)) {
        $locations = [];
    }
}

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Crear nueva ubicación con user_id
$newLocation = [
    "id" => uniqid(),
    "user_id" => $currentUser["id"], // ID del usuario que guarda
    "username" => $currentUser["username"], // Para referencia rápida
    "name" => $name,
    "latitude" => $latitude,
    "longitude" => $longitude,
    "accuracy" => $accuracy,
    "comment" => $comment,
    "photo" => $photoPath,
    "timestamp" => date("Y-m-d H:i:s")
];

// Agregar la nueva ubicación al principio del array
array_unshift($locations, $newLocation);

// Guardar el archivo JSON actualizado
$jsonData = json_encode($locations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Intentar escribir el archivo
if (file_put_contents($locationsFile, $jsonData) !== false) {
    // Éxito - redirigir con mensaje de confirmación
    header("Location: index.php?saved=true");
} else {
    // Error al guardar - redirigir con mensaje de error
    header("Location: index.php?error=save_failed");
}
exit;
?>';

    // 4. export.php
    $files['export.php'] = '<?php
require_once "auth.php";
requireLogin(); // Requiere estar logueado

// Obtener ubicaciones según el usuario
if (isAdmin() && isset($_GET["all"]) && $_GET["all"] === "true") {
    $locations = getUserLocations("all");
    $exportType = "todas";
} else {
    $locations = getUserLocations();
    $exportType = "usuario_" . $_SESSION["username"];
}

// Generar nombre de archivo con fecha
$filename = "ubicaciones_" . $exportType . "_" . date("Y-m-d_H-i-s") . ".csv";

// Configurar headers para descarga
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Pragma: no-cache");
header("Expires: 0");

// Añadir BOM para que Excel reconozca UTF-8
echo "\xEF\xBB\xBF";

// Abrir output stream
$output = fopen("php://output", "w");

// Escribir encabezados (incluir usuario si es admin exportando todo)
if (isAdmin() && isset($_GET["all"]) && $_GET["all"] === "true") {
    fputcsv($output, ["Usuario", "Nombre", "Latitud", "Longitud", "Precisión (m)", "Comentario", "Tiene Foto", "Fecha y Hora", "Google Maps"], ";");
} else {
    fputcsv($output, ["Nombre", "Latitud", "Longitud", "Precisión (m)", "Comentario", "Tiene Foto", "Fecha y Hora", "Google Maps"], ";");
}

// Escribir datos
foreach ($locations as $location) {
    $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query={$location["latitude"]},{$location["longitude"]}";
    $hasPhoto = (!empty($location["photo"]) && file_exists($location["photo"])) ? "Sí" : "No";
    
    if (isAdmin() && isset($_GET["all"]) && $_GET["all"] === "true") {
        fputcsv($output, [
            $location["username"] ?? "N/A",
            $location["name"],
            $location["latitude"],
            $location["longitude"],
            isset($location["accuracy"]) ? $location["accuracy"] : "N/A",
            $location["comment"] ?? "",
            $hasPhoto,
            date("d/m/Y H:i:s", strtotime($location["timestamp"])),
            $googleMapsUrl
        ], ";");
    } else {
        fputcsv($output, [
            $location["name"],
            $location["latitude"],
            $location["longitude"],
            isset($location["accuracy"]) ? $location["accuracy"] : "N/A",
            $location["comment"] ?? "",
            $hasPhoto,
            date("d/m/Y H:i:s", strtotime($location["timestamp"])),
            $googleMapsUrl
        ], ";");
    }
}

fclose($output);
exit;
?>';

    // 5. .htaccess
    $files['.htaccess'] = '# Archivo index predeterminado
DirectoryIndex index.php

# Proteger archivos JSON
<FilesMatch "\.(json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevenir listado de directorios
Options -Indexes';

    // 6. Archivos JSON
    $files['users.json'] = '[]';
    $files['locations.json'] = '[]';

    // Crear todos los archivos
    foreach ($files as $filename => $content) {
        if (file_put_contents($filename, $content) !== false) {
            $results[] = "✅ Archivo '$filename' creado";
        } else {
            $errors[] = "❌ Error al crear '$filename'";
        }
    }
    
    // Marcar como instalado si no hay errores
    if (count($errors) == 0) {
        file_put_contents('.installed', date('Y-m-d H:i:s'));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Sistema GPS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .body {
            padding: 40px;
        }
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
        }
        .btn:hover {
            background: #218838;
        }
        .result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .file-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador Sistema GPS</h1>
            <p>Instalación parcial del sistema</p>
        </div>
        
        <div class="body">
            <?php if (!isset($_POST['install'])): ?>
                <h2>Bienvenido al Instalador</h2>
                <p>Este instalador creará la estructura base del sistema GPS.</p>
                
                <div class="info">
                    <h3>📋 Se crearán:</h3>
                    <ul>
                        <li>Directorios: photos/, backups/</li>
                        <li>Archivos de configuración: auth.php, logout.php, .htaccess</li>
                        <li>Archivos de procesamiento: save_location.php, export.php</li>
                        <li>Bases de datos JSON vacías</li>
                    </ul>
                </div>
                
                <div class="warning">
                    <h3>⚠️ IMPORTANTE - Archivos Faltantes</h3>
                    <p>Después de la instalación, necesitarás crear manualmente estos archivos grandes:</p>
                    <ol>
                        <li><strong>login.php</strong> - Sistema de autenticación (buscar en artifacts)</li>
                        <li><strong>index.php</strong> - Página principal (buscar en artifacts)</li>
                        <li><strong>gestion.php</strong> - Gestión de ubicaciones (buscar en artifacts)</li>
                        <li><strong>admin.php</strong> - Panel de administración (buscar en artifacts)</li>
                    </ol>
                    <p>Estos archivos son muy grandes para incluirlos aquí. Búscalos en los artifacts de la conversación.</p>
                </div>
                
                <form method="POST">
                    <button type="submit" name="install" class="btn">
                        Instalar Sistema Base
                    </button>
                </form>
                
            <?php else: ?>
                <h2>Resultados de la Instalación</h2>
                
                <?php foreach ($results as $result): ?>
                    <div class="result success"><?php echo $result; ?></div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $error): ?>
                    <div class="result error"><?php echo $error; ?></div>
                <?php endforeach; ?>
                
                <?php if (count($errors) == 0): ?>
                    <div class="info">
                        <h3>✅ Instalación Base Completada</h3>
                        <p>La estructura base del sistema se ha creado correctamente.</p>
                    </div>
                    
                    <div class="warning">
                        <h3>📝 Siguientes Pasos - MUY IMPORTANTE</h3>
                        <p>Ahora debes crear estos 4 archivos manualmente:</p>
                        
                        <h4>1. login.php</h4>
                        <p>Busca en los artifacts: "Sistema de Login y Registro - login.php"</p>
                        
                        <h4>2. index.php</h4>
                        <p>Busca en los artifacts: "Código completo de index.php"</p>
                        
                        <h4>3. gestion.php</h4>
                        <p>Busca en los artifacts: "Página de Gestión de Ubicaciones - gestion.php"</p>
                        
                        <h4>4. admin.php</h4>
                        <p>Busca en los artifacts: "Panel de Administración - admin.php"</p>
                        
                        <p><strong>Instrucciones:</strong></p>
                        <ol>
                            <li>Busca cada artifact en la conversación</li>
                            <li>Copia TODO el contenido</li>
                            <li>Crea un archivo nuevo con el nombre exacto</li>
                            <li>Pega el contenido y guarda</li>
                        </ol>
                    </div>
                    
                    <div class="info">
                        <h3>📁 Archivos Creados:</h3>
                        <div class="file-list">
                            <div class="file-item">✅ auth.php</div>
                            <div class="file-item">✅ logout.php</div>
                            <div class="file-item">✅ save_location.php</div>
                            <div class="file-item">✅ export.php</div>
                            <div class="file-item">✅ .htaccess</div>
                            <div class="file-item">✅ users.json</div>
                            <div class="file-item">✅ locations.json</div>
                            <div class="file-item">✅ photos/</div>
                            <div class="file-item">✅ backups/</div>
                        </div>
                        
                        <h3>📁 Archivos Pendientes:</h3>
                        <div class="file-list">
                            <div class="file-item" style="background: #f8d7da;">❌ login.php</div>
                            <div class="file-item" style="background: #f8d7da;">❌ index.php</div>
                            <div class="file-item" style="background: #f8d7da;">❌ gestion.php</div>
                            <div class="file-item" style="background: #f8d7da;">❌ admin.php</div>
                        </div>
                    </div>
                    
                    <p style="text-align: center; margin-top: 30px;">
                        <strong>Una vez hayas copiado los 4 archivos faltantes:</strong><br>
                        <a href="login.php" style="display: inline-block; margin-top: 10px; padding: 10px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                            Ir al Sistema
                        </a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>