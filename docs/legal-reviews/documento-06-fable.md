# Documento 6 - paquete de revision juridica adversarial

## BLOQUE 1 - PROMPT PARA CLAUDE FABLE

Actua como abogado colombiano senior, auditor adversarial de cumplimiento y especialista en proteccion de datos personales, derecho laboral y contractual, seguridad de la informacion y propiedad horizontal. Revisa exclusivamente el DOCUMENTO 6, titulado "Compromiso de Confidencialidad y Uso Autorizado", incluido despues de este prompt. Esta es una evaluacion independiente: no reutilices recuerdos ni conclusiones de conversaciones anteriores.

### Contexto verificable

- El Responsable es una copropiedad colombiana sometida a propiedad horizontal.
- El documento se firma individualmente por toda persona natural con acceso a datos: Administrador, empleados, miembros autorizados de organos, porteria, vigilancia, aseo cuando corresponda, contratistas, personal temporal, soporte TI y usuarios del aplicativo.
- El sistema documental contiene siete documentos maestros. Los documentos 1 a 5 son Politica, Aviso, Autorizacion, Procedimiento de derechos y Manual Interno de Seguridad.
- El aplicativo usa cuentas individuales, roles, MFA para roles criticos, auditoria, expedientes append-only, inventario por bases, incidentes y controles de alta/cambio/baja.
- La instancia debe identificar firmante, vinculo, rol, bases, finalidades, operaciones autorizadas, vigencia del acceso, responsable que autoriza, version de documentos vinculados y evidencia de aceptacion.
- El hash debe calcularse sobre la instancia individual que vio y acepto el firmante, no sobre una plantilla vacia.
- No asumas implementado ningun control sin evidencia. No confundas este compromiso individual con el contrato de transmision que corresponde a una persona juridica Encargada, que pertenece al Documento 7.

### Marco que debes verificar a la fecha de la respuesta

Usa fuentes oficiales y primarias. Contrasta como minimo:

1. Constitucion Politica, articulo 15.
2. Ley 1581 de 2012, especialmente los principios de finalidad, acceso y circulacion restringida, seguridad y confidencialidad; categorias sensibles y de menores; y deberes de Responsables y Encargados.
3. Decreto 1074 de 2015, Capitulo 25: responsabilidad demostrada, politicas internas efectivas y contrato de transmision cuando corresponda.
4. Resolucion SIC 25176 del 29 de mayo de 2020 y doctrina oficial asociada: es contrario a la Ley 1581 limitar temporalmente la reserva sobre datos personales despues de terminada la relacion.
5. Guia SIC para el Oficial de Proteccion de Datos, responsabilidad demostrada, propiedad horizontal, videovigilancia y gestion de incidentes cuando sean pertinentes.
6. Codigo Sustantivo del Trabajo y regimen contractual colombiano solo para consecuencias realmente aplicables. No inventes multas privadas, sanciones automaticas, renuncias a derechos, clausulas penales ni responsabilidad objetiva.
7. Ley 527 de 1999 cuando la aceptacion sea electronica.

Verifica reformas, circulares, resoluciones y jurisprudencia vigentes. Cita entidad, identificador, fecha, articulo o apartado y enlace oficial. No uses blogs comerciales como fundamento.

### Metodo adversarial

Analiza el documento como lo harian la SIC, un juez laboral o civil, el abogado del firmante, un investigador de una fuga y un perito digital. Busca obligaciones abiertas, sanciones desproporcionadas, ausencia de alcance, aceptaciones en bloque, prueba insuficiente, autorizaciones encubiertas para tratar datos del propio firmante y contradicciones con los documentos 1 a 5.

No apruebes frases generales como "funciones autorizadas", "de inmediato", "permitire la verificacion" o "medidas legales" sin definir responsable, canal, evento, alcance y evidencia. Tampoco permitas que el texto revele secretos tecnicos, topologias o credenciales.

### Cuestiones obligatorias

1. ¿Distingue datos personales, datos sensibles, datos de menores, informacion publica y otra informacion confidencial?
2. ¿Individualiza el acceso autorizado por base, finalidad, operacion y periodo?
3. ¿Prohibe consulta por curiosidad, descarga, captura, fotografia, copia local, envio a cuentas personales, mensajeria no autorizada, uso de USB y tratamiento para fines propios?
4. ¿Regula cuentas individuales, credenciales, MFA, pantalla, papel, trabajo remoto, exportaciones y dispositivos?
5. ¿Obliga a reportar incidentes por el canal del Documento 5 y preserva evidencia sin investigar por cuenta propia?
6. ¿Regula cambios de rol y terminacion: revocacion de accesos, devolucion, eliminacion de copias y certificacion?
7. ¿Mantiene indefinidamente la reserva de datos personales despues del vinculo, sin imponer una perpetuidad indiscriminada a toda clase de informacion?
8. ¿Evita utilizar el compromiso como autorizacion del firmante para tratar sus propios datos?
9. ¿Define consecuencias segun el tipo de vinculo y debido proceso, sin sanciones inventadas?
10. ¿Incluye instrucciones reforzadas para biometria, videovigilancia, sensibles, menores y porteria solo cuando apliquen?
11. ¿La firma electronica prueba identidad, texto exacto, voluntad, fecha, canal, version y hash?
12. ¿Que debe bloquear el aplicativo antes de activar o mantener el acceso del firmante?

### Entregable obligatorio

Entrega exactamente estas secciones:

A. DICTAMEN: APROBADO, APROBADO CONDICIONADO o NO APROBADO; riesgo critico/alto/medio/bajo y cinco razones.

B. ALCANCE Y SUPUESTOS: firmantes cubiertos, hechos recibidos, hechos no verificados, documentos faltantes y limites.

C. MATRIZ DE HALLAZGOS: numero, severidad, seccion, texto cuestionado, defecto, riesgo, fuente oficial exacta y reemplazo listo para usar.

D. MATRIZ DE OBLIGACIONES POR ROL: Administrador, OPD, empleado administrativo, Consejo, porteria/vigilancia, aseo, TI, contratista, temporal y tercero individual. Indica bases, operaciones, prohibiciones, evidencia y condicion de baja.

E. MATRIZ DE CANALES Y SOPORTES: aplicativo, correo, papel, CCTV, biometria, exportaciones, dispositivos, trabajo remoto y mensajeria.

F. PROTOCOLO DE ALTA, CAMBIO Y BAJA: solicitud, autorizacion, firma, induccion, activacion, recertificacion, cambio de rol, suspension, terminacion, devolucion y certificacion.

G. EVALUACION PROBATORIA: identidad, capacidad, texto visto, granularidad, version, hash, fecha/hora, IP y navegador cuando proceda, firma electronica, entrega de copia, revocacion de acceso e inmutabilidad.

H. REGLAS CONDICIONALES: [[SI_CONFIG:PORTERIA]], [[SI_CONFIG:VIDEOVIGILANCIA]], [[SI_CONFIG:BIOMETRIA]], [[SI_CONFIG:DATOS_SENSIBLES]], [[SI_CONFIG:MENORES]], [[SI_CONFIG:TRABAJO_REMOTO]] y otras necesarias. Verifica balance de todos los bloques.

I. DOCUMENTO MAESTRO CORREGIDO: texto completo y autosuficiente listo para plantilla PHP. Usa variables controladas como [RESPONSABLE], [FIRMANTE], [DOCUMENTO], [TIPO_VINCULO], [ROL], [BASES_AUTORIZADAS], [FINALIDADES_AUTORIZADAS], [OPERACIONES_AUTORIZADAS], [VIGENCIA_ACCESO], [AUTORIZADOR], [CANAL_INCIDENTES], [VERSION_DOC5], [VERSION], [FECHA] y [HASH_INSTANCIA]. No dejes espacios abiertos que permitan autorizaciones globales.

J. REGLAS DEL APLICATIVO: variable, tipo, obligatoriedad, validacion, dependencia y efecto; validaciones bloqueantes para activacion de cuentas, cambio de rol, recertificacion y baja; generacion individual, firma, entrega y hash.

K. PRUEBAS DE ACEPTACION: minimo 30 Given/When/Then, incluyendo negativos, acceso entre copropiedades, cuenta compartida, ausencia de firma/induccion, cambio de rol, terminacion, copia local, incidentes, bloques condicionales, alteracion de instancia y firma electronica.

L. COHERENCIA DOCUMENTAL Y FUENTES: matriz contra documentos 1 a 5. Marca "NO VERIFICABLE SIN DOCUMENTO" cuando no tengas el texto. Lista solo fuentes oficiales usadas y fecha de consulta.

### Reglas de redaccion

- No suavices hallazgos por conveniencia comercial.
- Distingue obligacion legal, instruccion SIC, recomendacion tecnica y regla interna.
- El deber de reserva de datos personales no puede terminar despues de un numero de anos.
- No declares confidencial toda informacion de manera indiscriminada; clasifica y limita el alcance.
- No impongas al firmante investigar incidentes ni contactar directamente a Titulares o a la SIC; debe reportar internamente.
- No incluyas secretos, credenciales, rutas o configuraciones explotables.
- No uses este documento para obtener autorizacion de tratamiento del firmante.
- Si es insuficiente, entrega igualmente el documento completo corregido.

## BLOQUE 2 - DOCUMENTO 6 ACTUAL DEL APLICATIVO

**[RESPONSABLE]**

# COMPROMISO DE CONFIDENCIALIDAD Y USO AUTORIZADO

Documento controlado. La copia valida es la version aprobada en el sistema.

Yo, **[NOMBRE]**, identificado(a) con **[DOCUMENTO]**, me obligo a tratar los datos personales a los que acceda exclusivamente para las funciones autorizadas por [RESPONSABLE].

Me comprometo a no consultar, copiar, descargar, divulgar, alterar o eliminar informacion fuera de mis funciones; custodiar credenciales y soportes; informar incidentes de inmediato; seguir las instrucciones del Responsable; y conservar la confidencialidad aun despues de terminar mi vinculacion.

Al finalizar el acceso devolvere o eliminare las copias bajo mi control y permitire la verificacion correspondiente. El incumplimiento podra generar medidas contractuales, disciplinarias y legales.

**Rol y alcance autorizado:** ____________________

**Firma:** ____________________  **Fecha:** ____________________

---

Canal de proteccion de datos: [CORREO_DE_PRIVACIDAD] | [TELEFONO]

## Fuentes iniciales para contrastar, no limitativas

- Ley 1581 de 2012: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981
- SIC sobre prohibicion de limitar temporalmente la reserva: https://sedeelectronica.sic.gov.co/publicaciones/boletin-juridico/boletin/son-contrarios-la-ley-1581-de-2012-los-acuerdos-de-confidencialidad-que-fijen-un-tiempo-para-mantener-la
- SIC sobre manuales internos: https://sedeelectronica.sic.gov.co/publicaciones/boletin-juridico/concepto/diferencia-entre-politica-de-tratamiento-de-datos-y-manuales-internos-de-tratamiento-de-datos
- Guia SIC para el Oficial de Proteccion de Datos: https://sedeelectronica.sic.gov.co/publicaciones/boletin-juridico/boletin/compartimos-en-esta-edicion-la-publicacion-de-la-guia-para-el-oficial-de-proteccion-de-datos-personales
