# 📥 Guía de Instalación - SIS Password

## 🖥️ Instalación Local (XAMPP macOS)

### Requisitos
- XAMPP instalado ([descargar](https://www.apachefriends.org))
- PHP 7.4+ y MySQL 5.7+
- Terminal (o cualquier cliente MySQL)

### Paso 1: Copiar Proyecto

```bash
# Ir a carpeta de XAMPP
cd /Applications/XAMPP/xamppfiles/htdocs

# Clonar o descargar el proyecto
git clone <repositorio-url> sis-password-php
# O descargar ZIP y extraer

# Entrar a la carpeta
cd sis-password-php
```

### Paso 2: Crear Base de Datos

#### Opción A: Script automático (recomendado)
```bash
# Dar permisos de ejecución
chmod +x setup.sh

# Ejecutar setup
./setup.sh

# Seguir las instrucciones en pantalla
```

#### Opción B: Manual
```bash
# Conectarse a MySQL
mysql -u root -p

# Si pide contraseña y la dejaste vacía en XAMPP, solo presiona Enter

# Ejecutar el script
source database/schema.sql;

# Salir
exit;
```

### Paso 3: Iniciar XAMPP

```bash
# En macOS
sudo /Applications/XAMPP/bin/xampp start

# O abrir la aplicación: /Applications/XAMPP/xamppmanager.app
# Y presionar "Start" en Apache y MySQL
```

### Paso 4: Acceder en Navegador

```
http://localhost/sis-password-php/public/login.php
```

O si copiaste a htdocs:
```
http://localhost/sis-password-php/
```

### Paso 5: Login

```
Email: admin@test.com
Contraseña: Admin123!
```

✅ ¡Listo! Sistema funcionando.

---

## 🌐 Instalación en DonWeb

### Requisitos
- Cuenta DonWeb activa
- Acceso FTP o SFTP
- Cliente FTP (Filezilla, WinSCP, Transmit, etc)

### Paso 1: Preparar en Local

```bash
# 1. Asegúrate que todo funciona en local primero
# 2. Haz backup de la BD (opcional)
mysqldump -u root sis_password > backup.sql
```

### Paso 2: Crear BD en DonWeb

1. Acceder a **https://panel.donweb.com**
2. Ir a **Bases de Datos → MySQL**
3. Click **"Crear Base de Datos"**
4. Nombre: `sis_password`
5. Copiar credenciales (host, usuario, contraseña)

### Paso 3: Importar Schema

#### Opción A: phpMyAdmin
1. Panel DonWeb → **Herramientas → phpMyAdmin**
2. Seleccionar BD `sis_password`
3. Click **"Importar"**
4. Seleccionar `database/schema.sql`
5. Click **"Ejecutar"**

#### Opción B: SSH
```bash
# Conectarse por SSH
ssh usuario@donweb.com

# Navegar a carpeta
cd public_html

# Importar
mysql -h host_mysql -u usuario -p sis_password < schema.sql
```

### Paso 4: Subir Archivos por FTP

1. Abrir Filezilla (u otro cliente FTP)
2. Conectar:
   - Host: `ftp.tudominio.donweb.com`
   - Usuario: tu usuario FTP
   - Contraseña: tu contraseña FTP
   - Puerto: 21
3. Navegar a `public_html`
4. Crear carpeta `sis-password`
5. Subir carpetas: `config`, `api`, `public`, `database`
6. Subir archivos: `index.php`, `.env.example`, etc

Estructura final en DonWeb:
```
public_html/
├── sis-password/
│   ├── config/
│   ├── api/
│   ├── public/
│   ├── database/
│   ├── index.php
│   └── [otros]
└── [otros archivos web]
```

### Paso 5: Configurar Credenciales

1. Descargar `config/config.php` por FTP
2. Editar localmente:
```php
define('DB_HOST', 'host_mysql_donweb');
define('DB_USER', 'usuario_mysql_donweb');
define('DB_PASS', 'contraseña_donweb');
define('DB_NAME', 'sis_password');
define('BASE_URL', '/sis-password/');
```

3. Subir archivo actualizado por FTP

### Paso 6: Acceder

```
https://tudominio.donweb.com/sis-password/public/login.php
```

Credenciales:
```
Email: admin@test.com
Contraseña: Admin123!
```

✅ ¡Sistema en producción!

---

## ⚙️ Configuración Avanzada

### Cambiar Contraseña de Admin

1. Acceder a phpMyAdmin (panel DonWeb o XAMPP)
2. Base de datos `sis_password` → Tabla `administradores`
3. Editar registro del admin
4. Campo `password` debe ser:
```
$2y$10$...hash_de_bcrypt...
```

Para generar hash (en terminal PHP):
```bash
php -r "echo password_hash('MiPassword123', PASSWORD_BCRYPT);"
```

### Crear Nuevo Admin

```sql
INSERT INTO administradores (email, password, nombre) VALUES 
('nuevo@test.com', '$2y$10$...hash...', 'Nuevo Admin');
```

### Cambiar Zona Horaria

En `config/config.php`:
```php
// Por defecto: America/Argentina/Buenos_Aires
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Otras opciones:
// 'America/New_York'
// 'Europe/Madrid'
// 'America/Mexico_City'
```

### Cambiar Tiempo de Sesión

En `config/config.php`:
```php
// Por defecto: 3600 segundos (1 hora)
define('SESSION_TIMEOUT', 3600);

// Ejemplos:
// 7200  = 2 horas
// 1800  = 30 minutos
// 86400 = 1 día
```

---

## 🐛 Troubleshooting

### Error: "XAMPP: Could not start MySQL"
```bash
# Verificar si puerto 3306 está en uso
lsof -i :3306

# Matar proceso
kill -9 <PID>

# O usar otro puerto en XAMPP config
```

### Error: "Access denied for user 'root'"
```bash
# XAMPP por defecto tiene password vacío
# Si configuraste contraseña:
mysql -u root -p
# Ingresar contraseña cuando pida
```

### Error: "Table doesn't exist"
```bash
# Base de datos no fue importada
# Ejecutar setup.sh nuevamente o:
mysql -u root -p < database/schema.sql
```

### Error: "Forbidden" en DonWeb
```bash
# Problema con .htaccess
# Revisar que .htaccess en public/ sea correcto
# O contactar soporte DonWeb
```

---

## 📋 Verificación Final

Checklist para confirmar instalación:

- [ ] Base de datos creada (tablas visibles en phpMyAdmin)
- [ ] Archivos subidos completos
- [ ] Archivo config.php tiene credenciales correctas
- [ ] Puedo acceder a login.php
- [ ] Login funciona (usuario/contraseña correctos)
- [ ] Dashboard carga sin errores
- [ ] Puedo crear un PC
- [ ] Puedo crear un usuario
- [ ] Puedo editar/eliminar

Si todos los items están ✓, ¡instalación completada!

---

## 📞 Ayuda

**Para XAMPP:**
- Documentación: https://www.apachefriends.org
- Soporte: Foros de XAMPP

**Para DonWeb:**
- Panel: https://panel.donweb.com
- Email: soporte@donweb.com
- Wiki: https://wiki.donweb.com

**Para este proyecto:**
- README.md - Información general
- DEVELOPMENT.md - Guía técnica
- DEPLOYMENT_DONWEB.md - Detalles de producción

---

**¡Éxito con la instalación! 🚀**

Si tienes problemas, consulta [QUICKSTART.md](QUICKSTART.md) o [DEVELOPMENT.md](DEVELOPMENT.md).
