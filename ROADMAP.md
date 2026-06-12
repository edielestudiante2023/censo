# ROADMAP — Censo PWA (Propiedad Horizontal)

> Documento de continuidad. Si se acaban los tokens, **otro agente (Codex u otro) puede continuar desde aquí**.
> Marca cada checkbox al completar. Trabajar SIEMPRE en la rama `cycloid` y mergear a `main` cuando un avance esté estable.

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
- [ ] Configurar BD de producción en `.env` del servidor (no en git)

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
- [ ] `qr_codes` (cliente_id, tipo_instrumento[poblacional|mascotas], token único, titulo, activo)

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
- [ ] Validaciones por modelo (se afinan al construir formularios/CRUD)

### Hito 5 — Autenticación y autorización
- [ ] Login (sin sesión requerida para instalar PWA ni para formularios públicos)
- [ ] Hash de contraseñas, sesión, recordar usuario
- [ ] Filtros por rol (superadmin/admin/cliente/consejo/comite)
- [ ] Aislamiento de datos por cliente

### Hito 6 — Panel administración (backoffice)
- [ ] CRUD clientes (superadmin/admin) con logo y colores (branding)
- [ ] Configuración del conjunto: tipo, torres, **generador de inmuebles** (rango casas / torres×pisos×aptos)
- [ ] Gestión de usuarios por cliente
- [ ] Tablero: total inmuebles vs respondidos (faltantes por diligenciar)
- [ ] Visualización/exportación de respuestas

### Hito 7 — Generación de QR
- [ ] Generar QR por instrumento (poblacional / mascotas) con token único
- [ ] Pieza gráfica con branding del cliente (logo) para imprimir/pegar en torres

### Hito 8 — Formularios públicos (PWA, anónimos)
- [ ] Resolver QR token → cliente + tipo de instrumento
- [ ] Selección de inmueble no digitable (torre→apto / casa) según config del cliente
- [ ] Pantalla de consentimiento Habeas Data (obligatorio)
- [ ] **Formulario A poblacional** con secciones n-items (propietarios, residentes, arrendatarios, vehículos, teléfonos)
- [ ] **Formulario B mascotas** con n-items mascotas + subida de fotos (cámara)
- [ ] Subida de archivos a `writable/uploads` (en BD solo la ruta)
- [ ] **Firma en canvas táctil** → guardar PNG
- [ ] Validaciones y guardado transaccional

### Hito 9 — PDF
- [ ] Integrar motor PDF (dompdf)
- [ ] Plantilla PDF poblacional (con branding)
- [ ] Plantilla PDF mascotas (con branding)
- [ ] Incrustar firma e info del diligenciamiento

### Hito 10 — Correo (SendGrid)
- [ ] `composer require sendgrid/sendgrid "^7.0" -W`
- [ ] Variables `email.*` en `.env` / `.env.example`
- [ ] `app/Libraries/EmailService.php` (API HTTP, click tracking OFF)
- [ ] Vistas de email + comando `php spark test:email`
- [ ] Al finalizar: enviar PDF al **diligenciador** y al **cliente**

### Hito 11 — PWA instalable desde login
- [ ] Verificar HTTPS/localhost e íconos (`public/assets/icons/icon-192.png`, `icon-512.png`)
- [ ] `public/manifest_login.json` (id/start_url = ruta de login)
- [ ] `public/sw_login.js` (service worker minimalista, network-first)
- [ ] Tarjeta de instalación + modal iOS en la vista de login
- [ ] Meta tags PWA + registro del SW
- [ ] Verificar instalabilidad (Lighthouse / DevTools)

### Hito 12 — Despliegue
- [ ] Migrar en LOCAL (`php spark migrate`) y validar
- [ ] Migrar en PRODUCCIÓN solo si local OK
- [ ] Verificar `.env` de producción (BD DigitalOcean + SendGrid)
- [ ] Verificar `.gitignore` antes de subir el proyecto completo (no `.env`)

---

## Convenciones
- InnoDB · `utf8mb4` · llaves foráneas · timestamps (`created_at/updated_at`) y borrado lógico (`deleted_at`) donde aplique.
- Todo cambio de BD = migración CI4. Nunca SQL manual.
- Auditoría Habeas Data: guardar `autorizacion_datos`, `fecha_autorizacion`, `ip`, `user_agent` por envío.
- Branch de desarrollo: `cycloid`. Merge a `main` en avances estables.
