# Prompt de auditoria integral del modulo de proteccion de datos

Actua como un equipo adversarial senior compuesto por:

1. Un arquitecto de software experto en PHP 8, CodeIgniter 4 y MySQL/MariaDB.
2. Un especialista en seguridad de aplicaciones, privacidad por diseno y evidencia digital.
3. Un QA lead experto en pruebas funcionales, integracion, concurrencia y regresion.
4. Un abogado colombiano especializado en proteccion de datos personales, propiedad horizontal y contratos de transmision de datos.

Debes auditar de forma integral el modulo de proteccion de datos personales ubicado en:

`C:\xampp\htdocs\censo`

## Objetivo

Determinar, con evidencia reproducible, si el modulo esta listo para produccion y si implementa de manera coherente, segura y trazable los siete documentos maestros del sistema. No aceptes como prueba suficiente que exista una clase, una tabla, una validacion textual o un test superficial: verifica que cada control opere de extremo a extremo y que no pueda eludirse por otra ruta, una peticion manipulada, concurrencia, cambios directos de estado o datos incompletos.

Los siete documentos son:

1. Politica de Tratamiento de Datos Personales.
2. Aviso de Privacidad.
3. Autorizacion para el Tratamiento de Datos Personales.
4. Procedimiento de Consultas, Reclamos, Rectificacion, Actualizacion, Revocatoria y Supresion.
5. Manual Interno de Seguridad de la Informacion y Proteccion de Datos.
6. Compromiso Individual de Confidencialidad y Uso Autorizado de la Informacion.
7. Acuerdo de Transmision y Tratamiento de Datos con Encargados.

Las revisiones juridicas disponibles estan en `docs/legal-reviews/` y deben tratarse como requisitos de aceptacion. Tambien debes revisar el contenido efectivo generado por `app/Libraries/PrivacyDocumentService.php`, `PrivacyConfidentialityService.php` y `PrivacyProcessorAgreementService.php`; no presupongas que el titulo o el nombre de una migracion demuestra cumplimiento.

## Reglas de trabajo

- Empieza en modo de auditoria: no modifiques archivos, datos ni configuracion hasta entregar el primer dictamen.
- No uses produccion para pruebas destructivas o de escritura. Si inspeccionas credenciales o configuracion, no reveles secretos en el informe.
- Trabaja sobre una base de datos local aislada o transacciones reversibles para las pruebas funcionales.
- No des por aprobada una exigencia juridica solo porque aparece en una plantilla. Rastrea: variable de origen, validacion, documento renderizado, aceptacion o firma, hash, persistencia, evento de auditoria, PDF/copia y posterior verificacion.
- Distingue claramente entre norma vigente, recomendacion, requisito contractual y decision de diseno. Para afirmaciones juridicas actuales usa fuentes oficiales primarias y cita URL y fecha de consulta.
- No uses proyectos de ley como si fueran derecho vigente.
- No expongas datos personales reales ni envies informacion real a OpenAI o SendGrid durante la auditoria.
- Si una prueba no puede ejecutarse, marca `NO VERIFICADO`; no la conviertas en aprobada por inspeccion parcial.

## Alcance tecnico minimo

Revisa, al menos:

- `app/Controllers/Privacy*.php` y los controladores de autenticacion y administracion modificados por el modulo.
- `app/Libraries/Privacy*.php`, `OpenAiPrivacyService.php` y `EmailService.php`.
- `app/Models/Dp*.php`.
- `app/Views/privacy/` y las vistas de autenticacion/administracion relacionadas.
- `app/Config/Routes.php`, `Filters.php`, `Database.php` y los filtros de autenticacion/roles.
- Las migraciones `2026-07-11-000001` a `2026-07-12-000017`.
- `app/Commands/PrivacyMigrate.php`.
- Todas las pruebas en `tests/unit/` relacionadas con privacidad.
- Configuracion de CSRF, sesiones, cookies, MFA, correo, OpenAI, almacenamiento de PDF, permisos de archivos y manejo de secretos.

## Matriz obligatoria documento-control

Construye una matriz para los siete documentos con estas columnas:

`Documento | Requisito juridico/operativo | Texto maestro exacto | Variable y origen | Regla condicional | Validacion bloqueante | Tabla/campo | Ruta/controlador | Evidencia de aceptacion o firma | Hash/version | Evento de auditoria | Prueba ejecutada | Resultado`

Comprueba especialmente:

### Documentos maestros e instancias

- Versionado, estados, dependencias entre documentos, publicacion y bloqueo de versiones incompletas.
- SHA-256 calculado sobre el contenido canonico correcto y, cuando corresponda, sobre la instancia exacta vista y firmada, no solo sobre la plantilla.
- Inmutabilidad de documentos e instancias firmadas frente a `UPDATE`, `DELETE`, reintentos, doble envio y concurrencia.
- Ausencia de marcadores sin resolver, campos vacios, condicionales desbalanceados y contradicciones entre documentos.
- Fidelidad entre HTML mostrado, datos aceptados, hash almacenado y PDF entregado.
- Posibilidad real de verificar posteriormente una firma, su identidad, fecha, version, IP/agente si aplica, OTP, consentimiento granular y cadena de eventos.

### Autorizaciones y titulares

- Informacion previa completa, finalidades separadas, decisiones granulares y ausencia de autorizacion en bloque.
- Tratamiento diferenciado de datos sensibles, biometria, menores, videovigilancia y tratamientos exceptuados de autorizacion.
- Alternativa real a biometria y ausencia de condicionamiento indebido.
- Revocatoria, supresion, rectificacion, actualizacion, consultas y reclamos con legitimacion y verificacion de identidad proporcionales.
- Computo correcto de dias habiles, requerimientos, desistimiento, traslado, prorroga comunicada antes del vencimiento y vencimientos concurrentes.
- Flujo completo de negativa al tratamiento: registro, restriccion inmediata, propagacion, respuesta, evidencia y bloqueo de usos posteriores.
- Ruta de supresion en datos activos, exportaciones, archivos, caches, integraciones, respaldos y restauraciones; verifica que una restauracion no resucite datos suprimidos.

### Seguridad y acceso

- Separacion por cliente/copropiedad en cada consulta y operacion. Intenta IDOR y manipulacion de `cliente_id`, `solicitud_id`, `documento_id`, `acuerdo_id` y tokens.
- RBAC, minimo privilegio, alta/cambio/baja, compromisos de confidencialidad vigentes y bloqueo efectivo de usuarios sin requisitos.
- MFA, OTP: entropia, expiracion, un solo uso, intentos, rate limiting, replay, enumeracion y almacenamiento seguro.
- CSRF, XSS almacenado/reflejado, SQL injection, mass assignment, carga de archivos, path traversal, session fixation y enlaces publicos.
- Tokens publicos: entropia, expiracion, revocacion, comparacion segura, filtracion en logs/referer y acceso posterior a la firma.
- Firmas dibujadas y archivos: tamano, tipo real, contenido malicioso, limites y valor probatorio.
- Auditoria append-only y resistencia a alteracion; identifica quien, que, cuando, sobre cual cliente y con que correlacion.
- Cifrado en transito y reposo, minimizacion de logs, secretos fuera del repositorio y mensajes de error sin datos sensibles.

### Incidentes, terceros y proveedores

- Deteccion, contencion, evaluacion, responsables, evidencias y reporte a la SIC dentro del termino aplicable, incluyendo Responsables no inscritos en RNBD si la norma vigente asi lo exige.
- Clasificacion material de terceros: Encargado, Responsable independiente, rol dual o sin tratamiento. Verifica que revisor fiscal y abogado externo no sean clasificados automaticamente como Encargados.
- Acuerdo del Documento 7 con 21 clausulas y anexos A-F, alcance resuelto, instrucciones, subencargados, paises, controles, incidentes, derechos de titulares, terminacion, devolucion/supresion y no resurreccion desde respaldos.
- Doble firma, facultades de representantes, integridad de instancia, vigencia y bloqueo del proveedor antes de que exista contrato valido.
- `PrivacyProcessorGate`: intenta eludirlo desde todas las llamadas de SendGrid y OpenAI, jobs, errores, recuperacion de clave, MFA, notificaciones y flujos publicos.
- Excepcion de onboarding contractual: demuestra que solo permite enviar lo estrictamente necesario para formalizar el acuerdo y que no se convierte en bypass general.
- SendGrid/OpenAI: minimizacion, contratos, transmision internacional, subencargados, paises, retencion y prohibicion efectiva de enviar identidades a IA.
- Confirma que la Circular 002 de 2025 no se presente como regimen general de transferencias internacionales y que cualquier referencia a clausulas tipo de la Circular 003 de 2025 tenga el alcance correcto.

### Base de datos y operacion

- Ejecuta las migraciones desde una base vacia y desde una base en la version inmediatamente anterior; prueba `up`, fallos parciales y `down` cuando sea seguro.
- Revisa claves foraneas, indices, `NOT NULL`, unicidad, tipos, zonas horarias, transacciones y consistencia entre esquema y modelos.
- Busca estados imposibles y transiciones que solo esten protegidas en la interfaz pero no en servidor/base de datos.
- Prueba concurrencia: doble firma, doble publicacion, dos respuestas, revocacion simultanea, terminacion y notificacion de incidentes.
- Verifica aislamiento multiusuario y multicliente.
- Revisa copias de seguridad, restauracion, lista de exclusiones por supresion, retencion y evidencia de destruccion.
- Evalua observabilidad: errores accionables, correlacion, alertas, vencimientos, reintentos idempotentes y recuperacion ante caida de SendGrid/OpenAI.

### Interfaz y accesibilidad

- Recorre los flujos de administrador, operador, titular, usuario interno y representante de Encargado en escritorio y movil.
- Comprueba que no haya controles solo decorativos, campos condicionales omitidos, texto cortado, superposiciones ni estados ambiguos.
- Revisa accesibilidad basica: teclado, foco, labels, contraste, mensajes de error, firma tactil y lector de pantalla.
- Verifica que confirmar lectura por scroll no sea la unica evidencia de comprension ni pueda romperse por contenido corto, zoom o dispositivo movil.

## Pruebas obligatorias

1. Ejecuta la suite existente e informa comando, resultado, pruebas, aserciones y omisiones.
2. Evalua la calidad de los tests: identifica los que solo buscan cadenas en archivos y no prueban comportamiento.
3. Agrega en el informe una lista de pruebas faltantes clasificadas en unitarias, integracion, HTTP, base de datos, seguridad y end-to-end.
4. Ejecuta pruebas HTTP reales contra el entorno local para rutas autorizadas y no autorizadas.
5. Prueba al menos dos clientes para detectar fugas entre copropiedades.
6. Realiza pruebas negativas y manipulacion directa de parametros para cada operacion sensible.
7. Compara una instancia firmada de cada documento aplicable contra el contenido efectivamente mostrado y el hash recalculado.

Comandos iniciales sugeridos, que debes adaptar al proyecto:

```powershell
git status --short
php spark routes
php spark migrate:status
vendor\bin\phpunit
```

No ejecutes `privacy:migrate` contra produccion ni uses `D:\DESARROLLO\KEYS\sql.txt` para escrituras durante esta auditoria.

## Formato del entregable

Abre con uno de estos dictamenes:

- `APROBADO PARA PRODUCCION`
- `APROBADO CON CONDICIONES`
- `NO APROBADO`

Luego entrega, en este orden:

### A. Resumen ejecutivo

Maximo 15 lineas. Indica el riesgo global y los tres problemas mas importantes.

### B. Hallazgos

Ordenados por severidad: `CRITICO`, `ALTO`, `MEDIO`, `BAJO`. Cada hallazgo debe incluir:

`ID | Severidad | Documento/control afectado | Archivo:linea | Escenario reproducible | Impacto juridico/tecnico | Evidencia | Correccion exacta | Prueba de aceptacion`

No incluyas recomendaciones genericas. Toda afirmacion debe apuntar a codigo, esquema, salida de prueba o fuente oficial.

### C. Matriz de los siete documentos

Incluye la matriz documento-control completa y marca cada fila `CUMPLE`, `PARCIAL`, `NO CUMPLE` o `NO VERIFICADO`.

### D. Arquitectura y amenazas

Diagrama textual de componentes, limites de confianza, datos sensibles, actores, proveedores y principales rutas de ataque.

### E. Base de datos y migraciones

Resultado reproducible de instalacion limpia, actualizacion, restricciones, triggers, concurrencia, aislamiento por cliente e integridad.

### F. Pruebas

Comandos ejecutados, resultados completos resumidos, cobertura real por flujo, falsos positivos de la suite y pruebas faltantes.

### G. Plan de correccion

Lista priorizada en bloques `P0`, `P1`, `P2`, con archivos concretos, dependencia, esfuerzo estimado y criterio objetivo de cierre.

### H. Veredicto de despliegue

Indica expresamente:

- Que bloquea produccion.
- Que puede aceptarse temporalmente y bajo que control compensatorio.
- Que debe probarse nuevamente despues de corregir.
- Riesgo residual.

### I. Anexo juridico

Fuentes oficiales primarias, URL, fecha de consulta, norma vigente y requisito del aplicativo que soporta cada una. Separa cualquier inferencia o recomendacion de la obligacion legal estricta.

## Criterio de rigor

Tu funcion no es confirmar que el equipo trabajo mucho ni repetir el diseno declarado. Tu funcion es intentar demostrar, mediante casos reproducibles, que el modulo puede fallar juridica, funcional o tecnicamente. Solo marca un control como aprobado cuando exista evidencia de que funciona de extremo a extremo y de que sus rutas alternativas relevantes tambien quedan protegidas.
