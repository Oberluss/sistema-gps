<?php
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
?>