<?php
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
?>