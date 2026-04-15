# Gestion de Activos - UNAP

Sistema de gestion de inventariado y control de activos para la UNAP. Desarrollado con Laravel para el backend y Quasar Framework (Vue.js) para el frontend.

## Requisitos Previos

Antes de iniciar la instalacion, asegurese de tener instalados los siguientes componentes:

* PHP >= 8.1
* Composer
* Node.js >= 18.x
* NPM
* Servidor de base de datos MySQL o MariaDB

## Instalacion del Proyecto

Siga estos pasos para configurar el entorno de desarrollo local.

### 1. Clonacion del Repositorio

bash
git clone https://github.com/IDragonII/inventariado_unap.git
cd inventariado_unap


### 2. Configuracion del Backend (Laravel)

1. Instale las dependencias de PHP:
   bash
   composer install
   

2. Cree el archivo de configuracion de entorno:
   bash
   cp .env.example .env
   

3. Configure sus credenciales de base de datos en el archivo .env:
   text
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nombre_de_tu_bd
   DB_USERNAME=tu_usuario
   DB_PASSWORD=tu_contrasena
   

4. Genere la clave de aplicacion de Laravel:
   bash
   php artisan key:generate
   

5. Ejecute las migraciones y cargue los datos iniciales:
   bash
   php artisan migrate --seed
   

6. Cree el enlace simbolico para los archivos de almacenamiento:
   bash
   php artisan storage:link
   

### 3. Configuracion del Frontend (Quasar)

1. Ingrese al directorio donde se encuentra el archivo package.json del frontend:
   bash
   npm install
   

2. Inicie el servidor de desarrollo del frontend:
   bash
   npm run dev
   

## Ejecucion de Servicios en Segundo Plano

Para que funciones como la exportacion de activos a Excel operen correctamente, es obligatorio mantener activo el procesador de colas de Laravel. Ejecute el siguiente comando en una terminal independiente:

bash
php artisan queue:work --timeout=3600 --memory=1024


## Comandos de Mantenimiento

### Backend
* Iniciar servidor API: php artisan serve
* Limpiar cache: php artisan cache:clear
* Listar rutas: php artisan route:list

### Frontend
* Compilar para produccion: npm run build

## Notas de Implementacion
* El sistema utiliza colas (queues) para procesos pesados de exportacion. Si el comando queue:work no esta ejecutandose, los archivos Excel permaneceran en estado "procesando".
* Verifique que el tiempo de vida de la sesion y los dominios de Sanctum en el .env coincidan con su entorno local para evitar problemas de autenticacion.
