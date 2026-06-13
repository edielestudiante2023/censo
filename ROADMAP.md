# ROADMAP — Censo PWA (Propiedad Horizontal)

> Documento de continuidad. Si se acaban los tokens, **otro agente (Codex u otro) puede continuar desde aquí**.
> Marca cada checkbox al completar. Trabajar SIEMPRE en la rama `cycloid` y mergear a `main` cuando un avance esté estable.

---

## ESTADO ACTUAL / HANDOFF (al 2026-06-13)

**Hitos 0–12 COMPLETOS y DESPLEGADOS en LOCAL y PRODUCCIÓN.** App con login, backoffice, QR, formularios públicos, PDF, envío por correo y PWA instalada desde login. El flujo real de punta a punta ya fue probado en producción.

- **Local:** XAMPP/MariaDB base `censo`. Servir con `php spark serve` → http://localhost:8080/ . 18 tablas + seeders. Login OK con el superadmin.
- **Producción:** https://censo.cycloidtalent.com/ — desplegada desde `main`; login, PWA (`manifest_login.json`/`sw_login.js`), backoffice, formularios públicos, PDF (dompdf) y correo (SendGrid) operativos. BD DigitalOcean (SSL). aaPanel, ruta `/www/wwwroot/censo`, document root `/public`.
- **Superadmin:** `edison.cuervo@cycloidtalent.com` (contraseña en `superadmin.password` del `.env`, NO en git).
- **Git:** `main` (estable) y `cycloid` (desarrollo) sincronizadas; ver `git log -1 --oneline` para el SHA actual.
- **`.env` de producción** ya tiene BD DigitalOcean + `superadmin.*` + `email.*` (SendGrid). Replicar cualquier var nueva ahí (no va en git).

**PRÓXIMO PUNTO DE ENTRADA → Pulido / operación.** El núcleo funcional está completo y en vivo. Pendiente externo: ajustar en Cloudflare el bloque gestionado de `robots.txt` si se quiere Lighthouse limpio al 100%. Desde el repo, `public/robots.txt` ya es valido.

**Auditoría PWA (2026-06-13):** Lighthouse 12.8.2 ejecutado contra producción y local. Producción cumple HTTPS, manifest, service worker e íconos. Se corrigieron en local avisos de SEO/accesibilidad del login (meta description, orden de encabezados, nombre accesible del botón de contraseña). `robots.txt` remoto contiene un bloque "Cloudflare Managed Content" con `Content-Signal`, que Lighthouse reporta como directiva desconocida aunque no viene del `public/robots.txt` versionado. Verificado: `public/robots.txt` solo contiene directivas estándar; el ajuste debe hacerse en Cloudflare, no en la app.

**Prueba E2E producción (2026-06-13):** completada con cliente demo `Demo E2E 20260613115708` (`id=1`, slug `demo-e2e-20260613115708`). Se generó inmueble `Casa 1`, QR poblacional y QR mascotas, se diligenciaron ambos formularios públicos vía `/q/{token}`, se generaron PDFs desde backoffice y la BD quedó con `pdf_enviado=1` y `fecha_envio` en ambos censos. Tokens usados: poblacional `98378844504095414e586b045b6511511a06e9c1198d86d5`, mascotas `78bf91e3befc8c7400f5ae0163d90f743162a96d9c994483`. El cliente demo fue archivado después de la prueba desde la ruta admin; los QR públicos ahora muestran "QR no disponible".

**Prueba PDF con imagenes WhatsApp (2026-06-13):** completada en producción con cliente demo activo `Demo Imagenes E2E 20260613145137` (`id=3`, slug `demo-img-e2e-20260613145137`). Se diligenció el censo de mascotas con 3 imágenes `.jpeg` de `D:\Descargas` en los campos `foto_0`, `foto_carne_0` y `foto_poliza_0`; la BD guardó las tres rutas y generó PDF `mascotas/2` con `pdf_enviado=1`. Copia local descargada para revisión: `D:\Descargas\censo-mascotas-whatsapp-e2e-20260613145137.pdf` (5.263.064 bytes). Luego se optimizó el manejo de imágenes: las fotos subidas se guardan como JPEG máximo 1600 px y el PDF usa miniaturas JPEG máximo 480 px; repetición `mascotas/3` bajó a 127.231 bytes con las mismas imágenes. Mantener el cliente demo activo hasta que el usuario termine de revisar los PDFs.

**Nota de pruebas locales:** en esta máquina `localhost:8080` puede resolver a otro proyecto (`actas`); para `censo` usar `127.0.0.1:8080` si hay conflicto.

**Nota de PHPUnit local:** `composer test` pasa en esta máquina (40 tests, 85 assertions). El test ejemplo de SQLite se salta automáticamente si la extensión PHP `sqlite3` no está cargada. Hay cobertura básica de filtros de acceso, validaciones de modelos, scopes multi-tenant y lógica de envío de correo con PDF.

### Flujo de trabajo (repetir en cada avance)
1. Desarrollar en `cycloid`, probar en LOCAL (`php spark serve`).
2. Cambios de BD SOLO con migraciones (`php spark migrate`). Local primero.
3. Commit en `cycloid` → `git checkout main && git merge cycloid && git push origin main && git checkout cycloid`.
4. Deploy producción por SSH: `ssh -i C:\Users\elipt\.ssh\id_ed25519 root@66.29.154.174`, luego `cd /www/wwwroot/censo && git pull origin main`, y si hay migraciones nuevas `php spark migrate` (+ `chown -R www:www . && chmod -R 775 writable`).
5. Credenciales de SSH, BD producción y superadmin: en `D:\DESARROLLO\KEYS\` del usuario (NUNCA al repo).

---

## 0. Contexto del proyecto

- **Qué es:** PWA para **censos poblacionales y de mascotas** en conjuntos residenciales (propiedad horizontal).
- **Modelo de negocio:** se vende a conjuntos. Cada conjunto (cliente) recibe usuario/contraseña para ver sus datos y **genera sus propios QR** para pegar en torres y que los residentes diligencien.
- **Multi-tenant:** cada cliente solo ve SUS datos. Casi toda tabla lleva `cliente_id`.
- **Dos instrumentos SEPARADOS** (cada uno con su propio QR, uno por instrumento):
  - **A. Censo poblacional** → para la **administración** (completo).
  - **B. Censo de mascotas** → para **Secretaría de Salud** (solo mascotas).
- **Quien diligencia NO es usuario:** llena un formulario público/anónimo vía QR. Solo cliente/consejo/comité inician sesión para VER.
- **Roles:** `superadmin`, `admin` (globales, del proveedor) · `cliente`, `consejo`, `comite` (ligados a un `cliente_id`).
- **Tipos de conjunto:** `casas`, `apartamentos`, `mixto`. La numeración NO es estándar: varía por cliente y por torre (8 aptos/piso, torres de 24 pisos, etc.) → **generador de inmuebles parametrizable**.
- **Branding:** personalizar cada cliente con su **logo y colores** (portal, formularios, PDF, pieza gráfica del QR).

## Reglas duras de BASE DE DATOS (taxativas)

- **Prohibido SQL manual** (phpMyAdmin, cliente, navegador). Solo cambios vía **migraciones CI4** (`php spark migrate`) = script PHP CLI del repo.
- **Local primero, producción después** (solo si local sale OK).
- **Local:** XAMPP/MariaDB en `localhost`, base `censo`, usuario `root` sin contraseña.
- **Producción:** MySQL gestionado en DigitalOcean, base `censo`, `sslmode=REQUIRED`. Credenciales **solo en `.env`** (DigitalOcean), NUNCA en git. (Ver archivo de claves del usuario; no copiar secretos al repo.)
- **`.env` jamás se commitea** (ya está en `.gitignore` de CI4).

## Texto Habeas Data (aprobado, parametrizable)

> Autorización para el Tratamiento de Datos Personales. En cumplimiento de la Ley 1581 de 2012, el Decreto 1377 de 2013 y demás normas concordantes, autorizo de manera previa, expresa e informada a **{NOMBRE_CONJUNTO}**, identificado con NIT **{NIT}**, en calidad de Responsable del Tratamiento, para recolectar, almacenar, usar, actualizar y suprimir los datos personales aquí suministrados. La finalidad es la gestión administrativa de la copropiedad: actualización del censo de residentes, comunicación con propietarios y residentes, control de acceso y parqueaderos, atención de emergencias y convivencia, y el cumplimiento de las obligaciones propias de la propiedad horizontal. Declaro que la información es veraz y que, como Titular, conozco mi derecho a conocer, actualizar, rectificar y suprimir mis datos y a revocar esta autorización, escribiendo a **{CORREO_ADMIN}**. El suministro de datos de terceros (otros residentes) se realiza bajo mi responsabilidad, manifestando contar con su autorización. Esta autorización se entiende otorgada al enviar el presente formulario.

Parámetros: `{NOMBRE_CONJUNTO}`, `{NIT}`, `{CORREO_ADMIN}`.

---

## HITOS

### Hito 0 — Base
- [x] Instalar CodeIgniter 4 vía Composer (v4.7.3)
- [x] Inicializar git, `main` + rama `cycloid`, remoto GitHub
- [x] README.md y ROADMAP.md
- [x] Commit del proyecto base CI4 en `cycloid` + merge a `main`
- [x] Deploy base a producción (aaPanel, document root `/public`, pseudo-static nginx, HTTPS OK en https://censo.cycloidtalent.com/)

### Hito 1 — Configuración local
- [x] Copiar `env` → `.env`
- [x] Configurar `app.baseURL` (`http://localhost:8080/`) y entorno `development`
- [x] Configurar BD local en `.env` (MariaDB localhost, base `censo`, root sin pass)
- [x] Generar `encryption.key` (`php spark key:generate`)
- [x] Base `censo` disponible en MariaDB local (ya existía) y conexión verificada (`db:table --show`)
- [x] Configurar BD de producción en `.env` del servidor (DigitalOcean + SSL en `Config/Database.php`; no en git)

> **Producción:** sitio creado en aaPanel → https://censo.cycloidtalent.com/ (Ubuntu 24.04, ruta `/www/wwwroot/censo`). Acceso SSH y credenciales del superadmin en archivos de claves del usuario (NO en el repo).

### Hito 2 — Modelo de datos (migraciones)
**Núcleo / seguridad** — LOTE 1 ✅ migrado en local
- [x] `roles` (superadmin, admin, cliente, consejo, comite)
- [x] `clientes` (nombre_tercero, tipo_documento, documento/NIT, direccion, ciudad, telefono, persona_contacto, email, logo, color_primario, color_secundario, tipo_conjunto[casas|apartamentos|mixto], slug, texto_habeas_data, activo)
- [x] `usuarios` (cliente_id NULL si superadmin/admin, rol_id, nombre, email, password_hash, telefono, activo, last_login)

**Estructura física** — LOTE 2 ✅ migrado en local
- [x] `torres` (cliente_id, nombre, num_pisos) — solo aptos/mixto
- [x] `inmuebles` (cliente_id, torre_id NULL en casas, tipo[casa|apartamento], identificador, piso) — único(cliente_id,torre_id,identificador)

**QR**
- [x] `qr_codes` (cliente_id, tipo_instrumento[poblacional|mascotas], token único, titulo, activo)

**Instrumento A — Censo poblacional** — LOTE 3 ✅ migrado en local
- [x] `censos_poblacionales` (cliente_id, qr_id, inmueble_id, autorizacion_datos, fecha_autorizacion, vive_en_copropiedad, direccion_notificacion, quien_vive, administrado_por[inmobiliaria|persona_natural], inmobiliaria_nombre/telefono/correo, correo_contacto, discapacidad_descripcion, tiene_parqueadero, observaciones, firmante_nombre, firma_imagen, pdf_ruta, pdf_enviado, fecha_envio, ip, user_agent)
- [x] `censo_propietarios` (censo_id, nombre, documento, telefono, correo)
- [x] `censo_arrendatarios` (censo_id, nombre, documento, telefono, correo)
- [x] `censo_residentes` (censo_id, nombre, documento, parentesco_id, edad)
- [x] `censo_vehiculos` (censo_id, tipo_vehiculo_id, marca, linea, modelo, color, placa)
- [x] `censo_telefonos` (censo_id, numero, orden)

**Instrumento B — Censo de mascotas** — LOTE 3 ✅ migrado en local
- [x] `censos_mascotas` (cliente_id, qr_id, inmueble_id, autorizacion_datos, fecha_autorizacion, responsable_nombre/documento/telefono/correo, firmante_nombre, firma_imagen, pdf_ruta, pdf_enviado, fecha_envio, ip, user_agent)
- [x] `mascotas` (censo_mascota_id, nombre, tipo_mascota_id, edad, raza_color, vacunacion_completa, esterilizada, foto_ruta, foto_carne_ruta, foto_poliza_ruta)

**Catálogos** — LOTE 2 ✅ migrado en local (tablas creadas; datos en Hito 3 seeders)
- [x] `tipos_documento` (NIT, CC, CE, TI, …)
- [x] `parentescos`
- [x] `tipos_vehiculo` (carro, moto, bici, …)
- [x] `tipos_mascota` (perro, gato, …)

### Hito 3 — Seeders ✅ ejecutado en local
- [x] Seeder de catálogos (tipos_documento, parentescos, tipos_vehiculo, tipos_mascota)
- [x] Seeder de roles (superadmin, admin, cliente, consejo, comite)
- [x] Seeder usuario superadmin inicial (lee credenciales desde `.env`: `superadmin.email`, `superadmin.nombre`, `superadmin.password`)

> Ejecutar: `php spark db:seed DatabaseSeeder`. Para producción, definir primero `superadmin.*` en el `.env` del servidor (no va en git).

### Hito 4 — Modelos y entidades ✅ (18 models, sin errores de sintaxis)
- [x] Models CI4 por tabla (núcleo, estructura, catálogos, QR, censos + hijas)
- [x] Filtro/scope multi-tenant por `cliente_id` (`forCliente()`); hijas con `forCenso()`
- [x] Validaciones por modelo (`validationRules` en núcleo, estructura, catálogos, QR, censos + hijas)

### Hito 5 — Autenticación y autorización ✅ probado en local
- [x] Login (`AuthController`) con `password_verify`, sesión y `last_login`
- [x] Vista de login responsive (lista para branding y bloque PWA del Hito 11)
- [x] Filtro `auth` (sesión) y filtro `role` (por rol) registrados; CSRF activado
- [x] Rutas: `/`, `/login` (GET/POST), `/logout`, `/dashboard` (protegida)
- [x] `indexPage` vacío (URLs limpias para nginx)
- [x] Verificado: superadmin entra → dashboard muestra rol y nombre
- [x] Aislamiento de datos por cliente en rutas cliente/consejo/comité (`session()->cliente_id` + filtros `cliente_id`)

### Hito 6 — Panel administración (backoffice)
- [x] CRUD clientes (superadmin/admin) con logo y colores (branding)
- [x] Configuración del conjunto: tipo, torres, **generador de inmuebles** (rango casas / torres×pisos×aptos)
- [x] Gestión de usuarios por cliente
- [x] Tablero: total inmuebles vs respondidos (faltantes por diligenciar)
- [x] Visualización/exportación de respuestas

### Hito 7 — Generación de QR
- [x] Generar QR por instrumento (poblacional / mascotas) con token único
- [x] Pieza gráfica con branding del cliente (logo) para imprimir/pegar en torres

### Hito 8 — Formularios públicos (PWA, anónimos)
- [x] Resolver QR token → cliente + tipo de instrumento
- [x] Selección de inmueble no digitable (torre→apto / casa) según config del cliente
- [x] Pantalla de consentimiento Habeas Data (obligatorio)
- [x] **Formulario A poblacional** con secciones n-items (propietarios, residentes, arrendatarios, vehículos, teléfonos)
- [x] **Formulario B mascotas** con n-items mascotas + subida de fotos (cámara)
- [x] Subida de archivos a `writable/uploads` (en BD solo la ruta)
- [x] **Firma en canvas táctil** → guardar PNG
- [x] Validaciones y guardado transaccional

### Hito 9 — PDF ✅ probado en local
- [x] Integrar motor PDF (dompdf v3.1) — `composer require dompdf/dompdf` (producción: correr `composer install` tras el pull)
- [x] `app/Libraries/CensoPdf.php` (genera, guarda en `writable/uploads/...`, actualiza `pdf_ruta`)
- [x] Plantilla PDF poblacional (`app/Views/pdf/poblacional.php`) con branding (logo + color del cliente)
- [x] Plantilla PDF mascotas (`app/Views/pdf/mascotas.php`) con branding + fotos
- [x] Incrustar firma (base64) e info del diligenciamiento (IP, fechas, Habeas Data)
- [x] Hook en `QrPublicController::submit()` → genera PDF tras guardar; descarga en página de gracias (`/q/{token}/pdf`)
- [x] Descarga desde backoffice (respuestas) con regeneración si falta: `respuestas/pdf/{instrumento}/{id}` y `admin/.../respuestas/pdf/...`
- [x] Verificado: PDF poblacional y mascotas generados OK (con datos de prueba, luego limpiados)

### Hito 10 — Correo (SendGrid) ✅ probado en local y producción
- [x] `composer require sendgrid/sendgrid "^7.0"` (producción: `composer install` tras el pull)
- [x] Variables `email.*` en `.env` (fromEmail `notificacion.cycloidtalent@cycloidtalent.com`, fromName `Censo APP`, SMTPPass=API key) — NO en git; replicar en `.env` del servidor
- [x] `app/Libraries/EmailService.php` (API HTTP SDK v7, click tracking OFF, adjunto PDF)
- [x] Vistas de email (`emails/test_email`, `emails/censo`) + comando `php spark test:email`
- [x] Enganche en `submit()`: tras el PDF, enviar al **diligenciador** y al **cliente**; marca `pdf_enviado`/`fecha_envio`
- [x] Verificado: `php spark test:email` entregó OK (API key + remitente verificado)
- [x] Verificado en E2E producción: formularios poblacional y mascotas marcaron `pdf_enviado=1` con `fecha_envio`

### Hito 11 — PWA instalable desde login
- [x] Íconos disponibles (`public/assets/icons/icon-192.png`, `icon-512.png`, `icon-192-maskable.png`, `icon-512-maskable.png`)
- [x] `public/manifest_login.json` (id/start_url = ruta de login)
- [x] `public/sw_login.js` (service worker minimalista, network-first)
- [x] Tarjeta de instalación + modal iOS en la vista de login
- [x] Meta tags PWA + registro del SW
- [x] Desplegado en producción: `manifest_login.json` y `sw_login.js` responden 200 en HTTPS
- [x] Verificar instalabilidad con auditoría Lighthouse / DevTools

### Hito 12 — Despliegue (pipeline ya probado de punta a punta)
- [x] Migrar en LOCAL (`php spark migrate`) y validar
- [x] Migrar en PRODUCCIÓN (DigitalOcean, SSL) — 18 tablas + seeders aplicados
- [x] `.env` de producción con BD DigitalOcean *(SendGrid pendiente en Hito 10)*
- [x] Verificado `.gitignore` (no sube `.env`); proyecto completo en `main`
- [x] Deploy base + login verificados en https://censo.cycloidtalent.com/
- [x] Deploy Hitos 6–10 + PWA a producción (`6daffe5`): pull + `composer install` (dompdf, sendgrid) + `email.*` en `.env`; login/PWA/correo verificados en vivo
- [x] Prueba end-to-end real en producción: cliente demo, generación de inmueble, QR poblacional, QR mascotas, formularios públicos, PDF y correo
- [ ] Repetir `composer install` y/o `php spark migrate` en producción cuando se agreguen dependencias o tablas nuevas

---

## Convenciones
- InnoDB · `utf8mb4` · llaves foráneas · timestamps (`created_at/updated_at`) y borrado lógico (`deleted_at`) donde aplique.
- Todo cambio de BD = migración CI4. Nunca SQL manual.
- Auditoría Habeas Data: guardar `autorizacion_datos`, `fecha_autorizacion`, `ip`, `user_agent` por envío.
- Branch de desarrollo: `cycloid`. Merge a `main` en avances estables.
