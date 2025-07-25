<?php
/**
 * INSTALADOR ULTRA-COMPLETO - Sistema GPS
 * Este archivo contiene ABSOLUTAMENTE TODO el sistema completo
 */

// Verificar si ya está instalado
if (file_exists('.installed') && !isset($_POST['force_install'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema ya instalado</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; text-align: center; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { padding: 15px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block; }
            .btn-danger { background: #dc3545; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>✅ Sistema ya instalado</h2>
            <p>El sistema GPS ya está instalado en este directorio.</p>
            <a href="login.php" class="btn">Ir al Sistema</a>
            <form method="POST" style="display: inline;">
                <button type="submit" name="force_install" class="btn btn-danger">Reinstalar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_POST['install']) || isset($_POST['force_install'])) {
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
        } else {
            $results[] = "✅ Directorio '$dir' ya existe";
        }
    }
    
    // ARCHIVO 1: auth.php
    $authPhp = '<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function isLoggedIn() { return isset($_SESSION["user_id"]); }
function isAdmin() { return isset($_SESSION["role"]) && $_SESSION["role"] === "admin"; }

function requireLogin() {
    if (!isLoggedIn()) { header("Location: login.php"); exit; }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) { header("Location: index.php"); exit; }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        "id" => $_SESSION["user_id"],
        "username" => $_SESSION["username"], 
        "name" => $_SESSION["name"],
        "role" => $_SESSION["role"]
    ];
}

function getUserLocations($userId = null) {
    if ($userId === null) $userId = $_SESSION["user_id"];
    
    $locationsFile = "locations.json";
    $locations = [];
    
    if (file_exists($locationsFile)) {
        $allLocations = json_decode(file_get_contents($locationsFile), true) ?: [];
        
        if (isAdmin() && $userId === "all") return $allLocations;
        
        foreach ($allLocations as $location) {
            if (isset($location["user_id"]) && $location["user_id"] === $userId) {
                $locations[] = $location;
            }
        }
    }
    return $locations;
}

function getAllUsers() {
    if (!isAdmin()) return [];
    $usersFile = "users.json";
    if (file_exists($usersFile)) {
        return json_decode(file_get_contents($usersFile), true) ?: [];
    }
    return [];
}

function updateUserStatus($userId, $status) {
    if (!isAdmin()) return false;
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

function updateUserRole($userId, $role) {
    if (!isAdmin()) return false;
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

function getUserStats($userId = null) {
    if ($userId === null) $userId = $_SESSION["user_id"];
    
    $locations = getUserLocations($userId);
    $today = date("Y-m-d");
    $week = date("Y-m-d", strtotime("-7 days"));
    
    $stats = ["total" => count($locations), "today" => 0, "week" => 0, "with_photos" => 0];
    
    foreach ($locations as $loc) {
        $locDate = date("Y-m-d", strtotime($loc["timestamp"]));
        if ($locDate === $today) $stats["today"]++;
        if (strtotime($loc["timestamp"]) >= strtotime($week)) $stats["week"]++;
        if (!empty($loc["photo"]) && file_exists($loc["photo"])) $stats["with_photos"]++;
    }
    return $stats;
}
?>';

    // ARCHIVO 2: login.php (COMPLETO)
    $loginPhp = '<?php
session_start();

if (isset($_SESSION[\'user_id\'])) {
    header(\'Location: index.php\');
    exit;
}

$error = \'\';
$success = \'\';

if ($_SERVER[\'REQUEST_METHOD\'] == \'POST\' && isset($_POST[\'login\'])) {
    $username = trim($_POST[\'username\']);
    $password = $_POST[\'password\'];
    
    if (empty($username) || empty($password)) {
        $error = \'Por favor, completa todos los campos\';
    } else {
        $usersFile = \'users.json\';
        $users = [];
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
        }
        
        $userFound = false;
        foreach ($users as $user) {
            if ($user[\'username\'] === $username && password_verify($password, $user[\'password\'])) {
                if ($user[\'status\'] === \'blocked\') {
                    $error = \'Tu cuenta está bloqueada. Contacta al administrador.\';
                } else {
                    $_SESSION[\'user_id\'] = $user[\'id\'];
                    $_SESSION[\'username\'] = $user[\'username\'];
                    $_SESSION[\'name\'] = $user[\'name\'];
                    $_SESSION[\'role\'] = $user[\'role\'];
                    header(\'Location: index.php\');
                    exit;
                }
                $userFound = true;
                break;
            }
        }
        
        if (!$userFound) {
            $error = \'Usuario o contraseña incorrectos\';
        }
    }
}

if ($_SERVER[\'REQUEST_METHOD\'] == \'POST\' && isset($_POST[\'register\'])) {
    $username = trim($_POST[\'reg_username\']);
    $name = trim($_POST[\'reg_name\']);
    $password = $_POST[\'reg_password\'];
    $confirmPassword = $_POST[\'reg_confirm_password\'];
    
    if (empty($username) || empty($name) || empty($password) || empty($confirmPassword)) {
        $error = \'Por favor, completa todos los campos\';
    } elseif (strlen($password) < 6) {
        $error = \'La contraseña debe tener al menos 6 caracteres\';
    } elseif ($password !== $confirmPassword) {
        $error = \'Las contraseñas no coinciden\';
    } else {
        $usersFile = \'users.json\';
        $users = [];
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
        }
        
        $userExists = false;
        foreach ($users as $user) {
            if ($user[\'username\'] === $username) {
                $userExists = true;
                break;
            }
        }
        
        if ($userExists) {
            $error = \'Este nombre de usuario ya existe\';
        } else {
            $newUser = [
                \'id\' => uniqid(),
                \'username\' => $username,
                \'name\' => $name,
                \'password\' => password_hash($password, PASSWORD_DEFAULT),
                \'role\' => count($users) === 0 ? \'admin\' : \'user\',
                \'status\' => \'active\',
                \'created\' => date(\'Y-m-d H:i:s\')
            ];
            
            $users[] = $newUser;
            
            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $success = \'Usuario registrado correctamente. Ahora puedes iniciar sesión.\';
            } else {
                $error = \'Error al registrar usuario\';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema GPS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .body { padding: 30px; }
        .form-toggle {
            display: flex;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
        }
        .toggle-btn {
            flex: 1;
            padding: 10px;
            background: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .toggle-btn.active {
            background: #667eea;
            color: white;
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #5a6fd8; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-container { display: none; }
        .form-container.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌍 Sistema GPS</h1>
            <p>Bienvenido al sistema</p>
        </div>
        
        <div class="body">
            <div class="form-toggle">
                <button class="toggle-btn active" onclick="showLogin()">Iniciar Sesión</button>
                <button class="toggle-btn" onclick="showRegister()">Registrarse</button>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div id="login-form" class="form-container active">
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn">Iniciar Sesión</button>
                </form>
            </div>
            
            <div id="register-form" class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="reg_username">Usuario:</label>
                        <input type="text" id="reg_username" name="reg_username" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_name">Nombre completo:</label>
                        <input type="text" id="reg_name" name="reg_name" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Contraseña:</label>
                        <input type="password" id="reg_password" name="reg_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="reg_confirm_password">Confirmar contraseña:</label>
                        <input type="password" id="reg_confirm_password" name="reg_confirm_password" required minlength="6">
                    </div>
                    <button type="submit" name="register" class="btn">Registrarse</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showLogin() {
            document.getElementById(\'login-form\').classList.add(\'active\');
            document.getElementById(\'register-form\').classList.remove(\'active\');
            document.querySelectorAll(\'.toggle-btn\')[0].classList.add(\'active\');
            document.querySelectorAll(\'.toggle-btn\')[1].classList.remove(\'active\');
        }
        
        function showRegister() {
            document.getElementById(\'register-form\').classList.add(\'active\');
            document.getElementById(\'login-form\').classList.remove(\'active\');
            document.querySelectorAll(\'.toggle-btn\')[1].classList.add(\'active\');
            document.querySelectorAll(\'.toggle-btn\')[0].classList.remove(\'active\');
        }
    </script>
</body>
</html>';

    // Array de archivos para crear
    $files = [
        'auth.php' => $authPhp,
        'login.php' => $loginPhp,
        
        // logout.php
        'logout.php' => '<?php
session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
header("Location: login.php");
exit;
?>',

        // save_location.php
        'save_location.php' => '<?php
require_once "auth.php";
requireLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$latitude = isset($_POST["latitude"]) ? floatval($_POST["latitude"]) : null;
$longitude = isset($_POST["longitude"]) ? floatval($_POST["longitude"]) : null;
$accuracy = isset($_POST["accuracy"]) ? intval($_POST["accuracy"]) : null;
$name = isset($_POST["name"]) ? trim($_POST["name"]) : "";
$comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";

if (!$latitude || !$longitude || empty($name)) {
    header("Location: index.php?error=missing_data");
    exit;
}

$photosDir = "photos";
if (!file_exists($photosDir)) mkdir($photosDir, 0755, true);

$photoPath = null;
if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES["photo"];
    $allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
    $fileType = mime_content_type($uploadedFile["tmp_name"]);
    
    if (in_array($fileType, $allowedTypes)) {
        $extension = pathinfo($uploadedFile["name"], PATHINFO_EXTENSION);
        $photoName = uniqid("photo_") . "_" . time() . "." . $extension;
        $photoPath = $photosDir . "/" . $photoName;
        
        if (!move_uploaded_file($uploadedFile["tmp_name"], $photoPath)) {
            $photoPath = null;
        }
    }
}

$locationsFile = "locations.json";
$locations = [];
if (file_exists($locationsFile)) {
    $jsonContent = file_get_contents($locationsFile);
    $locations = json_decode($jsonContent, true);
    if (!is_array($locations)) $locations = [];
}

$currentUser = getCurrentUser();
$newLocation = [
    "id" => uniqid(),
    "user_id" => $currentUser["id"],
    "username" => $currentUser["username"],
    "name" => $name,
    "latitude" => $latitude,
    "longitude" => $longitude,
    "accuracy" => $accuracy,
    "comment" => $comment,
    "photo" => $photoPath,
    "timestamp" => date("Y-m-d H:i:s")
];

array_unshift($locations, $newLocation);
$jsonData = json_encode($locations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($locationsFile, $jsonData) !== false) {
    header("Location: index.php?saved=true");
} else {
    header("Location: index.php?error=save_failed");
}
exit;
?>',

        // export.php
        'export.php' => '<?php
require_once "auth.php";
requireLogin();

if (isAdmin() && isset($_GET["all"]) && $_GET["all"] === "true") {
    $locations = getUserLocations("all");
    $exportType = "todas";
} else {
    $locations = getUserLocations();
    $exportType = "usuario_" . $_SESSION["username"];
}

$filename = "ubicaciones_" . $exportType . "_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
$output = fopen("php://output", "w");

if (isAdmin() && isset($_GET["all"]) && $_GET["all"] === "true") {
    fputcsv($output, ["Usuario", "Nombre", "Latitud", "Longitud", "Precisión (m)", "Comentario", "Tiene Foto", "Fecha y Hora", "Google Maps"], ";");
} else {
    fputcsv($output, ["Nombre", "Latitud", "Longitud", "Precisión (m)", "Comentario", "Tiene Foto", "Fecha y Hora", "Google Maps"], ";");
}

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
?>',

        // .htaccess
        '.htaccess' => 'DirectoryIndex index.php
<FilesMatch "\.(json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
Options -Indexes',

        // Archivos JSON
        'users.json' => '[]',
        'locations.json' => '[]'
    ];

    // Crear index.php dinámicamente (simplificado)
    $indexPhp = createIndexFile();
    $files['index.php'] = $indexPhp;
    
    // Crear gestion.php dinámicamente (simplificado)
    $gestionPhp = createGestionFile();
    $files['gestion.php'] = $gestionPhp;
    
    // Crear admin.php dinámicamente (simplificado)
    $adminPhp = createAdminFile();
    $files['admin.php'] = $adminPhp;

    // Crear todos los archivos
    foreach ($files as $filename => $content) {
        if (file_put_contents($filename, $content) !== false) {
            $results[] = "✅ Archivo '$filename' creado";
        } else {
            $errors[] = "❌ Error al crear '$filename'";
        }
    }

    // Marcar como instalado
    if (count($errors) == 0) {
        file_put_contents('.installed', date('Y-m-d H:i:s'));
    }
}

function createIndexFile() {
    return '<?php
require_once "auth.php";
requireLogin();

$currentUser = getCurrentUser();
$stats = getUserStats();
$locations = getUserLocations();

$message = "";
$messageType = "";

if (isset($_GET["saved"]) && $_GET["saved"] === "true") {
    $message = "✅ Ubicación guardada correctamente";
    $messageType = "success";
} elseif (isset($_GET["error"])) {
    switch ($_GET["error"]) {
        case "missing_data":
            $message = "❌ Faltan datos obligatorios";
            $messageType = "error";
            break;
        case "save_failed":
            $message = "❌ Error al guardar la ubicación";
            $messageType = "error";
            break;
        default:
            $message = "❌ Error desconocido";
            $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema GPS - <?php echo htmlspecialchars($currentUser["name"]); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav {
            background: #f8f9fa;
            padding: 0;
            display: flex;
            overflow-x: auto;
        }
        .nav a {
            padding: 15px 20px;
            text-decoration: none;
            color: #495057;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .nav a:hover, .nav a.active {
            background: white;
            border-bottom-color: #667eea;
            color: #667eea;
        }
        .main-content { padding: 30px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-card h3 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 5px;
        }
        .location-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #5a6fd8; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .gps-status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .gps-searching { background: #fff3cd; color: #856404; }
        .gps-found { background: #d4edda; color: #155724; }
        .gps-error { background: #f8d7da; color: #721c24; }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .location-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .location-info h4 {
            color: #667eea;
            margin-bottom: 5px;
        }
        .location-meta {
            color: #6c757d;
            font-size: 14px;
        }
        .location-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 5px 15px;
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .header { text-align: center; }
            .form-grid { grid-template-columns: 1fr; }
            .location-item { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🌍 Sistema GPS</h1>
                <p>Gestión de ubicaciones</p>
            </div>
            <div style="text-align: right;">
                <p>Bienvenido, <strong><?php echo htmlspecialchars($currentUser["name"]); ?></strong></p>
                <p>Rol: <?php echo ucfirst($currentUser["role"]); ?></p>
            </div>
        </div>
        
        <div class="nav">
            <a href="index.php" class="active">Inicio</a>
            <a href="gestion.php">Mis Ubicaciones</a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">Administración</a>
            <?php endif; ?>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
        
        <div class="main-content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo $stats["total"]; ?></h3>
                    <p>Total ubicaciones</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["today"]; ?></h3>
                    <p>Hoy</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["week"]; ?></h3>
                    <p>Esta semana</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["with_photos"]; ?></h3>
                    <p>Con fotos</p>
                </div>
            </div>
            
            <div class="location-form">
                <h2>📍 Guardar Nueva Ubicación</h2>
                
                <div id="gps-status" class="gps-status gps-searching" style="display: none;">
                    🔍 Buscando ubicación GPS...
                </div>
                
                <form action="save_location.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div>
                            <div class="form-group">
                                <label for="name">Nombre de la ubicación *</label>
                                <input type="text" id="name" name="name" required placeholder="Ej: Mi casa, Oficina, etc.">
                            </div>
                            <div class="form-group">
                                <label for="comment">Comentario</label>
                                <textarea id="comment" name="comment" rows="3" placeholder="Descripción adicional..."></textarea>
                            </div>
                            <div class="form-group">
                                <label for="photo">Foto (opcional)</label>
                                <input type="file" id="photo" name="photo" accept="image/*">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="latitude">Latitud</label>
                                <input type="number" id="latitude" name="latitude" step="any" readonly required>
                            </div>
                            <div class="form-group">
                                <label for="longitude">Longitud</label>
                                <input type="number" id="longitude" name="longitude" step="any" readonly required>
                            </div>
                            <div class="form-group">
                                <label for="accuracy">Precisión (metros)</label>
                                <input type="number" id="accuracy" name="accuracy" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <button type="button" onclick="getCurrentLocation()" class="btn btn-secondary">
                            📡 Obtener Ubicación Actual
                        </button>
                        <button type="submit" class="btn btn-success" id="saveBtn" disabled>
                            💾 Guardar Ubicación
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if (count($locations) > 0): ?>
                <div style="margin-top: 30px;">
                    <h2>📍 Ubicaciones Recientes</h2>
                    <?php foreach (array_slice($locations, 0, 5) as $location): ?>
                        <div class="location-item">
                            <div class="location-info">
                                <h4><?php echo htmlspecialchars($location["name"]); ?></h4>
                                <p class="location-meta">
                                    📅 <?php echo date("d/m/Y H:i", strtotime($location["timestamp"])); ?>
                                    <?php if (!empty($location["comment"])): ?>
                                        <br>💬 <?php echo htmlspecialchars($location["comment"]); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($location["photo"]) && file_exists($location["photo"])): ?>
                                        <br>📷 Con foto
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="location-actions">
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $location["latitude"]; ?>,<?php echo $location["longitude"]; ?>" 
                                   target="_blank" class="btn btn-small">
                                    🗺️ Ver en Google Maps
                                </a>
                                <?php if (!empty($location["photo"]) && file_exists($location["photo"])): ?>
                                    <a href="<?php echo htmlspecialchars($location["photo"]); ?>" target="_blank" class="btn btn-small btn-secondary">
                                        📷 Ver Foto
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($locations) > 5): ?>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="gestion.php" class="btn">Ver Todas las Ubicaciones</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <h3>📍 No hay ubicaciones guardadas</h3>
                    <p>Usa el formulario de arriba para guardar tu primera ubicación</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function getCurrentLocation() {
            const statusDiv = document.getElementById("gps-status");
            const saveBtn = document.getElementById("saveBtn");
            
            statusDiv.style.display = "block";
            statusDiv.className = "gps-status gps-searching";
            statusDiv.innerHTML = "🔍 Buscando ubicación GPS...";
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById("latitude").value = position.coords.latitude;
                        document.getElementById("longitude").value = position.coords.longitude;
                        document.getElementById("accuracy").value = Math.round(position.coords.accuracy);
                        
                        statusDiv.className = "gps-status gps-found";
                        statusDiv.innerHTML = "✅ Ubicación obtenida correctamente (±" + Math.round(position.coords.accuracy) + " metros)";
                        
                        saveBtn.disabled = false;
                        
                        setTimeout(() => {
                            statusDiv.style.display = "none";
                        }, 3000);
                    },
                    function(error) {
                        let errorMsg = "";
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = "❌ Acceso a la ubicación denegado por el usuario";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg = "❌ Información de ubicación no disponible";
                                break;
                            case error.TIMEOUT:
                                errorMsg = "❌ Tiempo de espera agotado";
                                break;
                            default:
                                errorMsg = "❌ Error desconocido al obtener ubicación";
                                break;
                        }
                        
                        statusDiv.className = "gps-status gps-error";
                        statusDiv.innerHTML = errorMsg;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            } else {
                statusDiv.className = "gps-status gps-error";
                statusDiv.innerHTML = "❌ Geolocalización no es compatible con este navegador";
            }
        }
        
        window.addEventListener("load", function() {
            if (navigator.geolocation) {
                getCurrentLocation();
            }
        });
    </script>
</body>
</html>';
}

function createGestionFile() {
    return '<?php
require_once "auth.php";
requireLogin();

$currentUser = getCurrentUser();
$locations = getUserLocations();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_location"])) {
    $locationId = $_POST["location_id"];
    
    $locationsFile = "locations.json";
    $allLocations = [];
    if (file_exists($locationsFile)) {
        $allLocations = json_decode(file_get_contents($locationsFile), true) ?: [];
    }
    
    foreach ($allLocations as $index => $location) {
        if ($location["id"] === $locationId && $location["user_id"] === $currentUser["id"]) {
            if (!empty($location["photo"]) && file_exists($location["photo"])) {
                unlink($location["photo"]);
            }
            unset($allLocations[$index]);
            break;
        }
    }
    
    $allLocations = array_values($allLocations);
    file_put_contents($locationsFile, json_encode($allLocations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    header("Location: gestion.php?deleted=true");
    exit;
}

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$filteredLocations = $locations;

if (!empty($search)) {
    $filteredLocations = array_filter($filteredLocations, function($location) use ($search) {
        return stripos($location["name"], $search) !== false || 
               stripos($location["comment"], $search) !== false;
    });
}

$message = "";
if (isset($_GET["deleted"]) && $_GET["deleted"] === "true") {
    $message = "✅ Ubicación eliminada correctamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ubicaciones - Sistema GPS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        .nav {
            background: #f8f9fa;
            padding: 0;
            display: flex;
            overflow-x: auto;
        }
        .nav a {
            padding: 15px 20px;
            text-decoration: none;
            color: #495057;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .nav a:hover, .nav a.active {
            background: white;
            border-bottom-color: #667eea;
            color: #667eea;
        }
        .main-content { padding: 30px; }
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .search-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .search-form input {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            margin-right: 10px;
        }
        .location-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .location-info h4 {
            color: #667eea;
            margin-bottom: 5px;
        }
        .location-meta {
            color: #6c757d;
            font-size: 14px;
        }
        .location-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📍 Gestión de Ubicaciones</h1>
            <p>Administra tus ubicaciones guardadas</p>
        </div>
        
        <div class="nav">
            <a href="index.php">Inicio</a>
            <a href="gestion.php" class="active">Mis Ubicaciones</a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">Administración</a>
            <?php endif; ?>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
        
        <div class="main-content">
            <?php if ($message): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="search-form">
                <form method="GET">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar ubicaciones...">
                    <button type="submit" class="btn">🔍 Buscar</button>
                    <a href="gestion.php" class="btn">Limpiar</a>
                    <a href="export.php" class="btn btn-success">📊 Exportar CSV</a>
                </form>
            </div>
            
            <?php if (count($filteredLocations) > 0): ?>
                <p style="margin-bottom: 20px;">
                    <strong>Mostrando:</strong> <?php echo count($filteredLocations); ?> ubicaciones
                </p>
                
                <?php foreach ($filteredLocations as $location): ?>
                    <div class="location-card">
                        <div class="location-info">
                            <h4><?php echo htmlspecialchars($location["name"]); ?></h4>
                            <p class="location-meta">
                                📅 <?php echo date("d/m/Y H:i:s", strtotime($location["timestamp"])); ?>
                                <?php if (!empty($location["comment"])): ?>
                                    <br>💬 <?php echo htmlspecialchars($location["comment"]); ?>
                                <?php endif; ?>
                                <br>📍 Lat: <?php echo number_format($location["latitude"], 6); ?>, Lng: <?php echo number_format($location["longitude"], 6); ?>
                                <?php if (!empty($location["photo"]) && file_exists($location["photo"])): ?>
                                    <br>📷 Con foto
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="location-actions">
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $location["latitude"]; ?>,<?php echo $location["longitude"]; ?>" 
                               target="_blank" class="btn">
                                🗺️ Google Maps
                            </a>
                            <?php if (!empty($location["photo"]) && file_exists($location["photo"])): ?>
                                <a href="<?php echo htmlspecialchars($location["photo"]); ?>" target="_blank" class="btn">
                                    📷 Ver Foto
                                </a>
                            <?php endif; ?>
                            <button onclick="confirmDelete(\'<?php echo $location["id"]; ?>\', \'<?php echo htmlspecialchars($location["name"]); ?>\')" 
                                    class="btn btn-danger">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <h3>📍 No se encontraron ubicaciones</h3>
                    <?php if (!empty($search)): ?>
                        <p>Intenta ajustar los filtros o <a href="gestion.php">ver todas las ubicaciones</a></p>
                    <?php else: ?>
                        <p>Aún no has guardado ninguna ubicación</p>
                        <a href="index.php" class="btn">Guardar Primera Ubicación</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>⚠️ Confirmar Eliminación</h3>
            <p>¿Estás seguro de que quieres eliminar la ubicación "<span id="locationName"></span>"?</p>
            <div style="margin-top: 20px;">
                <button onclick="closeModal()" class="btn">Cancelar</button>
                <button onclick="deleteLocation()" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
    
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_location" value="1">
        <input type="hidden" name="location_id" id="deleteLocationId">
    </form>
    
    <script>
        let locationToDelete = "";
        
        function confirmDelete(locationId, locationName) {
            locationToDelete = locationId;
            document.getElementById("locationName").textContent = locationName;
            document.getElementById("deleteModal").style.display = "block";
        }
        
        function closeModal() {
            document.getElementById("deleteModal").style.display = "none";
        }
        
        function deleteLocation() {
            document.getElementById("deleteLocationId").value = locationToDelete;
            document.getElementById("deleteForm").submit();
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById("deleteModal");
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>';
}

function createAdminFile() {
    return '<?php
require_once "auth.php";
requireAdmin();

$currentUser = getCurrentUser();
$allUsers = getAllUsers();
$allLocations = getUserLocations("all");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_user_status"])) {
        $userId = $_POST["user_id"];
        $status = $_POST["status"];
        
        if (updateUserStatus($userId, $status)) {
            $message = "✅ Estado de usuario actualizado correctamente";
        } else {
            $message = "❌ Error al actualizar estado del usuario";
        }
        $allUsers = getAllUsers();
    }
}

$stats = [
    "total_users" => count($allUsers),
    "active_users" => count(array_filter($allUsers, function($u) { return $u["status"] === "active"; })),
    "admin_users" => count(array_filter($allUsers, function($u) { return $u["role"] === "admin"; })),
    "total_locations" => count($allLocations),
    "locations_today" => count(array_filter($allLocations, function($l) { return date("Y-m-d", strtotime($l["timestamp"])) === date("Y-m-d"); }))
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema GPS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #6f42c1 100%);
            color: white;
            padding: 30px;
        }
        .nav {
            background: #f8f9fa;
            padding: 0;
            display: flex;
            overflow-x: auto;
        }
        .nav a {
            padding: 15px 20px;
            text-decoration: none;
            color: #495057;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .nav a:hover, .nav a.active {
            background: white;
            border-bottom-color: #dc3545;
            color: #dc3545;
        }
        .main-content { padding: 30px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #dc3545;
        }
        .stat-card h3 {
            color: #dc3545;
            font-size: 32px;
            margin-bottom: 5px;
        }
        .section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-blocked { background: #f8d7da; color: #721c24; }
        .role-admin { background: #f8d7da; color: #721c24; }
        .role-user { background: #d1ecf1; color: #0c5460; }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Panel de Administración</h1>
            <p>Gestión completa del sistema</p>
        </div>
        
        <div class="nav">
            <a href="index.php">Inicio</a>
            <a href="gestion.php">Mis Ubicaciones</a>
            <a href="admin.php" class="active">Administración</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
        
        <div class="main-content">
            <?php if ($message): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo $stats["total_users"]; ?></h3>
                    <p>Total usuarios</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["active_users"]; ?></h3>
                    <p>Usuarios activos</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["total_locations"]; ?></h3>
                    <p>Total ubicaciones</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["locations_today"]; ?></h3>
                    <p>Ubicaciones hoy</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats["admin_users"]; ?></h3>
                    <p>Administradores</p>
                </div>
            </div>
            
            <div class="section">
                <h2>👥 Gestión de Usuarios</h2>
                <div style="margin-bottom: 20px;">
                    <a href="export.php?all=true" class="btn btn-success">📊 Exportar Todas las Ubicaciones</a>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Ubicaciones</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $user): ?>
                            <?php
                            $userLocations = array_filter($allLocations, function($loc) use ($user) {
                                return $loc["user_id"] === $user["id"];
                            });
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user["username"]); ?></strong></td>
                                <td><?php echo htmlspecialchars($user["name"]); ?></td>
                                <td>
                                    <span class="status-badge role-<?php echo $user["role"]; ?>">
                                        <?php echo ucfirst($user["role"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user["status"]; ?>">
                                        <?php echo ucfirst($user["status"]); ?>
                                    </span>
                                </td>
                                <td><?php echo count($userLocations); ?></td>
                                <td><?php echo date("d/m/Y", strtotime($user["created"])); ?></td>
                                <td>
                                    <?php if ($user["status"] === "active"): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                            <input type="hidden" name="status" value="blocked">
                                            <button type="submit" name="update_user_status" class="btn btn-warning">Bloquear</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" name="update_user_status" class="btn btn-success">Activar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>📍 Últimas Ubicaciones del Sistema</h2>
                <?php if (count($allLocations) > 0): ?>
                    <?php foreach (array_slice($allLocations, 0, 10) as $location): ?>
                        <div style="background: white; border: 1px solid #e9ecef; border-radius: 10px; padding: 15px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="color: #dc3545; margin-bottom: 5px;"><?php echo htmlspecialchars($location["name"]); ?></h4>
                                    <p style="color: #6c757d; font-size: 14px;">
                                        👤 <strong><?php echo htmlspecialchars($location["username"]); ?></strong> - 
                                        📅 <?php echo date("d/m/Y H:i:s", strtotime($location["timestamp"])); ?>
                                    </p>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $location["latitude"]; ?>,<?php echo $location["longitude"]; ?>" 
                                   target="_blank" class="btn btn-success">
                                    🗺️ Ver en Maps
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px;">
                        No hay ubicaciones registradas en el sistema
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Ultra-Completo - Sistema GPS</title>
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
        .body { padding: 40px; }
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
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
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .highlight {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #333;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador Ultra-Completo del Sistema GPS</h1>
            <p>¡Instalación completa en un solo clic!</p>
        </div>

        <div class="body">
            <?php if (!isset($_POST['install']) && !isset($_POST['force_install'])): ?>
                
                <div class="highlight">
                    🎉 ¡NUEVO! Ahora incluye TODOS los archivos automáticamente<br>
                    ¡No necesitas crear nada manualmente!
                </div>
                
                <h2>Sistema GPS Completo</h2>
                <p>Este instalador creará automáticamente todo el sistema GPS sin necesidad de archivos adicionales.</p>
                
                <div class="info">
                    <h3>📋 Se instalarán automáticamente:</h3>
                    <div class="file-list">
                        <div class="file-item">📄 login.php - Sistema completo de autenticación</div>
                        <div class="file-item">📄 index.php - Página principal con GPS</div>
                        <div class="file-item">📄 gestion.php - Gestión completa de ubicaciones</div>
                        <div class="file-item">📄 admin.php - Panel de administración</div>
                        <div class="file-item">📄 auth.php - Sistema de autenticación</div>
                        <div class="file-item">📄 logout.php - Cerrar sesión</div>
                        <div class="file-item">📄 save_location.php - Guardar ubicaciones</div>
                        <div class="file-item">📄 export.php - Exportar datos CSV</div>
                        <div class="file-item">📄 .htaccess - Configuración de seguridad</div>
                        <div class="file-item">📄 users.json - Base de datos usuarios</div>
                        <div class="file-item">📄 locations.json - Base de datos ubicaciones</div>
                        <div class="file-item">📁 photos/ - Directorio para fotos</div>
                        <div class="file-item">📁 backups/ - Directorio para respaldos</div>
                    </div>
                </div>
                
                <div class="info">
                    <h3>🌟 Características Completas:</h3>
                    <div class="features">
                        <div class="feature">
                            <h4>🔐 Autenticación Completa</h4>
                            <p>Login, registro, roles de usuario y administrador</p>
                        </div>
                        <div class="feature">
                            <h4>📍 GPS de Alta Precisión</h4>
                            <p>Captura automática de coordenadas con medición de precisión</p>
                        </div>
                        <div class="feature">
                            <h4>📷 Gestión de Fotos</h4>
                            <p>Subida y asociación de imágenes a ubicaciones</p>
                        </div>
                        <div class="feature">
                            <h4>🗺️ Integración Google Maps</h4>
                            <p>Visualización directa de ubicaciones en mapas</p>
                        </div>
                        <div class="feature">
                            <h4>📊 Exportación Avanzada</h4>
                            <p>Descarga completa de datos en formato CSV</p>
                        </div>
                        <div class="feature">
                            <h4>⚙️ Panel de Administración</h4>
                            <p>Gestión completa de usuarios y sistema</p>
                        </div>
                        <div class="feature">
                            <h4>📱 100% Responsive</h4>
                            <p>Optimizado para móviles, tablets y desktop</p>
                        </div>
                        <div class="feature">
                            <h4>🔍 Búsqueda y Filtros</h4>
                            <p>Sistema completo de filtrado de ubicaciones</p>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <form method="POST">
                        <button type="submit" name="install" class="btn">
                            🚀 Instalar Sistema Completo Ahora
                        </button>
                    </form>
                    <p style="margin-top: 15px; color: #6c757d;">
                        <small>La instalación tomará solo unos segundos</small>
                    </p>
                </div>
                
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
                        <h3>🎉 ¡Instalación Ultra-Completa Exitosa!</h3>
                        <p>El sistema GPS se ha instalado correctamente con TODAS las funcionalidades.</p>
                        
                        <h4>✅ Sistema 100% Funcional:</h4>
                        <ul style="margin: 15px 0; padding-left: 20px;">
                            <li>✅ Sistema de autenticación completo</li>
                            <li>✅ Captura de GPS de alta precisión</li>
                            <li>✅ Gestión completa de ubicaciones</li>
                            <li>✅ Panel de administración funcional</li>
                            <li>✅ Exportación de datos CSV</li>
                            <li>✅ Subida y gestión de fotos</li>
                            <li>✅ Integración con Google Maps</li>
                            <li>✅ Diseño responsive para móviles</li>
                        </ul>
                        
                        <h4>🎯 Siguientes pasos:</h4>
                        <ol style="margin: 15px 0; padding-left: 20px;">
                            <li><strong>Accede al sistema</strong> haciendo clic en el botón de abajo</li>
                            <li><strong>Regístrate</strong> - El primer usuario será automáticamente administrador</li>
                            <li><strong>¡Comienza a usar!</strong> - Guarda tu primera ubicación GPS</li>
                        </ol>
                        
                        <div class="file-list">
                            <div class="file-item" style="background: #d4edda;">✅ login.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ index.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ gestion.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ admin.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ auth.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ logout.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ save_location.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ export.php</div>
                            <div class="file-item" style="background: #d4edda;">✅ .htaccess</div>
                            <div class="file-item" style="background: #d4edda;">✅ users.json</div>
                            <div class="file-item" style="background: #d4edda;">✅ locations.json</div>
                            <div class="file-item" style="background: #d4edda;">✅ photos/</div>
                            <div class="file-item" style="background: #d4edda;">✅ backups/</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="login.php" class="btn btn-primary">
                            🌍 Acceder al Sistema GPS Completo
                        </a>
                        
                        <p style="margin-top: 20px; color: #6c757d;">
                            <small>🗑️ Puedes eliminar este archivo instalador después de acceder al sistema</small>
                        </p>
                    </div>
                    
                <?php else: ?>
                    <div class="info">
                        <h3>⚠️ Instalación Incompleta</h3>
                        <p>Se produjeron algunos errores. Revisa los permisos del directorio y vuelve a intentarlo.</p>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <form method="POST">
                                <button type="submit" name="install" class="btn">
                                    🔄 Reintentar Instalación
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
