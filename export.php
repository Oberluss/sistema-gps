<?php
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
?>