# Documento 5 - paquete de revision juridica adversarial

## BLOQUE 1 - PROMPT PARA CLAUDE FABLE

Actua como abogado colombiano senior, auditor adversarial de cumplimiento y especialista en proteccion de datos personales, seguridad de la informacion y propiedad horizontal. Debes revisar exclusivamente el DOCUMENTO 5, titulado "Manual Interno de Seguridad de la Informacion Personal", que aparece despues de este prompt. Inicia una evaluacion independiente: no uses recuerdos, conclusiones ni aprobaciones de conversaciones anteriores.

### Contexto verificable

- El Responsable es una copropiedad sometida al regimen colombiano de propiedad horizontal.
- El manual forma parte de un sistema documental de siete documentos maestros versionados y sellados con SHA-256.
- Los documentos 1 a 4 son: Politica de Tratamiento, Aviso de Privacidad, Autorizacion y Procedimiento de Consultas, Reclamos, Rectificacion, Actualizacion, Revocatoria y Supresion.
- El aplicativo usa PHP/CodeIgniter, MySQL, segregacion por cliente, roles, auditoria, expedientes append-only, PDF, correo por SendGrid y una integracion restringida con OpenAI para revisar inventarios sin enviar identidades de titulares.
- Existen bases de residentes, propietarios, visitantes, proveedores, empleados u otras que cada copropiedad inventarie. Pueden existir videovigilancia, biometria, datos sensibles y datos de menores solo cuando la configuracion real lo indique.
- No des por implementado ningun control por el solo hecho de que el texto lo mencione. Separa: control juridicamente exigible, buena practica, control declarado, control probado y brecha.

### Marco que debes verificar a la fecha de tu respuesta

Consulta fuentes primarias y oficiales vigentes. Como minimo contrasta:

1. Constitucion Politica, articulo 15.
2. Ley 1581 de 2012, especialmente principios de seguridad y confidencialidad y deberes de Responsables y Encargados.
3. Decreto 1074 de 2015, Capitulo 25, incluida responsabilidad demostrada y politicas internas efectivas.
4. Instrucciones, guias y decisiones vigentes de la Superintendencia de Industria y Comercio sobre responsabilidad demostrada, gestion de incidentes, propiedad horizontal, videovigilancia, biometria, Encargados y Oficial de Proteccion de Datos.
5. Reglas vigentes sobre reporte de incidentes o violaciones de seguridad al RNBD, solo cuando resulten aplicables.
6. Normas adicionales realmente aplicables a la copropiedad. No conviertas ISO/IEC 27001, NIST, CIS Controls u otros estandares voluntarios en ley; usalos solo como criterio tecnico y marca esa naturaleza.

Verifica si hubo reformas, circulares, resoluciones o jurisprudencia posteriores. Cita enlace oficial, entidad, identificador, fecha y articulo o apartado exacto. No uses blogs comerciales como fundamento.

### Metodo adversarial obligatorio

Analiza el texto como lo harian simultaneamente: la Delegatura para la Proteccion de Datos Personales de la SIC, un perito forense despues de una fuga, el abogado de un Titular afectado, el Consejo de Administracion y un auditor de sistemas. Busca obligaciones vagas, imposibles de probar, controles sin propietario, alcance o frecuencia, contradicciones, tratamientos omitidos y afirmaciones tecnicas que el aplicativo no pueda acreditar.

No apruebes lenguaje aspiracional. Expresiones como "se aplicara", "autenticacion robusta", "archivo seguro", "revision periodica", "sin demora" o "destruccion segura" deben convertirse en reglas operativas verificables o identificarse como defecto.

### Preguntas que debes resolver

1. ¿El documento es un verdadero manual interno ejecutable o una declaracion general?
2. ¿Define alcance, activos, soportes, sedes, terceros, usuarios, ciclo de vida y clasificacion de informacion?
3. ¿Asigna gobierno RACI: Consejo, Administracion, Oficial de Proteccion, usuarios, TI, seguridad fisica y Encargados?
4. ¿Incluye evaluacion periodica de riesgos y controles proporcionales a datos sensibles, menores, biometria y videovigilancia?
5. ¿Regula altas, cambios, bajas, minimo privilegio, MFA, revisiones de acceso, cuentas compartidas, sesiones y segregacion?
6. ¿Regula contrasenas sin imponer reglas tecnicamente obsoletas? Distingue requisitos legales de recomendaciones tecnicas actuales.
7. ¿Cubre cifrado en transito y reposo, llaves, secretos, registros, vulnerabilidades, parches, malware, desarrollo seguro, ambientes y exportaciones?
8. ¿Cubre documentos fisicos, porteria, recepcion, visitantes, impresiones, prestamos, llaves, destruccion y trabajo remoto?
9. ¿Define copias de seguridad, inmutabilidad, cifrado, retencion, pruebas de restauracion y filtro obligatorio de exclusiones para no reactivar datos suprimidos?
10. ¿Define gestion de incidentes de extremo a extremo: deteccion, contencion, preservacion, severidad, investigacion, registro, decision de notificar, responsables, tiempos, RNBD/SIC cuando aplique, Titulares cuando proceda, recuperacion y lecciones aprendidas?
11. ¿Regula Encargados y subencargados, debida diligencia, contrato, instrucciones, incidentes, devolucion/supresion y evidencia?
12. ¿Cubre conservacion, bloqueo, supresion, anonimizacion irreversible y destruccion por tipo de soporte?
13. ¿Define capacitacion, pruebas, indicadores, auditoria, excepciones, planes correctivos, revision anual y gestion de cambios?
14. ¿Es coherente con los documentos 1 a 4? Si no tienes sus textos completos, identifica la comprobacion como pendiente; no inventes coherencia.
15. ¿Que controles debe bloquear el aplicativo antes de permitir aprobar este documento?

### Entregable obligatorio

Entrega exactamente estas secciones:

A. DICTAMEN: APROBADO, APROBADO CONDICIONADO o NO APROBADO; riesgo critico/alto/medio/bajo y cinco razones principales.

B. ALCANCE Y SUPUESTOS: hechos recibidos, hechos no verificados, documentos faltantes y limites del concepto.

C. MATRIZ DE HALLAZGOS: numero, severidad, seccion, texto cuestionado, defecto, riesgo juridico/operativo, fuente oficial exacta y texto de reemplazo listo para usar.

D. MATRIZ DE CONTROLES: dominio, riesgo, control preventivo/detectivo/correctivo, responsable, frecuencia o evento, evidencia, indicador, umbral, excepcion y accion por incumplimiento.

E. RACI: organo de administracion, Administrador, Oficial de Proteccion de Datos, TI/proveedor, seguridad/porteria, usuarios autorizados, Encargados y Consejo de Administracion.

F. PROTOCOLO DE INCIDENTES: flujo completo, clasificacion, tiempos internos concretos, decisiones externas condicionadas a la norma aplicable, evidencias y formato minimo del registro.

G. REGLAS CONDICIONALES: texto aplicable solo si hay videovigilancia, biometria, menores, datos sensibles, nube, trabajo remoto, soportes fisicos, SendGrid, OpenAI u otros Encargados. No dejes bloques inaplicables en la version final.

H. DOCUMENTO MAESTRO CORREGIDO: texto completo, autosuficiente y listo para convertirse en plantilla PHP. Usa variables [RESPONSABLE], [NIT], [DOMICILIO], [CANAL], [OFICIAL], [VERSION], [FECHA], [FRECUENCIA], etc. Marca los bloques condicionales con [[SI_CONFIG:CAMPO]]...[[FIN_SI]]. No uses finalidades abiertas ni datos ficticios.

I. REGLAS DEL APLICATIVO: inventario de variables, tipo, obligatoriedad, validacion, dependencia, fuente y efecto. Incluye validaciones bloqueantes para aprobacion, versionado, hash de la instancia, evidencias, alertas, revisiones, incidentes y restauraciones.

J. PRUEBAS DE ACEPTACION: minimo 30 casos Given/When/Then, con casos negativos, configuraciones condicionales, permisos, Encargados, incidentes, eliminacion, restauracion, trazabilidad e inmutabilidad.

K. COHERENCIA DOCUMENTAL: matriz contra Politica, Aviso, Autorizacion y Procedimiento; marca "NO VERIFICABLE SIN DOCUMENTO" cuando corresponda.

L. FUENTES: solo enlaces oficiales directamente utilizados, con fecha de consulta.

### Reglas de redaccion

- No suavices hallazgos por conveniencia comercial.
- No afirmes cumplimiento porque exista una plantilla, hash o campo.
- No inventes plazos legales. Cuando la norma no fije uno, propone un SLA interno y etiquetalo como tal.
- Distingue obligacion legal, instruccion de autoridad, recomendacion tecnica y decision interna.
- Conserva datos probatorios minimos; evita que evidencias o logs acumulen datos personales innecesarios.
- No redactes secretos, contrasenas, topologias, rutas internas o configuraciones explotables dentro del manual distribuible.
- Si el documento es insuficiente, entrega igualmente la version completa corregida.

## BLOQUE 2 - DOCUMENTO 5 ACTUAL DEL APLICATIVO

**[RESPONSABLE]**

# MANUAL INTERNO DE SEGURIDAD DE LA INFORMACION PERSONAL

**Version:** [VERSION]
**Hash SHA-256:** [HASH_DE_LA_INSTANCIA_APROBADA]

Documento controlado. La copia valida es la version aprobada en el sistema.

## 1. Gobierno y acceso

El responsable operativo es [OFICIAL_DE_PROTECCION_DE_DATOS]. El acceso se concedera por necesidad funcional, con cuentas individuales, privilegio minimo, revision periodica y retiro inmediato al terminar la relacion.

## 2. Inventario sujeto a controles

El sistema insertara el inventario vigente de bases de datos en una tabla con las columnas: Base; Titulares y datos; Finalidades; Conservacion.

Si no existe inventario aprobado, el documento mostrara: "El inventario debe completarse y aprobarse antes de publicar este documento".

## 3. Controles administrativos

Se mantendran compromisos de confidencialidad, capacitacion, inventario actualizado, evaluacion de Encargados, gestion de cambios, conservacion definida y procedimientos de respuesta a derechos e incidentes.

## 4. Controles tecnicos

Se aplicaran autenticacion robusta, segregacion por cliente, cifrado en transito, copias de seguridad verificadas, registros de auditoria, actualizaciones, proteccion contra codigo malicioso y restricciones de exportacion. Los secretos no se almacenaran en el codigo fuente.

## 5. Controles fisicos

Los documentos en papel y dispositivos estaran bajo custodia, con acceso restringido, archivo seguro, control de prestamo y destruccion que impida reconstruccion.

## 6. Incidentes

Todo evento de perdida, alteracion, acceso o divulgacion no autorizada sera contenido, registrado, investigado y evaluado. Se preservaran evidencias, se corregira la causa y se realizaran las comunicaciones exigibles.

## 7. Eliminacion

La eliminacion sera autorizada, verificable y proporcional al soporte. Los respaldos seguiran una rotacion documentada y los medios retirados seran borrados o destruidos de forma segura.

---

Canal de proteccion de datos: [CORREO_DE_PRIVACIDAD] | [TELEFONO]

## Fuentes iniciales para contrastar, no limitativas

- Ley 1581 de 2012: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981
- Concepto SIC sobre manuales internos: https://sedeelectronica.sic.gov.co/publicaciones/boletin-juridico/concepto/diferencia-entre-politica-de-tratamiento-de-datos-y-manuales-internos-de-tratamiento-de-datos
- Guia SIC sobre Oficial de Proteccion de Datos: https://sedeelectronica.sic.gov.co/publicaciones/boletin-juridico/boletin/compartimos-en-esta-edicion-la-publicacion-de-la-guia-para-el-oficial-de-proteccion-de-datos-personales
- Guia SIC para propiedad horizontal: https://www.sic.gov.co/slider/superindustria-lanza-la-gu%C3%ADa-para-el-tratamiento-de-datos-personales-en-la-propiedad-horizontal
