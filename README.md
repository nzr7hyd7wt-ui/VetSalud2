# VetSalud

Aplicación web para agendar baño, peluquería y servicios para mascotas.

**Stack:** PHP + mysqli + CSS propio + sesiones PHP (sin frameworks).

**Diseño:** paleta aqua + cream, tipografía Fraunces + Outfit, fotos reales en `img/`.

## Instalación

1. Clona este repositorio dentro de la carpeta `htdocs` de XAMPP con el nombre `VetSalud2`.
2. Enciende Apache y MySQL en XAMPP.
3. Importa la base de datos (ver abajo).

## URL local

http://localhost/VetSalud2/

| Página | URL |
|--------|-----|
| Sitio público | http://localhost/VetSalud2/ |
| Pedir cita | http://localhost/VetSalud2/cita.php |
| Admin | http://localhost/VetSalud2/admin/login.php |

## Credenciales demo

| Campo | Valor |
|-------|-------|
| Correo | `admin@demo.com` |
| Contraseña | `Admin123!` |

## Requisitos

- XAMPP (Apache + MySQL/MariaDB)
- PHP 8+ con extensión mysqli
- Conexión: `127.0.0.1`, usuario `root`, sin contraseña, BD `vetsalud`, puerto `3306`

## Importar la base de datos

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < /Applications/XAMPP/xamppfiles/htdocs/VetSalud2/bd/vetsalud.sql
```

## Seed incluido

- 9 servicios con precios e imágenes
- 4 especies y 20 razas realistas
- 7 dueños, 10 mascotas, 15 citas demo
- Admin: `admin@demo.com` / `Admin123!`

## Flujos

1. **Público:** ver servicios con precios → solicitar cita → confirmación + WhatsApp.
2. **Admin:** login → panel → agenda agrupada por día → CRUD dueños / mascotas / servicios.
