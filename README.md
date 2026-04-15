# Gestión de Activos - UNAP

Sistema para la gestión de inventariado y control de activos de la UNAP. El proyecto utiliza un backend desarrollado en Laravel y un frontend con Quasar Framework (Vue.js 3).

---

## Requisitos del Sistema

- PHP >= 8.1  
- Composer  
- Node.js >= 18.x  
- NPM  
- MySQL o MariaDB  

---

## Configuración Inicial

### 1. Clonación del Repositorio

```bash
git clone https://github.com/IDragonII/inventariado_unap.git
cd inventariado_unap
```

---

## Backend (Laravel)

Instalar dependencias:

```bash
composer install
```

Crear archivo de entorno:

```bash
cp .env.example .env
```

Generar clave:

```bash
php artisan key:generate
```

Configurar base de datos en `.env` y ejecutar:

```bash
php artisan migrate --seed
```

Crear enlace de almacenamiento:

```bash
php artisan storage:link
```

Iniciar servidor:

```bash
php artisan serve
```

---

## Frontend (Quasar)

Instalar dependencias:

```bash
npm install
```

Ejecutar entorno de desarrollo:

```bash
npm run dev
```

---

## Procesamiento de Tareas (Queues)

Ejecutar en una terminal independiente dentro del backend:

```bash
php artisan queue:work --timeout=3600 --memory=1024
```

---

## Comandos Adicionales

### Limpiar caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Listar rutas

```bash
php artisan route:list
```

---

## Producción Frontend

Generar build:

```bash
npm run build
```

---

## Notas de Implementación

- El sistema utiliza Laravel Sanctum para la autenticación.
- El worker de colas debe mantenerse activo para exportaciones y procesos en segundo plano.
