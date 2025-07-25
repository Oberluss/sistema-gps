<?php
session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Leer usuarios
    $usersFile = 'users.json';
    $users = [];
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?: [];
    }
    
    // Buscar usuario
    $userFound = false;
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            if ($user['status'] === 'active') {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                header('Location: index.php');
                exit;
            } else {
                $message = 'Tu cuenta está pendiente de activación por un administrador.';
                $messageType = 'warning';
            }
            $userFound = true;
            break;
        }
    }
    
    if (!$userFound) {
        $message = 'Usuario o contraseña incorrectos.';
        $messageType = 'error';
    }
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    
    // Validaciones
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $message = 'Todos los campos son obligatorios.';
        $messageType = 'error';
    } elseif ($password !== $password2) {
        $message = 'Las contraseñas no coinciden.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El email no es válido.';
        $messageType = 'error';
    } else {
        // Leer usuarios existentes
        $usersFile = 'users.json';
        $users = [];
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
        }
        
        // Verificar si el usuario ya existe
        $exists = false;
        foreach ($users as $user) {
            if ($user['username'] === $username || $user['email'] === $email) {
                $exists = true;
                break;
            }
        }
        
        if ($exists) {
            $message = 'El usuario o email ya está registrado.';
            $messageType = 'error';
        } else {
            // Crear nuevo usuario
            $newUser = [
                'id' => uniqid('user_'),
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user', // Por defecto usuario normal
                'status' => 'pending', // Pendiente de activación
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Si es el primer usuario, hacerlo admin automáticamente
            if (count($users) === 0) {
                $newUser['role'] = 'admin';
                $newUser['status'] = 'active';
            }
            
            $users[] = $newUser;
            
            // Guardar usuarios
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            if (count($users) === 1) {
                $message = '¡Registro exitoso! Como eres el primer usuario, tu cuenta ya está activa y eres administrador.';
            } else {
                $message = '¡Registro exitoso! Tu cuenta está pendiente de activación por un administrador.';
            }
            $messageType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema GPS - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .auth-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .auth-body {
            padding: 30px;
        }

        .form-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #999;
            transition: all 0.3s;
            position: relative;
        }

        .tab-btn.active {
            color: #667eea;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }

        .form-panel {
            display: none;
        }

        .form-panel.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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

        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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

        .auth-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>📍 Sistema GPS</h1>
            <p>Gestión de ubicaciones personales</p>
        </div>
        
        <div class="auth-body">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-tabs">
                <button class="tab-btn active" onclick="showTab('login')">Iniciar Sesión</button>
                <button class="tab-btn" onclick="showTab('register')">Registrarse</button>
            </div>
            
            <!-- Panel de Login -->
            <div class="form-panel active" id="loginPanel">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn-submit">
                        Iniciar Sesión
                    </button>
                </form>
            </div>
            
            <!-- Panel de Registro -->
            <div class="form-panel" id="registerPanel">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-input" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmar contraseña</label>
                        <input type="password" name="password2" class="form-input" required minlength="6">
                    </div>
                    
                    <button type="submit" name="register" class="btn-submit">
                        Registrarse
                    </button>
                </form>
            </div>
        </div>
        
        <div class="auth-footer">
            <p>Sistema seguro de gestión GPS</p>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            // Cambiar tabs activas
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Cambiar paneles
            document.querySelectorAll('.form-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            if (tab === 'login') {
                document.getElementById('loginPanel').classList.add('active');
            } else {
                document.getElementById('registerPanel').classList.add('active');
            }
        }
        
        // Auto-ocultar mensajes
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 300);
            });
        }, 5000);
    </script>
</body>
</html>