# ROADMAP 2 — Censo APP (continuación / handoff a Codex)

> Documento de continuidad **vigente**. El histórico completo y el modelo de datos están en `ROADMAP.md`.
> Trabajar SIEMPRE en `cycloid` y mergear a `main` cuando un avance esté estable.

---

## Estado: app FUNCIONALMENTE COMPLETA y desplegada

Hitos 0–11 implementados, verificados y **en vivo** en local y producción.

- **Local:** XAMPP/MariaDB base `censo`. Servir con `php spark serve --host 127.0.0.1 --port 8080`.
  - En esta máquina `localhost:8080` puede chocar con otro proyecto (`actas`); usar **`127.0.0.1:8080`**.
- **Producción:** https://censo.cycloidtalent.com — aaPanel, Ubuntu, ruta `/www/wwwroot/censo`, document root `/public`, BD DigitalOcean (MySQL, SSL). PHP 8.4, Composer 2.8, Git.
- **Superadmin:** `edison.cuervo@cycloidtalent.com` (contraseña en `superadmin.password` del `.env`).
- **Git:** `main` (estable) y `cycloid` (desarrollo), sincronizadas con producción.

### Funcionalidad lista
- Login + sesión + filtros `auth` / `role` (superadmin, admin, cliente, consejo, comite). CSRF global. URLs limpias.
- Backoffice: CRUD clientes (logo + colores), configuración del conjunto + generador de inmuebles, usuarios por cliente, tablero de avance, respuestas + export CSV.
- QR por instrumento (poblacional / mascotas) + pieza gráfica.
- Formularios públicos anónimos `/q/{token}`: selección de inmueble no digitable, consentimiento Habeas Data, n-items, firma en canvas, uploads.
- PDF (dompdf) con branding + firma → `App\Libraries\CensoPdf`. Correo (SendGrid v7) → `App\Libraries\EmailService` (envía PDF al diligenciador y al cliente).
- PWA instalable desde login. Branding "Censo APP", favicon, login rediseñado, crédito "Desarrollado por Enterprisesst · empowered by Cycloid Talent SAS".

---

## Reglas y flujo (OBLIGATORIO)

1. **BD solo por migraciones CI4** (`php spark migrate`). Nunca SQL manual. Local primero, luego producción.
2. **`.env` NUNCA se commitea.** Secretos solo en `.env`. Claves del usuario en `D:\DESARROLLO\KEYS\`.
3. **Flujo:** desarrollar en `cycloid` → probar local → `git checkout main && git merge cycloid && git push origin main && git checkout cycloid` → deploy `ssh -i C:\Users\elipt\.ssh\id_ed25519 root@66.29.154.174`, `cd /www/wwwroot/censo && git pull origin main` (+ `composer install` si hay deps, `php spark migrate` si hay migraciones, `chown -R www:www . && chmod -R 775 writable`).
4. **`vendor/` no está en git** → producción necesita `composer install` tras nuevas dependencias.
5. **`.env` de producción** ya tiene BD DigitalOcean, `superadmin.*`, `email.*`. SSL DigitalOcean se activa por hostname (ver `app/Config/Database.php`).

---

## EN CURSO / PRÓXIMO

- [ ] **Recuperación de contraseña** (forgot/reset por correo con SendGrid).
- [ ] **Dashboard de inteligencia de negocio**: segmentación por inmueble, torre, sexo y demás parámetros; filtros persistentes; gráficos filtrables y clickeables (cross-filter).
  - NOTA: el campo **sexo NO se captura hoy** (residentes: nombre, documento, parentesco_id, edad). Requiere migración para agregar `sexo` a `residentes` (y opcionalmente a propietarios/responsable) + ajuste de los formularios públicos.

## PENDIENTES (pulido)

- [ ] `.env.example` con todas las variables (sin secretos).
- [ ] Páginas de error 404 / 500 con branding.
- [ ] Rediseño visual del backoffice (estilo del nuevo login).
- [ ] Rediseño de páginas públicas con branding del cliente.
- [ ] Auditoría PWA Lighthouse en producción.
- [ ] Validaciones por modelo.

## Mapa de archivos clave

- Rutas: `app/Config/Routes.php` · Auth: `app/Controllers/AuthController.php`, `app/Filters/{AuthFilter,RoleFilter}.php`
- Backoffice: `app/Controllers/Admin/*`, `ClienteTableroController.php`, `ClienteRespuestasController.php`
- Público: `app/Controllers/QrPublicController.php`, vistas `app/Views/public/*`
- PDF: `app/Libraries/CensoPdf.php`, vistas `app/Views/pdf/*` · Correo: `app/Libraries/EmailService.php`, `app/Views/emails/*`, `app/Commands/TestEmail.php`
- Modelos: `app/Models/*` (18) · Migraciones/seeders: `app/Database/*`
- PWA/assets: `public/manifest_login.json`, `public/sw_login.js`, `public/assets/icons/`, `public/favicon.*`
