<?php
require_once 'auth.php';
requireAdmin(); // Solo admins pueden acceder

$message = '';
$messageType = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['activate_user'])) {
        $userId = $_POST['user_id'];
        if (updateUserStatus($userId, 'active')) {
            $message = 'Usuario activado correctamente.';
            $messageType = 'success';
        }
    } elseif (isset($_POST['deactivate_user'])) {
        $userId = $_POST['user_id'];
        if (updateUserStatus($userId, 'inactive')) {
            $message = 'Usuario desactivado correctamente.';
            $messageType = 'success';
        }
    } elseif (isset($_POST['make_admin'])) {
        $userId = $_POST['user_id'];
        if (updateUserRole($userId, 'admin')) {
            $message = 'Usuario promovido a administrador.';
            $messageType = 'success';
        }
    } elseif (isset($_POST['make_user'])) {
        $userId = $_POST['user_id'];
        if (updateUserRole($userId, 'user')) {
            $message = 'Usuario degradado a usuario normal.';
            $messageType = 'success';
        }
    } elseif (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        // No permitir auto-eliminación
        if ($userId === $_SESSION['user_id']) {
            $message = 'No puedes eliminar tu propia cuenta.';
            $messageType = 'error';
        } else {
            // Eliminar usuario
            $usersFile = 'users.json';
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
            $users = array_filter($users, function($user) use ($userId) {
                return $user['id'] !== $userId;
            });
            $users = array_values($users);
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Eliminar ubicaciones del usuario
            $locationsFile = 'locations.json';
            if (file_exists($locationsFile)) {
                $locations = json_decode(file_get_contents($locationsFile), true) ?: [];
                $locations = array_filter($locations, function($loc) use ($userId) {
                    // Eliminar foto si existe
                    if (isset($loc['user_id']) && $loc['user_id'] === $userId && !empty($loc['photo']) && file_exists($loc['photo'])) {
                        unlink($loc['photo']);
                    }
                    return !isset($loc['user_id']) || $loc['user_id'] !== $userId;
                });
                $locations = array_values($locations);
                file_put_contents($locationsFile, json_encode($locations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            
            $message = 'Usuario y sus datos eliminados correctamente.';
            $messageType = 'success';
        }
    }
}

// Obtener todos los usuarios
$users = getAllUsers();

// Obtener estadísticas generales
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$pendingUsers = count(array_filter($users, fn($u) => $u['status'] === 'pending'));
$adminUsers = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema GPS</title>
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

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            text-align: center;
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .users-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .user-email {
            font-size: 12px;
            color: #999;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-admin {
            background: #e7e3ff;
            color: #5a32a3;
        }

        .badge-user {
            background: #e3f2fd;
            color: #1976d2;
        }

        .actions-cell {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
            color: #666;
        }

        .btn-small:hover {
            background: #e9ecef;
        }

        .btn-activate {
            background: #d4edda;
            color: #155724;
        }

        .btn-deactivate {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .locations-count {
            font-size: 12px;
            color: #666;
        }

        @media (max-width: 768px) {
            .users-table {
                font-size: 14px;
            }

            .actions-cell {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🛡️ Panel de Administración</h1>
            <div class="nav-links">
                <a href="index.php" class="nav-link">📍 Guardar</a>
                <a href="gestion.php" class="nav-link">📋 Gestionar</a>
                <a href="logout.php" class="nav-link">🚪 Salir</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Usuarios Totales</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $activeUsers; ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?php echo $pendingUsers; ?></div>
                <div class="stat-label">Pendientes de Activación</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👑</div>
                <div class="stat-number"><?php echo $adminUsers; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2 class="section-title">Gestión de Usuarios</h2>
            </div>
            
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Rol</th>
                        <th>Ubicaciones</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php 
                        $userStats = getUserStats($user['id']);
                        $isCurrentUser = $user['id'] === $_SESSION['user_id'];
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                            <?php if ($isCurrentUser): ?>
                                                <span style="color: #667eea; font-size: 12px;">(Tú)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge badge-active">Activo</span>
                                <?php elseif ($user['status'] === 'pending'): ?>
                                    <span class="badge badge-pending">Pendiente</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">Usuario</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="locations-count">
                                    📍 <?php echo $userStats['total']; ?> total<br>
                                    📸 <?php echo $userStats['with_photos']; ?> con foto
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="actions-cell">
                                    <?php if (!$isCurrentUser): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            
                                            <?php if ($user['status'] === 'pending' || $user['status'] === 'inactive'): ?>
                                                <button type="submit" name="activate_user" class="btn-small btn-activate">Activar</button>
                                            <?php elseif ($user['status'] === 'active'): ?>
                                                <button type="submit" name="deactivate_user" class="btn-small btn-deactivate">Desactivar</button>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['role'] === 'user'): ?>
                                                <button type="submit" name="make_admin" class="btn-small">Hacer Admin</button>
                                            <?php else: ?>
                                                <button type="submit" name="make_user" class="btn-small">Quitar Admin</button>
                                            <?php endif; ?>
                                            
                                            <button type="submit" name="delete_user" class="btn-small btn-delete" 
                                                    onclick="return confirm('¿Eliminar usuario y todos sus datos?\n\nEsta acción no se puede deshacer.')">
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">No puedes modificar tu cuenta</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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