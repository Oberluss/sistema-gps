# 🌍 Sistema GPS - Geolocalización y Gestión de Ubicaciones

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![GitHub release](https://img.shields.io/github/release/tu-usuario/sistema-gps.svg)](https://GitHub.com/tu-usuario/sistema-gps/releases/)

Sistema completo de geolocalización GPS con gestión de ubicaciones, autenticación de usuarios y panel de administración. Diseñado para ser fácil de instalar y usar.

## 🚀 Instalación Rápida

### Método 1: Instalación Automática desde GitHub (Recomendado)

1. **Descarga solo el archivo setup.php**:
   ```bash
   wget https://raw.githubusercontent.com/tu-usuario/sistema-gps/main/setup.php
   ```

2. **Sube setup.php a tu servidor** y accede desde el navegador:
   ```
   https://tu-sitio.com/setup.php
   ```

3. **Haz clic en "Descargar e Instalar desde GitHub"** y ¡listo!

### Método 2: Instalación Manual

1. **Clona el repositorio**:
   ```bash
   git clone https://github.com/tu-usuario/sistema-gps.git
   cd sistema-gps
   ```

2. **Copia los archivos** de la carpeta `src/` a tu directorio web

3. **Crea los directorios necesarios**:
   ```bash
   mkdir photos backups logs
   chmod 755 photos backups logs
   ```

4. **Crea los archivos de configuración**:
   ```bash
   echo '[]' > users.json
   echo '[]' > locations.json
   chmod 666 users.json locations.json
   ```

## 🌟 Características

### 🔐 Sistema de Autenticación
- ✅ Registro e inicio de sesión seguro
- ✅ Roles de usuario (Usuario/Administrador)
- ✅ Gestión de estados de cuenta (activo/bloqueado)
- ✅ Sesiones seguras con hash de contraseñas

### 📍 Geolocalización GPS
- ✅ Captura automática de coordenadas GPS
- ✅ Medición de precisión en metros
- ✅ Soporte para navegadores modernos
- ✅ Manejo de errores de geolocalización

### 📷 Gestión de Imágenes
- ✅ Subida de fotos para cada ubicación
- ✅ Soporte para múltiples formatos (JPG, PNG, GIF, WebP)
- ✅ Nombres únicos para evitar conflictos
- ✅ Visualización y descarga de imágenes

### 🗺️ Integración con Mapas
- ✅ Enlaces directos a Google Maps
- ✅ Visualización de ubicaciones en mapas
- ✅ Coordenadas precisas con 6 decimales

### 📊 Exportación de Datos
- ✅ Exportación completa a CSV
- ✅ Incluye coordenadas, comentarios y enlaces
- ✅ Exportación por usuario o completa (admin)
- ✅ Compatible con Excel

### ⚙️ Panel de Administración
- ✅ Gestión completa de usuarios
- ✅ Estadísticas del sistema en tiempo real
- ✅ Activar/bloquear cuentas de usuario
- ✅ Visualización de todas las ubicaciones

### 📱 Diseño Responsive
- ✅ Optimizado para móviles y tablets
- ✅ Interfaz moderna y fácil de usar
- ✅ Compatible con todos los navegadores
- ✅ Modo oscuro automático

## 🛠️ Requisitos del Sistema

### Servidor Web
- **PHP:** 7.4 o superior
- **Servidor web:** Apache, Nginx, o similar
- **Extensiones PHP requeridas:**
  - `json` (incluida por defecto)
  - `fileinfo` (para validación de archivos)
  - `session` (incluida por defecto)

### Permisos de Archivos
```bash
# Archivos de datos (lectura/escritura)
chmod 666 users.json locations.json

# Directorios de contenido (lectura/escritura/ejecución)
chmod 755 photos/ backups/ logs/

# Archivos PHP (solo lectura)
chmod 644 *.php
```

### Navegadores Compatibles
- ✅ Chrome/Chromium 50+
- ✅ Firefox 45+
- ✅ Safari 10+
- ✅ Edge 14+
- ✅ Navegadores móviles modernos

## 📖 Guía de Uso

### Primera Configuración

1. **Accede al sistema** después de la instalación
2. **Regístrate** - El primer usuario será automáticamente administrador
3. **Permite el acceso a la ubicación** cuando el navegador lo solicite
4. **Guarda tu primera ubicación GPS**

### Uso Básico

#### Guardar Ubicación
1. En la página principal, haz clic en "📡 Obtener Ubicación Actual"
2. Espera a que se detecte tu ubicación GPS
3. Completa el nombre y comentario (opcional)
4. Opcionalmente sube una foto
5. Haz clic en "💾 Guardar Ubicación"

#### Gestionar Ubicaciones
1. Ve a "Mis Ubicaciones" en el menú
2. Usa los filtros para buscar ubicaciones específicas
3. Haz clic en "🗺️ Google Maps" para ver en el mapa
4. Usa "🗑️ Eliminar" para borrar ubicaciones

#### Exportar Datos
1. En "Mis Ubicaciones", haz clic en "📊 Exportar CSV"
2. Se descargará un archivo con todas tus ubicaciones
3. Abre con Excel o cualquier programa de hojas de cálculo

### Administración

#### Gestión de Usuarios
1. Accede al "Panel de Administración"
2. Ve la lista completa de usuarios registrados
3. Usa "Bloquear" para desactivar cuentas
4. Usa "Activar" para reactivar cuentas

#### Estadísticas del Sistema
- **Dashboard principal:** Estadísticas generales en tiempo real
- **Usuarios totales:** Número de cuentas registradas
- **Ubicaciones del día:** Actividad diaria del sistema
- **Exportación masiva:** Descarga todos los datos del sistema

## 🔧 Configuración Avanzada

### Personalización del Sistema

#### Cambiar Configuraciones
Edita el archivo `config.php` para personalizar:

```php
// Nombre de la aplicación
define("APP_NAME", "Mi Sistema GPS");

// Límites de archivos
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// Configuración de sesiones
ini_set('session.gc_maxlifetime', 7200); // 2 horas
```

#### Personalizar Estilos
Los estilos CSS están incluidos en cada archivo PHP. Para personalizar:

1. Busca la sección `<style>` en el archivo correspondiente
2. Modifica los colores, fuentes o diseño según tus preferencias
3. Mantén las clases responsive para compatibilidad móvil

### Seguridad

#### Configuración de Servidor

**Apache (.htaccess incluido):**
```apache
# Proteger archivos JSON
<FilesMatch "\.(json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevenir listado de directorios
Options -Indexes
```

**Nginx:**
```nginx
# Bloquear acceso a archivos JSON
location ~* \.json$ {
    deny all;
    return 404;
}

# Bloquear listado de directorios
autoindex off;
```

#### Backup Automático
```bash
# Crear script de backup (backup.sh)
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf "backups/backup_$DATE.tar.gz" *.json photos/ logs/

# Ejecutar diariamente con cron
0 2 * * * /path/to/backup.sh
```

## 🐛 Solución de Problemas

### Problemas Comunes

#### "Error al obtener ubicación GPS"
- **Causa:** Navegador sin soporte o permisos denegados
- **Solución:** 
  1. Verifica que el sitio use HTTPS (requerido para GPS)
  2. Permite el acceso a la ubicación en el navegador
  3. Prueba con un navegador diferente

#### "Error al subir foto"
- **Causa:** Archivo demasiado grande o formato no soportado
- **Solución:**
  1. Verifica que el archivo sea menor a 10MB
  2. Usa formatos: JPG, PNG, GIF, WebP
  3. Aumenta `upload_max_filesize` en PHP si es necesario

#### "No se pueden guardar datos"
- **Causa:** Permisos de archivos incorrectos
- **Solución:**
  ```bash
  chmod 666 users.json locations.json
  chmod 755 photos/ backups/
  ```

#### "Setup no puede descargar archivos"
- **Causa:** Conexión a Internet o repositorio inaccesible
- **Solución:**
  1. Verifica la conexión a Internet
  2. Comprueba que el repositorio sea público
  3. Verifica la configuración del repositorio en setup.php

### Logs y Depuración

#### Activar Logs de PHP
```php
// En config.php
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');
```

#### Verificar Logs
```bash
# Ver últimos errores
tail -f logs/php_errors.log

# Ver estadísticas de uso
ls -la photos/ | wc -l  # Número de fotos
wc -l users.json        # Número de usuarios
```

## 🤝 Contribución

### Cómo Contribuir

1. **Fork** el repositorio
2. **Crea** una rama para tu característica (`git checkout -b feature/nueva-caracteristica`)
3. **Commit** tus cambios (`git commit -am 'Añadir nueva característica'`)
4. **Push** a la rama (`git push origin feature/nueva-caracteristica`)
5. **Abre** un Pull Request

### Reportar Bugs

Usa el [sistema de issues de GitHub](https://github.com/tu-usuario/sistema-gps/issues) para reportar bugs. Incluye:

- 📱 **Dispositivo y navegador** usado
- 🔍 **Pasos para reproducir** el problema
- 📋 **Comportamiento esperado** vs actual
- 📊 **Logs de error** si están disponibles

### Roadmap

#### Próximas Características
- [ ] 🗺️ Mapa interactivo en la interfaz
- [ ] 📱 PWA (Progressive Web App) para instalación móvil
- [ ] 🔄 Sincronización automática con servicios en la nube
- [ ] 📈 Gráficos avanzados de estadísticas
- [ ] 🌐 Soporte multiidioma
- [ ] 🔐 Autenticación de dos factores (2FA)
- [ ] 📤 API REST para integración externa

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## 👨‍💻 Autor

**Tu Nombre**
- GitHub: [@tu-usuario](https://github.com/tu-usuario)
- Email: tu-email@example.com
- Web: [tu-sitio.com](https://tu-sitio.com)

## 🙏 Agradecimientos

- 🗺️ **Google Maps API** por la integración de mapas
- 📱 **Geolocation API** del W3C por el estándar GPS
- 🎨 **Heroicons** por los iconos utilizados
- 🌐 **GitHub** por el hosting del repositorio

---

⭐ **¡Dale una estrella si te ha sido útil!** ⭐
