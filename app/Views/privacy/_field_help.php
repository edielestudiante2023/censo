<?php
$privacyFieldHelp = [
    'responsable_nombre' => 'Nombre juridico completo de la copropiedad que decide para que y como se tratan los datos. Ejemplo: Conjunto Residencial Los Cedros P.H.',
    'responsable_documento' => 'NIT del Responsable, incluido el digito de verificacion cuando corresponda. Ejemplo: 900123456-7.',
    'responsable_ciudad' => 'Municipio y departamento del domicilio principal del Responsable. Ejemplo: Bogota D.C.',
    'responsable_direccion' => 'Direccion fisica donde el Titular puede presentar comunicaciones o consultar la politica.',
    'canal_email' => 'Correo institucional vigilado que recibira consultas, reclamos, revocatorias y solicitudes de supresion.',
    'canal_telefono' => 'Numero institucional para orientar al Titular. Incluya indicativo o extension si aplica.',
    'oficial_nombre' => 'Nombre de la persona que coordina diariamente el programa. No implica crear un cargo legal nuevo.',
    'oficial_cargo' => 'Cargo o vinculacion del responsable operativo. Ejemplo: Administradora de la copropiedad.',
    'area_responsable' => 'Area que recibe, clasifica y responde derechos. Ejemplo: Administracion de la copropiedad.',
    'horario_atencion' => 'Dias y horas reales de atencion del canal. Ejemplo: lunes a viernes, 8:00 a.m. a 5:00 p.m.',
    'organo_aprobacion' => 'Organo que aprobo el programa o documento. Ejemplo: Consejo de Administracion, Acta 014.',
    'medio_publicacion' => 'Canales institucionales usados para socializar. Ejemplo: cartelera, correo, portal web y asamblea.',
    'fecha_aprobacion' => 'Fecha del acta o decision institucional que aprobo la version.',
    'fecha_vigencia' => 'Fecha desde la cual la politica y el programa empiezan a aplicarse.',
    'url_politica' => 'Direccion publica y estable donde cualquier Titular puede consultar la politica vigente.',
    'housing_tower_prefix' => 'Texto que identifica cada torre antes de su numero. Ejemplo: Torre genera Torre 1, Torre 2 y asi sucesivamente.',
    'housing_tower_from' => 'Primer numero de torre que se generara en esta ejecucion. Ejemplo: 1.',
    'housing_tower_to' => 'Ultimo numero de torre que se generara. Para 19 torres numeradas desde 1, escriba 19.',
    'housing_floors' => 'Cantidad de pisos habitacionales que tiene cada torre incluida en este rango.',
    'housing_units_per_floor' => 'Cantidad de apartamentos que se generaran en cada piso de cada torre.',
    'housing_unit_from' => 'Primer consecutivo de apartamento dentro de cada piso. Use 1 para generar 101, 102 y siguientes en el piso 1.',
    'housing_house_prefix' => 'Texto que aparecera antes del numero de cada casa. Ejemplo: Casa seguido de un espacio.',
    'housing_house_from' => 'Primer numero de casa que se generara en esta ejecucion.',
    'housing_house_to' => 'Ultimo numero de casa que se generara en esta ejecucion.',
    'housing_house_padding' => 'Cantidad total de digitos del numero. Use 0 para Casa 1 o 3 para Casa 001.',
    'backup_rotation_days' => 'Numero maximo de dias durante los cuales un dato suprimido podria permanecer en respaldos antes de su purga definitiva.',
    'request_cutoff_time' => 'Hora diaria despues de la cual una solicitud se entiende recibida el siguiente dia habil para computar terminos.',
    'request_file_years' => 'Anios durante los cuales se conservara el expediente probatorio de cada consulta o reclamo. El minimo configurado es cinco.',
    'privacy_app_name' => 'Nombre con el que se identificara este modulo en documentos y evidencias.',
    'sendgrid_transmission_confirmed' => 'Marque solo cuando SendGrid este inventariado como Encargado internacional y exista acuerdo o garantia contractual verificable.',
    'estado' => 'Seleccione la situacion operativa actual del registro. Use Activo solamente cuando la informacion haya sido revisada.',
    'usa_videovigilancia' => 'Marque si existen camaras que captan personas identificadas o identificables en zonas de la copropiedad.',
    'graba_videovigilancia' => 'Marque si las imagenes de las camaras se almacenan y pueden consultarse posteriormente.',
    'video_identificacion_biometrica' => 'Marque si el video se usa para reconocer o autenticar rostros, no por la simple existencia de camaras.',
    'usa_biometria' => 'Marque si se usan huellas, reconocimiento facial, iris, voz u otro identificador biometrico.',
    'publica_morosos' => 'Marque si se informa publicamente el estado de obligaciones en mora de unidades o personas.',
    'obligado_rnbd' => 'Marque solo despues de evaluar si el Responsable debe inscribir bases en el Registro Nacional de Bases de Datos.',
    'plazo_grabaciones_dias' => 'Dias reales que permanecen almacenadas las grabaciones antes de su sobrescritura o eliminacion.',
    'justificacion_retencion_video' => 'Motivo concreto y documentado para conservar grabaciones por mas de 30 dias.',
    'tipo_biometria' => 'Tecnologia y dato usado. Ejemplo: plantilla matematica de huella para control de acceso.',
    'finalidad_biometria' => 'Objetivo exclusivo que hace necesario el tratamiento biometrico. Evite finalidades generales.',
    'alternativa_biometrica' => 'Mecanismo de ingreso equivalente que no exige biometria. Ejemplo: tarjeta, codigo o validacion en porteria.',
    'plazo_supresion_biometria_dias' => 'Dias para eliminar la plantilla biometrica cuando termina la finalidad o el Titular retira la autorizacion.',
    'zonas_vigiladas' => 'Enumere las areas cubiertas por camaras. Ejemplo: accesos, recepcion, parqueaderos y ascensores.',
    'encargados_publicos' => 'Nombres y servicios de Encargados informados al Titular. Si no existen, declare expresamente que no existen.',
    'canal_entrega_copia' => 'Medio por el cual el Titular recibe su autorizacion firmada. Ejemplo: correo electronico verificado.',
    'transmision_internacional' => 'Marque si un proveedor en otro pais trata datos siguiendo instrucciones de la copropiedad.',
    'transferencia_internacional' => 'Marque si otro Responsable ubicado fuera de Colombia recibe datos para decidir sus propios fines o medios.',
    'transferencia_requiere_autorizacion' => 'Marque cuando la garantia usada sea la autorizacion expresa del Titular.',
    'transferencia_nacional' => 'Marque si se entregan datos a otro Responsable en Colombia para finalidades propias.',
    'paises_transmision' => 'Paises donde operan Encargados o subencargados. Separe varios valores con coma.',
    'paises_transferencia' => 'Paises de los Responsables destinatarios de transferencias. Separe varios valores con coma.',
    'receptor_exterior' => 'Nombre juridico e identificacion del Responsable que recibira datos fuera de Colombia.',
    'garantia_transferencia' => 'Mecanismo juridico que permite la transferencia internacional. Debe coincidir con la evidencia disponible.',
    'garantia_transferencia_detalle' => 'Numero, fecha y referencia verificable de la declaracion, excepcion o autorizacion aplicable.',
    'responsable_destinatario' => 'Nombre e identificacion del otro Responsable en Colombia que recibira los datos.',
    'finalidad_transferencia' => 'Finalidad concreta para la cual el Responsable destinatario usara los datos.',
    'security_acta' => 'Numero, fecha y ubicacion del acta que aprobo el manual interno de seguridad.',
    'security_administrador' => 'Nombre y cargo de quien responde por ejecutar los controles del manual.',
    'security_opd_designacion' => 'Acta, contrato o comunicacion que asigna la funcion de proteccion de datos.',
    'security_opd_reporte' => 'Organo o cargo que recibe informes y escalaciones del responsable de privacidad.',
    'security_proveedor_ti' => 'Nombre del area interna o proveedor que administra sistemas, respaldos y accesos.',
    'security_sedes' => 'Lugares fisicos donde se almacenan o consultan datos personales.',
    'security_archivo_ubicacion' => 'Ubicacion exacta y controlada del archivo fisico. Ejemplo: gabinete 2 de Administracion.',
    'security_archivo_custodio' => 'Persona o cargo responsable de llaves, prestamos y devolucion del archivo fisico.',
    'security_canal_incidentes' => 'Correo, telefono o mesa de ayuda para reportar perdidas, accesos indebidos o filtraciones.',
    'security_gestor_secretos' => 'Herramienta o procedimiento usado para custodiar claves. No escriba contrasenas en este campo.',
    'security_contenedor_destruccion' => 'Mecanismo de destruccion segura de papel y soportes. Ejemplo: contenedor sellado con proveedor certificado.',
    'security_retencion_logs_meses' => 'Meses durante los cuales se conservan registros de acceso y eventos para investigacion.',
    'security_retencion_backups_dias' => 'Dias de permanencia de respaldos antes de rotacion o destruccion segura.',
    'security_revision_riesgos_meses' => 'Frecuencia en meses para actualizar la matriz de riesgos de privacidad y seguridad.',
    'security_revision_accesos_meses' => 'Frecuencia para confirmar que cada usuario conserva solo los permisos necesarios.',
    'security_revision_inventario_meses' => 'Frecuencia para verificar bases, finalidades, ubicaciones y terceros.',
    'security_prueba_restauracion_meses' => 'Frecuencia para restaurar un respaldo de prueba y comprobar integridad y exclusiones.',
    'security_capacitacion_meses' => 'Frecuencia maxima entre capacitaciones obligatorias del personal con acceso.',
    'security_evaluacion_encargados_meses' => 'Frecuencia para revisar contratos, seguridad e incidentes de cada Encargado.',
    'security_rotacion_secretos_meses' => 'Frecuencia para cambiar claves tecnicas, tokens y secretos de integracion.',
    'security_prueba_ia_meses' => 'Frecuencia para probar que el filtro impide enviar identidades a la IA.',
    'security_timeout_minutos' => 'Minutos de inactividad antes de cerrar automaticamente una sesion.',
    'security_max_intentos' => 'Cantidad de intentos fallidos permitidos antes de bloquear temporalmente el acceso.',
    'security_min_caracteres' => 'Longitud minima exigida para contrasenas. No ingrese una contrasena real.',
    'security_umbral_exportacion' => 'Numero de registros a partir del cual una exportacion requiere control o revision adicional.',
    'security_nube_proveedor' => 'Proveedor que almacena o procesa informacion en nube. Ejemplo: DigitalOcean.',
    'security_nube_pais' => 'Pais o paises donde el proveedor aloja o procesa los datos.',
    'security_cctv_ubicacion' => 'Sistema, equipo o cuarto donde se almacenan y consultan grabaciones.',
    'security_cctv_roles' => 'Cargos autorizados para consultar o extraer grabaciones, no nombres genericos como todos.',
    'security_soportes_fisicos' => 'Marque si existen carpetas, formularios, discos u otros soportes fisicos con datos.',
    'security_trabajo_remoto' => 'Marque si personal o contratistas consultan datos fuera de las instalaciones.',
    'security_nube' => 'Marque si cualquier base, copia o servicio se encuentra alojado en nube.',
    'nombre' => 'Nombre claro y unico del registro. Para una base, use el colectivo tratado; para un tercero, use su razon social.',
    'codigo' => 'Identificador corto sin espacios para reconocer la base. Ejemplo: RESIDENTES o CCTV.',
    'medio' => 'Seleccione si la base existe en soporte digital, fisico o en ambos.',
    'tipos_titular' => 'Un tipo de persona por linea. Ejemplo: propietarios, residentes, visitantes y empleados.',
    'categorias_datos' => 'Categorias presentes, una por linea. Ejemplo: identificacion, contacto, vehiculos e imagen.',
    'finalidad_resumen' => 'Descripcion breve del uso principal de la base, sin formulas abiertas.',
    'responsable_interno' => 'Persona o cargo que custodia y mantiene actualizada esta base.',
    'ubicacion' => 'Sistema, archivo o lugar exacto donde se encuentra la informacion.',
    'soportes_ubicacion' => 'Detalle de servidores, carpetas, archivadores, aplicaciones y copias donde existe la base.',
    'retencion_meses' => 'Meses que se conserva la informacion despues de cumplir la finalidad o terminar la relacion.',
    'origen_datos' => 'Indique quien entrega los datos y por que canal. Ejemplo: formulario del residente y porteria.',
    'fundamento' => 'Norma, autorizacion o situacion concreta que permite el tratamiento de esta base.',
    'criterio_eliminacion' => 'Evento y regla que ordenan eliminar o anonimizar. Ejemplo: 30 dias despues de finalizar la residencia, salvo obligacion legal.',
    'medidas_seguridad' => 'Controles aplicados a esta base: permisos, cifrado, gabinetes, respaldos, registros y destruccion.',
    'rnbd_aplica' => 'Resultado de la evaluacion de inscripcion de esta base en RNBD. Use Por evaluar mientras no exista decision.',
    'datos_sensibles' => 'Marque si la base contiene salud, biometria, orientacion, creencias u otra categoria sensible.',
    'datos_biometricos' => 'Marque si contiene huella, plantilla facial, voz u otro dato biometrico.',
    'datos_menores' => 'Marque si contiene informacion de personas menores de 18 anios.',
    'base_juridica_tipo' => 'Indique si la finalidad depende de autorizacion o de una excepcion legal documentada.',
    'base_juridica_detalle' => 'Cite la norma, articulo, orden o evidencia exacta. No escriba solamente obligacion legal.',
    'categorias_datos_finalidad' => 'Datos estrictamente necesarios para esta finalidad, una categoria por linea.',
    'consecuencia_negativa' => 'Explique que ocurre si el Titular niega una finalidad opcional, sin lenguaje disuasivo.',
    'es_opcional' => 'Marque cuando negar esta finalidad no impide recibir el servicio principal.',
    'requiere_consentimiento_explicito' => 'Marque cuando la finalidad trata datos sensibles y requiere una decision separada.',
    'dato' => 'Nombre de un solo dato sensible. Ejemplo: plantilla de huella o diagnostico de salud.',
    'finalidad_exclusiva' => 'Uso concreto y exclusivo de ese dato sensible, separado de las finalidades comunes.',
    'contenido_html' => 'HTML de la instancia documental en revision. Edite solo si conoce la estructura; guardar recalcula el SHA-256.',
    'canal' => 'Medio real usado para publicar o comunicar. Ejemplo: cartelera, correo, web o formulario.',
    'evidencia' => 'Referencia verificable del soporte. Indique acta, ticket, ruta, folio, hash o documento que permita comprobar la actuacion.',
    'tipo' => 'Seleccione o escriba la clasificacion que corresponde al expediente actual.',
    'calidad_solicitante' => 'Relacion de quien presenta la solicitud con el Titular: Titular, representante, causahabiente o representante de menor.',
    'titular_nombre' => 'Nombre completo del Titular cuyos datos son objeto de la solicitud.',
    'titular_documento' => 'Documento del Titular usado para localizar registros y aplicar exclusiones.',
    'titular_email' => 'Correo verificado al que se enviaran acuse, requerimientos y respuesta.',
    'solicitud_texto' => 'Peticion concreta. En reclamos incluya hechos; en consultas basta indicar la informacion solicitada.',
    'nuevo_tipo' => 'Nueva clasificacion juridica de la solicitud despues de revisar su contenido real.',
    'reclasificacion_motivo' => 'Explique por que el contenido corresponde al nuevo tipo y deje trazabilidad de la decision.',
    'traslado_destinatario' => 'Nombre y canal del Responsable competente al que se traslada el reclamo.',
    'subsanacion_detalle' => 'Indique exactamente que informacion o soporte falta para completar el reclamo.',
    'reclamo_motivo' => 'Texto breve que se insertara como leyenda de reclamo en tramite en las bases afectadas.',
    'prorroga_motivo' => 'Circunstancia real que impide responder en el plazo inicial. Debe comunicarse antes del vencimiento.',
    'accion' => 'Accion ejecutada sobre la base o instruida al Encargado. Debe coincidir con la evidencia.',
    'detalle' => 'Describa de forma verificable que se hizo, cuando, por quien y sobre que sistema o expediente.',
    'valor_anterior' => 'Valor que se corrige. El sistema conserva solamente su hash para reducir exposicion.',
    'valor_nuevo' => 'Dato corregido o actualizado que debe quedar en la base.',
    'fuente_correccion' => 'Documento o fuente confiable que demuestra el valor correcto.',
    'bloqueado_hasta' => 'Fecha limite de conservacion o bloqueo antes de ejecutar la supresion programada.',
    'respuesta_detalle' => 'Respuesta recibida del Encargado y fecha de confirmacion de la accion ordenada.',
    'resultado' => 'Resultado final que se comunicara al Titular: total, parcial, bloqueo, negada o informacion entregada.',
    'respuesta_texto' => 'Respuesta completa, clara y congruente con las acciones y evidencias del expediente.',
    'fundamento_conservacion' => 'Norma u obligacion concreta que impide suprimir una parte de la informacion.',
    'datos_conservados' => 'Categorias y registros que continuaran conservados pese a la solicitud.',
    'conservacion_hasta' => 'Fecha en que termina la obligacion de conservar y debe revisarse la supresion.',
    'vencimiento_causa' => 'Causa documentada del retraso cuando la respuesta se cierra despues del termino.',
    'pais' => 'Pais contractual y operativo de la entidad o subencargado.',
    'contacto_email' => 'Correo del tercero para asuntos contractuales, de privacidad e incidentes.',
    'representante_nombre' => 'Nombre completo de quien tiene facultad para firmar por la entidad.',
    'representante_documento' => 'Documento de identidad del representante que firmara la instancia.',
    'representante_email' => 'Correo individual que recibira el codigo y enlace de firma.',
    'representacion_evidencia' => 'Certificado, poder, acta o documento que demuestra facultades de representacion.',
    'facultades_verificadas' => 'Marque despues de revisar vigencia, identidad y alcance de las facultades.',
    'rol_accede_datos' => 'Marque si el tercero puede ver, almacenar, transportar o administrar datos personales.',
    'rol_decide_finalidades' => 'Marque si el tercero decide para que se usan los datos.',
    'rol_decide_medios' => 'Marque si decide medios esenciales y no solo herramientas tecnicas subordinadas.',
    'rol_obligacion_propia' => 'Marque si trata datos para cumplir una obligacion legal propia.',
    'rol_fines_propios' => 'Marque si reutiliza datos para objetivos distintos a las instrucciones de la copropiedad.',
    'rol_solo_instrucciones' => 'Marque si tambien existe una operacion claramente delimitada bajo instrucciones documentadas.',
    'operaciones_rol_dual' => 'Separe las operaciones en que actua como Encargado de aquellas en que es Responsable independiente.',
    'clasificacion_justificacion' => 'Explique el resultado del test material con hechos del contrato y del servicio.',
    'contrato_principal_ref' => 'Numero, fecha o codigo que identifica el contrato principal.',
    'contrato_principal_objeto' => 'Objeto contractual aprobado, sin reemplazarlo por una descripcion generica.',
    'servicio' => 'Describa actividades, sistemas, soporte y forma de acceso a datos con suficiente detalle.',
    'contrato_fecha' => 'Fecha de celebracion o inicio del contrato principal.',
    'contrato_vence' => 'Fecha final de vigencia contractual o de la autorizacion para tratar datos.',
    'contrato_evidencia' => 'Ubicacion, folio, hash o referencia del contrato firmado y sus anexos.',
    'bases[]' => 'Seleccione solamente las bases a las que este usuario o tercero necesita acceder.',
    'finalidades[]' => 'Seleccione las finalidades exactas autorizadas; no otorgue un alcance global por comodidad.',
    'titulares[]' => 'Marque los grupos de personas cuyos datos entran en el alcance contractual.',
    'categorias[]' => 'Marque las categorias de datos que el tercero realmente recibira o consultara.',
    'operaciones[]' => 'Marque solo las operaciones necesarias: consultar, registrar, actualizar, exportar, suprimir u otras listadas.',
    'sistemas' => 'Aplicaciones, carpetas, equipos y soportes usados por el tercero, separados por coma.',
    'paises' => 'Paises donde el tercero y sus subencargados almacenan o acceden a datos.',
    'nivel_riesgo' => 'Nivel inicial segun volumen, sensibilidad, menores, biometria, paises y operaciones.',
    'medidas[]' => 'Marque cada control cuya implementacion fue comprobada, no solo declarada por el proveedor.',
    'debida_diligencia_evidencia' => 'Referencia del informe, cuestionario, auditoria o soportes revisados antes de contratar.',
    'medidas_verificadas' => 'Marque despues de revisar evidencia de los controles declarados.',
    'canal_incidentes_probado' => 'Marque despues de realizar una prueba documentada del canal de reporte del Encargado.',
    'plazo_incidente_dias' => 'Dias habiles maximos para que el Encargado informe un incidente a la copropiedad. El sistema limita de uno a tres.',
    'logs_retencion_meses' => 'Meses que el Encargado conserva registros de acceso y seguridad.',
    'backup_rotacion_dias' => 'Dias para purgar datos eliminados de los respaldos del Encargado.',
    'rto_horas' => 'Horas maximas para restablecer el servicio despues de una interrupcion.',
    'rpo_horas' => 'Maximo de horas de informacion que podria perderse entre respaldos.',
    'seguro_evidencia' => 'Poliza, garantia o respaldo financiero exigido para riesgo medio o alto.',
    'datos' => 'Categorias concretas que tratara el subencargado, separadas por coma.',
    'verificacion_paises_at' => 'Fecha en que se verifico la lista y garantia aplicable a los paises involucrados.',
    'volumen_frecuencia' => 'Cantidad aproximada de registros y periodicidad del tratamiento. Ejemplo: 800 residentes, envio diario.',
    'perfiles_acceso' => 'Cargos o perfiles tecnicos del Encargado que podran acceder a los datos.',
    'contenido' => 'Instruccion concreta, fechada y limitada que el Encargado debe ejecutar.',
    'motivo' => 'Razon concreta y verificable que justifica la actuacion o terminacion.',
    'no_resucitar' => 'Confirme que los datos eliminados quedaron bloqueados para no reaparecer al restaurar respaldos.',
    'rol' => 'Funcion de gobierno o seguridad que se asigna a la persona o area.',
    'responsable' => 'Nombre de la persona, cargo o area que acepta y ejecuta esta responsabilidad.',
    'acto_designacion' => 'Acta, contrato o comunicacion que prueba la asignacion.',
    'vence_at' => 'Fecha hasta la cual la evidencia o control permanece vigente.',
    'severidad' => 'Clasificacion del impacto y urgencia del incidente segun el protocolo interno.',
    'fuente' => 'Persona, sistema, proveedor o alerta que detecto el incidente.',
    'detectado_at' => 'Fecha y hora del primer indicio tecnico del incidente.',
    'conocimiento_at' => 'Fecha y hora en que el area responsable conocio el incidente; desde aqui se controlan terminos.',
    'titulares_estimados' => 'Numero aproximado de personas afectadas, sustentado en la investigacion disponible.',
    'categorias_afectadas' => 'Datos comprometidos. Ejemplo: identificacion, contacto, imagen o biometria.',
    'investigacion' => 'Hallazgos, alcance, causa, sistemas y medidas de contencion ejecutadas.',
    'decision_reporte' => 'Decision sobre informar a la SIC, sustentada en la clasificacion y hechos.',
    'decision_motivo' => 'Fundamento tecnico y juridico de reportar o no reportar.',
    'sic_evidencia' => 'Numero de radicado, fecha y soporte del reporte presentado ante la SIC.',
    'titulares_comunicacion_motivo' => 'Texto enviado a Titulares o justificacion documentada para no comunicar.',
    'lecciones' => 'Causa raiz, mejoras, responsable y fecha de implementacion para evitar repeticion.',
    'usuario_id' => 'Usuario concreto para el que se genera el compromiso individual.',
    'tipo_documento' => 'Tipo del documento de identidad del firmante.',
    'numero_documento' => 'Numero de identidad del firmante que quedara en la instancia probatoria.',
    'tipo_vinculo' => 'Relacion real del firmante con la copropiedad: laboral, contratista, organo, temporal o proveedor.',
    'rol_operativo' => 'Funcion diaria que determina el acceso necesario a bases y operaciones.',
    'vigencia_hasta' => 'Fecha maxima del acceso. No debe superar la vinculacion ni doce meses sin recertificacion.',
    'alcance_total_justificacion' => 'Justificacion excepcional para autorizar todo el inventario. Deje vacio si el alcance esta limitado.',
    'cierre_motivo' => 'Causa de baja, cambio de rol o terminacion que obliga a revocar el acceso.',
    'devolucion_evidencia' => 'Constancia de devolucion, eliminacion o bloqueo de copias y credenciales.',
];
?>
<style>
    .field-help-icon{display:inline-grid;place-items:center;width:17px;height:17px;margin-left:6px;border:1px solid #8b98aa;border-radius:50%;color:#405168;background:#fff;font-size:.69rem;font-weight:800;line-height:1;vertical-align:middle;cursor:help}.field-help-icon:hover,.field-help-icon:focus{background:#172235;color:#fff;border-color:#172235;outline:none}.field-help-popover{position:fixed;z-index:1200;width:min(340px,calc(100vw - 24px));padding:11px 13px;border-radius:6px;background:#172235;color:#fff;box-shadow:0 12px 30px rgba(0,0,0,.28);font-size:.8rem;line-height:1.5;pointer-events:none}.field-help-popover[hidden]{display:none}.field-help-popover:after{content:'';position:absolute;top:-6px;left:var(--arrow-left,20px);width:12px;height:12px;background:#172235;transform:rotate(45deg)}.field-help-sr{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
</style>
<div class="field-help-popover" id="privacy-field-help-popover" role="tooltip" hidden></div>
<script>
(function(){
    var explicit=<?= json_encode($privacyFieldHelp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var popover=document.getElementById('privacy-field-help-popover');
    var activeIcon=null;
    function cleanLabel(label){return (label.childNodes.length?Array.from(label.childNodes).filter(function(node){return !(node.nodeType===1&&node.classList.contains('field-help-icon'))}).map(function(node){return node.textContent}).join(' '):label.textContent).replace(/\s+/g,' ').trim()}
    function fieldLabel(control){
        if(control.id){var byFor=document.querySelector('label[for="'+CSS.escape(control.id)+'"]');if(byFor)return byFor}
        var nested=control.closest('label');if(nested)return nested;
        var parent=control.parentElement;if(!parent)return null;
        var previous=control.previousElementSibling;
        while(previous){
            if(previous.tagName==='LABEL')return previous;
            if(['INPUT','SELECT','TEXTAREA'].includes(previous.tagName))break;
            previous=previous.previousElementSibling;
        }
        var labels=Array.from(parent.children).filter(function(child){return child.tagName==='LABEL'});
        return labels.length===1?labels[0]:null;
    }
    function contextFor(control){var box=control.closest('form,details,section,.list-row');if(!box)return 'esta seccion';var heading=box.querySelector('h3,h4,summary,strong');return heading?heading.textContent.replace(/\s+/g,' ').trim():'esta seccion'}
    function inferredHelp(control,labelText){
        var context=contextFor(control);var type=(control.getAttribute('type')||control.tagName).toLowerCase();
        if(type==='checkbox')return 'Marque "'+labelText+'" solo cuando la condicion exista y pueda demostrarse. Esta seleccion cambia validaciones, documentos o alcance operativo en '+context+'.';
        if(type==='date'||type==='datetime-local')return 'Indique la fecha'+(type==='datetime-local'?' y hora':'')+' exacta de "'+labelText+'" con base en un acta, contrato, evento o evidencia verificable.';
        if(type==='number')return 'Ingrese el valor numerico esperado para "'+labelText+'" respetando los limites mostrados. Debe coincidir con el procedimiento real de '+context+'.';
        if(type==='email')return 'Ingrese un correo institucional valido y vigilado para "'+labelText+'". Evite cuentas que nadie supervise.';
        if(type==='url')return 'Ingrese una URL publica completa, comenzando por https://, para "'+labelText+'".';
        if(control.tagName==='SELECT')return 'Seleccione la opcion que describe la situacion real de "'+labelText+'" en '+context+'. No elija una opcion solo para superar una validacion.';
        if(control.tagName==='TEXTAREA')return 'Describa "'+labelText+'" con hechos concretos: que, quien, donde, cuando y evidencia disponible dentro de '+context+'.';
        return 'Diligencie "'+labelText+'" con un valor concreto y verificable que corresponda a '+context+'. Evite textos genericos como por definir, varios u otros.';
    }
    function place(icon,text){
        popover.textContent=text;popover.hidden=false;var rect=icon.getBoundingClientRect();var width=popover.offsetWidth;var left=Math.max(12,Math.min(rect.left-(width/2)+(rect.width/2),window.innerWidth-width-12));var top=rect.bottom+9;if(top+popover.offsetHeight>window.innerHeight-12){top=Math.max(12,rect.top-popover.offsetHeight-9);popover.style.setProperty('--arrow-left',Math.max(12,Math.min(width-24,rect.left-left+2))+'px')}else{popover.style.setProperty('--arrow-left',Math.max(12,Math.min(width-24,rect.left-left+2))+'px')}popover.style.left=left+'px';popover.style.top=top+'px';activeIcon=icon;
    }
    function hide(){popover.hidden=true;activeIcon=null}
    var controls=Array.from(document.querySelectorAll('main .pane input,main .pane select,main .pane textarea')).filter(function(control){return (control.type||'').toLowerCase()!=='hidden'&&control.type!=='submit'&&control.type!=='button'});
    controls.forEach(function(control,index){
        var label=fieldLabel(control);if(!label)return;
        var labelText=cleanLabel(label);if(!labelText)return;
        var key=(control.name||'').replace(/\[\]$/,'[]');var help=explicit[key]||inferredHelp(control,labelText);
        control.title=help;control.dataset.fieldHelp='ready';
        var description=document.createElement('span');description.className='field-help-sr';description.id='field-help-description-'+index;description.textContent=help;label.parentNode.insertBefore(description,label.nextSibling);control.setAttribute('aria-describedby',description.id);
        if(label.querySelector('.field-help-icon'))return;
        var icon=document.createElement('span');icon.className='field-help-icon';icon.tabIndex=0;icon.setAttribute('role','button');icon.setAttribute('aria-label','Ayuda para '+labelText);icon.title=help;icon.textContent='?';label.appendChild(icon);
        icon.addEventListener('mouseenter',function(){place(icon,help)});icon.addEventListener('mouseleave',hide);icon.addEventListener('focus',function(){place(icon,help)});icon.addEventListener('blur',hide);
        icon.addEventListener('pointerdown',function(event){event.preventDefault();event.stopPropagation()});
        icon.addEventListener('click',function(event){event.preventDefault();event.stopPropagation();if(activeIcon===icon&&!popover.hidden){hide()}else{place(icon,help)}});
    });
    document.addEventListener('click',function(event){if(!event.target.closest('.field-help-icon'))hide()});window.addEventListener('scroll',hide,true);window.addEventListener('resize',hide);
})();
</script>
