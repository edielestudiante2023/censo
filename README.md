# censo

Aplicación **PWA** para **censos poblacionales y de mascotas** en propiedad horizontal (conjuntos residenciales), construida sobre **CodeIgniter 4**.

## Descripción

- Multi-tenant: cada conjunto (cliente) administra y visualiza únicamente sus propios datos.
- Dos instrumentos independientes, cada uno con su propio QR:
  - **Censo poblacional** (para la administración): propietarios, residentes, arrendatarios, vehículos y contactos.
  - **Censo de mascotas** (para Secretaría de Salud).
- Los residentes diligencian de forma anónima escaneando un QR por instrumento, sin necesidad de iniciar sesión.
- Autorización de **tratamiento de datos personales** (Ley 1581 de 2012).
- Firma en pantalla, generación de **PDF** y envío por correo (SendGrid) al diligenciador y al cliente.
- Personalización por cliente (logo y colores).

## Stack

- CodeIgniter 4 · PHP 8.4 · MariaDB/MySQL
- Local: XAMPP (MariaDB `localhost`, base `censo`)

## Roadmap

Ver [ROADMAP.md](ROADMAP.md) para el detalle de hitos y avance.
