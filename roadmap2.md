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

## COMPLETADO recientemente

- [x] **Recuperación de contraseña** (forgot/reset por correo, SendGrid). `PasswordController`, rutas `/forgot` `/reset/{token}`, columnas `reset_token`/`reset_expires` en `usuarios`. Token un solo uso, expira 1h.
- [x] **Dashboard de inteligencia de negocio** (`InteligenciaController`, `app/Views/inteligencia/index.php`, Chart.js local en `public/assets/js/`). Rutas: `/inteligencia` (cliente) y `/admin/clientes/{id}/inteligencia` (admin). KPIs + gráficos con **cross-filter server-side**, filtros persistentes por URL y chips. Segmenta por sexo, torre, edad, parentesco, tipo de inmueble; mascotas por tipo.
  - Se agregó `sexo` (M/F/Otro) a `censo_residentes` + selector en el formulario poblacional (`partials/resident_row.php`). Registros previos quedan 'Sin dato'.
- [x] **Rediseño de PDFs** (`app/Views/pdf/*`): fotos de mascotas grandes y etiquetadas, columna Sexo en residentes, tablas con franjas, header refinado.
- [x] **Rediseño del backoffice** con `public/assets/css/app.css` (sistema de diseño compartido) enlazado en vistas admin/cliente.
- [x] **Páginas de error 404/500** con branding (`app/Views/errors/html/`).
- [x] **Formularios públicos** pulidos (logo del cliente en header, focos con color de marca).
- [x] **Comandos demo** (`demo:seed`, `demo:users`): cliente demo "Conjunto Residencial Demo" (slug `demo-muestra`) + usuarios de prueba por rol (pass `Demo2026*`). Borrar con `demo:seed clean` / `demo:users clean`.

### Credenciales de prueba (producción, sobre el cliente demo)
- superadmin: `edison.cuervo@cycloidtalent.com` / `Colombia2026+`
- admin: `admin@demo.test` · cliente: `cliente@demo.test` · consejo: `consejo@demo.test` · comité: `comite@demo.test` — todos `Demo2026*`

## PRÓXIMO / IDEAS

- [ ] Capturar `sexo` también en propietarios/arrendatarios/responsable si se quiere ampliar la segmentación.
- [ ] Más gráficos BI (vehículos por tipo, parqueadero, vive/no vive, mascotas vacunadas/esterilizadas).
- [ ] Exportar el dashboard a PDF/imagen.

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
