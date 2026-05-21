# STOCK 365 - Sistema Web de Control de Inventario y Ventas

**La mejor aplicación para gestionar stock, ventas y dinero en tus sedes deportivas**

## 🎯 Descripción

STOCK 365 es un sistema web moderno, responsive y eficiente diseñado específicamente para controlar inventario y ventas de cervezas en múltiples sedes de apuestas deportivas.

## ✨ Características Principales

### 📊 Para BOSS (Administrador)
- **Dashboard Ejecutivo**: Resumen de ventas, utilidades y alertas de todas las sedes
- **Gestión de Productos**: Crear, editar productos con precios y stock mínimo
- **Control de Inventario**: Stock en tiempo real, historial de movimientos
- **Pedidos de Compra**: Gestión completa de proveedores
- **Reportes Avanzados**: Ventas, utilidades, productos top, comparativo de sedes
- **Aprobación de Cierres**: Validar cierres de caja de operadores
- **Alertas de Stock**: Notificaciones de productos bajo stock
- **Exportación Excel**: Todos los reportes exportables

### 👩‍💼 Para OPERADORES (Empleadas)
- **Punto de Venta (POS)**: Interfaz grande y sencilla con botones grandes
- **Búsqueda de Productos**: Buscador rápido y categorías
- **Carrito de Compra**: Gestión de items con cantidades
- **Cierre de Caja**: Registro de efectivo y transferencias con cálculo automático
- **Recepción de Mercadería**: Confirmar stock recibido
- **Dashboard Simplificado**: Solo lo esencial para operaciones del día

## 🎨 Diseño Visual

**Colores Oficiales (Los Angeles Rams)**:
- Azul Royal Principal: `#003594`
- Dorado/Amarillo Acento: `#FFD100`
- Blanco: `#FFFFFF`

**Diseño**: Limpio, moderno, profesional con estilo deportivo premium

## 🛠 Stack Tecnológico

```
Backend:        Laravel 11 + PHP 8.3
Frontend:       Blade Templates + Livewire + Tailwind CSS
Base de Datos:  MySQL 8.0
Autenticación:  Laravel Fortify + Spatie Permission
Gráficos:       Chart.js
Exportación:    Laravel Excel
```

## 📋 Requisitos del Sistema

- PHP >= 8.3
- Composer
- Node.js y NPM
- MySQL 8.0+
- Servidor Web (Apache/Nginx)

## 🚀 Instalación Rápida

### 1. Clonar el Repositorio
```bash
git clone https://github.com/PERRS04/STOCK365.git
cd STOCK365
```

### 2. Configurar Ambiente
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Editar .env
```env
DB_DATABASE=stock365
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### 4. Instalar Dependencias
```bash
composer install
npm install
```

### 5. Ejecutar Migraciones y Seeders
```bash
php artisan migrate
php artisan db:seed
```

### 6. Compilar Assets
```bash
npm run build
# O para desarrollo:
npm run dev
```

### 7. Iniciar Servidor
```bash
php artisan serve
```

Accede a: **http://localhost:8000**

## 👥 Usuarios de Prueba

### BOSS
- **Email**: boss@stock365.com
- **Contraseña**: password123

### OPERADORES
- **María** (Sede Centro): maria@stock365.com / password123
- **Carlos** (Sede Norte): carlos@stock365.com / password123
- **Ana** (Sede Sur): ana@stock365.com / password123
- **Juan** (Sede Este): juan@stock365.com / password123
- **Rosa** (Sede Oeste): rosa@stock365.com / password123
- **Diego** (Sede Centro Comercial): diego@stock365.com / password123

## 📊 Estructura de Base de Datos

### Tablas Principales
- `users`: Usuarios BOSS y OPERADORES
- `sedes`: Ubicaciones (6 sedes)
- `products`: Catálogo de cervezas
- `inventories`: Stock en tiempo real por sede
- `sales`: Registro de ventas
- `sale_items`: Detalles de cada venta
- `cash_closings`: Cierres de caja diarios
- `purchase_orders`: Pedidos a proveedores
- `stock_alerts`: Alertas de bajo stock
- `inventory_movements`: Historial de movimientos

## 🔐 Seguridad

- ✅ Autenticación con Laravel Fortify
- ✅ Autorización basada en roles (BOSS/OPERATOR)
- ✅ Protección CSRF
- ✅ Validación de datos en servidor
- ✅ Hash de contraseñas con bcrypt
- ✅ HTTPS recomendado

## 📝 Funcionalidades Detalladas

### Módulo Inventario
- Stock en tiempo real por producto y sede
- Historial completo de entradas y salidas
- Alertas automáticas bajo stock mínimo
- Ajustes manuales de stock

### Módulo Ventas
- POS con interfaz intuitiva
- Búsqueda rápida de productos
- Cálculo automático de totales
- Historial de ventas

### Módulo Cierre de Caja
- Cálculo automático de diferencias
- Estados: Pendiente → Aprobado/Rechazado
- Observaciones y auditoría

### Módulo Reportes
- Ventas por período
- Utilidad bruta y margen
- Productos más vendidos
- Comparativo de sedes
- Exportación a Excel

## 🌐 Responsive Design

✅ **Mobile** (< 768px): Stack vertical, botones full width
✅ **Tablet** (768px - 1024px): Layout 2 columnas
✅ **Desktop** (> 1024px): Layout completo con sidebar

## 📞 Soporte

Para dudas o reportar errores, contacta a: [Tu Email]

## 📄 Licencia

MIT License - Libre para uso personal y comercial

---

**Desarrollado con ❤️ usando Laravel 11**

**Última actualización**: Mayo 2026
