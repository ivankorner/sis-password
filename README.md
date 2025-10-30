# 🔐 SIS Password - Sistema de Gestión de Usuarios y Contraseñas

Sistema web completo para administrar usuarios, contraseñas y acceso remoto de PCs en un edificio.

**Desarrollado con:** PHP 7.4+ | Bootstrap 5 | MySQL 5.7+ | Apache 2.4+

---

## ✨ Características

- 🔓 **Autenticación segura** con sesiones PHP y contraseñas hasheadas
- 💻 **Gestión de PCs** - crear, editar, eliminar computadoras
- 👥 **Múltiples usuarios por PC** - cada uno con sus datos completos
- 🔑 **Datos de acceso remoto** - TeamViewer, AnyDesk, etc.
- 🎨 **Interfaz Bootstrap 5** - moderna, responsiva y amigable
- 📱 **Funciona en cualquier navegador** - Chrome, Firefox, Safari, Edge
- ⚡ **Sin dependencias externas** - solo PHP, MySQL y Apache
- 🛡️ **Seguridad** - Prepared statements, sin SQL injection
- 🌐 **Compatible DonWeb** - hosting compartido

---

## 📊 Campos de Información por Usuario

Cada usuario registrado en el sistema contiene:

| Campo | Descripción |
|-------|-------------|
| **Oficina** | Ubicación del PC (Piso, Oficina, etc) |
| **PC** | Nombre/identificador del equipo |
| **Operario** | Nombre de la persona que lo usa |
| **Nombre Usuario** | Usuario para acceder al PC (Windows) |
| **Password** | Contraseña del PC |
| **ID Control Remoto** | ID TeamViewer, AnyDesk, etc |
| **Password Control Remoto** | Contraseña para acceso remoto |
| **Notas** | Información adicional (opcional) |

---

## 🚀 Inicio Rápido

### 1️⃣ Instalación Local (XAMPP)

```bash
# 1. Crear base de datos
mysql -u root -p < database/schema.sql

# 2. Acceder por navegador
http://localhost/sis-password-php/public/login.php

# 3. Login con:
# Email: admin@test.com
# Contraseña: Admin123!
```

**Ver más:** [QUICKSTART.md](QUICKSTART.md)

### 2️⃣ Despliegue en DonWeb

```bash
# 1. Crear BD en panel DonWeb
# 2. Subir archivos por FTP
# 3. Actualizar config/config.php
# 4. Acceder por navegador
# https://tudominio.donweb.com/sis-password/public/
```

**Ver más:** [DEPLOYMENT_DONWEB.md](DEPLOYMENT_DONWEB.md)

---

## 📁 Estructura del Proyecto

```
sis-password-php/
│
├── 📂 config/
│   ├── config.php              # Configuración global
│   └── database.php            # Conexión PDO a MySQL
│
├── 📂 api/
│   ├── auth.php               # Endpoints: login, logout, registrar
│   ├── pcs.php                # API REST: CRUD de PCs
│   └── usuarios.php           # API REST: CRUD de Usuarios
│
├── 📂 public/
│   ├── login.php              # Página de login
│   ├── dashboard.php          # Panel principal (con JS interactivo)
│   ├── .htaccess              # Configuración Apache
│   └── README.md
│
├── 📂 database/
│   └── schema.sql             # Script para crear BD
│
├── 📄 QUICKSTART.md           # Guía rápida (5 minutos)
├── 📄 DEVELOPMENT.md          # Guía de desarrollo
├── 📄 DEPLOYMENT_DONWEB.md    # Guía despliegue DonWeb
└── 📄 README.md               # Este archivo
```

---

## 🔧 Uso del Sistema

### Flujo Principal

```
LOGIN
  ↓
INICIO (Ver estadísticas)
  ├─ Gestionar PCs
  │  ├─ Crear nuevo PC
  │  ├─ Editar PC
  │  └─ Eliminar PC
  │
  └─ Gestionar Usuarios
     ├─ Crear usuario en un PC
     ├─ Editar usuario
     └─ Eliminar usuario
```

### Ejemplo Práctico

**Crear usuario en PC:**

1. Login con `admin@test.com` / `Admin123!`
2. Click en **"Gestionar PCs"**
3. Click en **"Nuevo PC"**
4. Completa:
   - Oficina: `Piso 1 - Oficina 101`
   - Nombre PC: `PC-ADMIN-01`
5. Click **"Guardar"**
6. Click en **"Gestionar Usuarios"**
7. Click en **"Nuevo Usuario"**
8. Completa:
   - PC: Selecciona `Piso 1 - Oficina 101 - PC-ADMIN-01`
   - Operario: `Juan García`
   - Nombre Usuario: `jgarcia`
   - Contraseña: `Pwd123456`
   - (Opcional) ID Control Remoto: `123456789`
   - (Opcional) Contraseña Control Remoto: `remotepwd123`
9. Click **"Guardar"**

¡Listo! El usuario aparecerá en la tabla.

---

## 🔐 Seguridad

### Implementado

✅ **Autenticación:** Sesiones PHP seguras  
✅ **Hashing:** Contraseñas de admin con bcrypt  
✅ **SQL Injection:** Prepared statements en todas las queries  
✅ **Headers:** Protección contra XSS y clickjacking  
✅ **Transacciones:** Integridad referencial en eliminaciones  

### Notas

- La contraseña del administrador está hasheada (no se puede ver)
- Las contraseñas de usuarios se guardan en texto plano (por diseño, para poder visualizarlas)
- Usa HTTPS en producción para proteger datos en tránsito
- Cambia la contraseña por defecto después de instalar

---

## 📚 Documentación

| Archivo | Contenido |
|---------|----------|
| [QUICKSTART.md](QUICKSTART.md) | Inicio rápido en 5 minutos |
| [DEVELOPMENT.md](DEVELOPMENT.md) | Guía para desarrolladores |
| [DEPLOYMENT_DONWEB.md](DEPLOYMENT_DONWEB.md) | Despliegue en DonWeb paso a paso |
| [public/README.md](public/README.md) | Notas de la carpeta pública |

---

## 💾 Base de Datos

### Tablas

**administradores** - Usuarios admin del sistema
```
id, email, password (hasheada), nombre, activo, fecha_creacion, fecha_actualizacion
```

**pcs** - Computadoras registradas
```
id, oficina, nombre, descripcion, fecha_creacion, fecha_actualizacion
```

**usuarios_pc** - Usuarios por PC
```
id, pc_id, operario, nombre_usuario, password, 
id_control_remoto, password_control_remoto, notas, fecha_creacion, fecha_actualizacion
```

### Crear usuario de prueba

```bash
mysql -u root -p sis_password

# Ver admin por defecto
SELECT * FROM administradores;

# Crear nuevo admin (la contraseña debe estar hasheada)
INSERT INTO administradores (email, password, nombre) VALUES 
('nuevo@test.com', '$2y$10$...hash...', 'Nuevo Admin');
```

Para generar un hash de contraseña:
```php
<?php
echo password_hash('MiPassword123', PASSWORD_BCRYPT);
// Copia el resultado y úsalo en el INSERT
?>
```

---

## 🐛 Solucionar Problemas

### Problema: "Error de conexión a base de datos"

**Soluciones:**
1. Verifica que MySQL está corriendo
2. Abre `config/config.php` y valida:
   - DB_HOST
   - DB_USER
   - DB_PASSWORD
   - DB_NAME
3. Intenta conectarte manualmente:
   ```bash
   mysql -h DB_HOST -u DB_USER -p DB_NAME
   ```

### Problema: "Página no encontrada (404)"

**Soluciones:**
1. Verifica que la carpeta está en `htdocs/sis-password-php/`
2. Asegúrate que Apache está corriendo
3. Intenta acceder a `http://localhost/sis-password-php/public/login.php`
4. Revisa .htaccess en `public/.htaccess`

### Problema: "No puedo hacer login"

**Soluciones:**
1. Abre phpMyAdmin y verifica tabla `administradores`
2. Asegúrate que hay al menos un registro
3. Re-importa `database/schema.sql` si es necesario
4. Verifica que la contraseña es correcta (sensible a mayúsculas)

### Problema: Tabla de usuarios vacía

**Soluciones:**
1. Primero debes crear un PC
2. Los usuarios se crean PARA un PC específico
3. No puedes crear un usuario sin un PC

---

## 📞 Soporte y Contacto

### Requisitos del Sistema

- **PHP:** 7.4 o superior
- **MySQL:** 5.7 o superior
- **Apache:** 2.4 o superior
- **Navegador:** Cualquiera moderno (Chrome, Firefox, Safari, Edge)

### Alojamiento Recomendado

- **Local:** XAMPP (macOS, Linux, Windows)
- **Producción:** DonWeb (hosting compartido)

### Hosting DonWeb

- **Panel:** https://panel.donweb.com
- **Email:** soporte@donweb.com
- **Tel:** +54 11 xxxx-xxxx
- **Wiki:** https://wiki.donweb.com

---

## 📄 Licencia

Este proyecto está disponible para uso educativo y comercial.

---

## 📝 Changelog

**v1.0.0 - Octubre 2025**
- ✅ Sistema completo en PHP + Bootstrap
- ✅ Autenticación con sesiones
- ✅ CRUD de PCs y Usuarios
- ✅ Interfaz responsiva
- ✅ Documentación completa
- ✅ Compatible DonWeb

---

**¡Listo para usar! 🎉**

Comienza con [QUICKSTART.md](QUICKSTART.md) o ve directamente a [DEPLOYMENT_DONWEB.md](DEPLOYMENT_DONWEB.md) si usas DonWeb.
