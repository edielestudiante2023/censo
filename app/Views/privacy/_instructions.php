<?php
$privacyGuides = [
    'general' => [
        'eyebrow' => 'Ruta recomendada',
        'title' => 'Implementar el programa de principio a fin',
        'intro' => 'Use primero una copropiedad de prueba y datos ficticios. El orden importa porque varios documentos dependen de versiones publicadas previamente.',
        'before' => [
            'Tener definidos el responsable del tratamiento, el canal de privacidad y el responsable operativo.',
            'Contar con informacion real sobre bases, proveedores, sistemas, respaldos y controles de seguridad.',
            'Usar un usuario superadmin, admin o cliente. Consejo y comite tienen acceso de consulta.',
        ],
        'steps' => [
            ['Configurar el programa', 'Complete Resumen con identidad del responsable, canales, tiempos de conservacion, tratamientos condicionados y variables de seguridad. Guarde antes de continuar.'],
            ['Levantar el inventario', 'Registre cada base de datos y despues sus finalidades. Separe los datos sensibles y documente su finalidad exclusiva.'],
            ['Revisar terceros y seguridad', 'Clasifique proveedores, asigne responsables, documente controles y prepare compromisos individuales para quienes acceden a datos.'],
            ['Generar los siete documentos', 'Genere versiones desde el inventario vigente. Revise el HTML y el PDF, corrija borradores y confirme que el hash aparezca como integro.'],
            ['Aprobar y publicar por dependencias', 'Publique primero Politica y Aviso; luego Autorizacion, Procedimiento, Seguridad, Confidencialidad y Encargados. Si el sistema informa una dependencia desactualizada, regenere y use la nueva version.'],
            ['Probar el portal del Titular', 'Abra el portal desde Resumen, complete una autorizacion ficticia y radique una solicitud ficticia. Verifique correo, expediente PDF y registro de la decision.'],
            ['Operar y auditar', 'Atienda la solicitud en Solicitudes, registre acciones por base y tercero, cierre con respuesta y compruebe notificaciones y revisiones en Trazabilidad.'],
        ],
        'done' => [
            'Las bases activas y sus finalidades reflejan la operacion real.',
            'Los siete documentos vigentes muestran integridad verificada.',
            'El portal permite decidir por finalidad y ejercer derechos.',
            'Existen responsables, controles, acuerdos y evidencias operativas.',
        ],
        'warning' => 'No publique documentos de prueba en una copropiedad real. Aprobar, publicar, firmar o cerrar expedientes produce evidencia trazable.',
    ],
    'resumen' => [
        'eyebrow' => 'Paso 1',
        'title' => 'Configurar responsable, canales y reglas operativas',
        'intro' => 'Esta informacion alimenta las plantillas y determina que bloques legales y operativos deben incluirse.',
        'before' => [
            'Razon social o nombre juridico, NIT, direccion y ciudad del responsable.',
            'Correo que recibira consultas y reclamos de titulares.',
            'Decisiones confirmadas sobre video, biometria, menores, nube y circulacion internacional.',
        ],
        'steps' => [
            ['Identificar al responsable', 'Complete nombre, documento, ciudad, direccion, correo y telefono. No use datos de Cycloid si la copropiedad es el Responsable.'],
            ['Asignar la operacion', 'Indique la persona o area que atiende derechos, el cargo y los canales habilitados.'],
            ['Definir tiempos', 'Registre hora de corte, conservacion de expedientes, rotacion de respaldos y canal de entrega de copias.'],
            ['Marcar tratamientos condicionados', 'Active solamente los que existen. Al activar video, biometria o transferencias aparecen obligaciones que deben quedar completamente definidas.'],
            ['Completar el manual de seguridad', 'Identifique custodios, sedes, archivo, proveedor de TI, canal de incidentes, gestor de secretos y destruccion segura.'],
            ['Guardar y revisar', 'Pulse Guardar programa. Todo cambio posterior exige regenerar documentos para que las nuevas versiones incorporen la configuracion.'],
            ['Usar la IA de forma controlada', 'La revision asistida analiza el inventario estructurado. Revise sus observaciones en Trazabilidad; no sustituye la aprobacion juridica.'],
        ],
        'done' => [
            'No quedan campos operativos por definir.',
            'Los tratamientos marcados coinciden con la realidad.',
            'El portal publico abre con la identidad correcta de la copropiedad.',
        ],
        'warning' => 'No marque una condicion solo para probarla en un cliente real: cambia el contenido documental y las validaciones de aprobacion.',
    ],
    'inventario' => [
        'eyebrow' => 'Paso 2',
        'title' => 'Construir el inventario de bases y finalidades',
        'intro' => 'El inventario es la fuente de verdad para autorizaciones, documentos, solicitudes de supresion y alcance de terceros.',
        'before' => [
            'Listado de archivos fisicos, sistemas, hojas de calculo, CCTV y servicios en nube.',
            'Responsable interno, ubicacion, origen, categorias y retencion de cada base.',
            'Finalidades concretas y fundamento juridico revisado.',
        ],
        'steps' => [
            ['Crear una base por conjunto homogeneo', 'Ejemplos: propietarios, residentes, visitantes, empleados, proveedores o videovigilancia. Evite una unica base generica para todo.'],
            ['Completar el expediente de la base', 'Abra la base creada y registre responsable, ubicacion, soportes, origen, retencion, eliminacion, seguridad y evaluacion RNBD.'],
            ['Clasificar categorias especiales', 'Marque sensibles, biometricos o menores solo cuando correspondan. Estas marcas activan controles y textos adicionales.'],
            ['Agregar finalidades especificas', 'Vincule cada finalidad a una base e indique categorias exactas. No use expresiones abiertas como fines conexos o entre otros.'],
            ['Separar autorizacion y excepcion', 'Seleccione autorizacion cuando dependa del consentimiento. Para excepcion legal, documente la norma o la orden concreta.'],
            ['Desglosar datos sensibles', 'Marque decision explicita y registre cada dato sensible por separado con su finalidad exclusiva.'],
            ['Revisar antes de generar', 'Abra todas las bases y finalidades. Corrija vacios y despues genere nuevas versiones documentales.'],
        ],
        'done' => [
            'Cada ubicacion que contiene datos aparece en una base.',
            'Cada base tiene criterio de eliminacion y medidas de seguridad.',
            'Cada finalidad tiene categorias y fundamento definidos.',
            'Los datos sensibles estan separados de las categorias comunes.',
        ],
        'warning' => 'Archivar conserva la trazabilidad y retira la base de la operacion activa. No archive para corregir un dato menor; edite y guarde.',
    ],
    'documentos' => [
        'eyebrow' => 'Paso 3',
        'title' => 'Generar, revisar, aprobar y publicar documentos',
        'intro' => 'Cada version se sella con SHA-256. Un documento aprobado o publicado deja de ser un borrador editable.',
        'before' => [
            'Resumen e inventario completos y revisados.',
            'Variables condicionadas resueltas, sin textos por definir.',
            'Persona autorizada para realizar la aprobacion institucional.',
        ],
        'steps' => [
            ['Generar versiones', 'Pulse Generar nuevas versiones. El sistema crea los siete documentos desde los datos vigentes.'],
            ['Revisar contenido y PDF', 'Abra cada documento, lea la vista previa y descargue el PDF. Confirme nombres, canales, inventario, plazos y bloques condicionados.'],
            ['Corregir solo el borrador', 'Edite el HTML controlado cuando sea necesario y pulse Guardar para revision. El sistema sanea el contenido y calcula un hash nuevo.'],
            ['Aprobar la version', 'Compruebe Integridad verificada y pulse Aprobar version. Las validaciones bloquearan vacios, dependencias o reglas incompatibles.'],
            ['Publicar en orden', 'Publique Politica y Aviso; despues Autorizacion, Procedimiento, Seguridad, Confidencialidad y Encargados. Regenere cuando una dependencia publicada deba quedar vinculada en la nueva version.'],
            ['Registrar el Aviso', 'Para cada variante publicada indique canal y ubicacion. Adjunte evidencia cuando exista una fotografia, PDF o constancia.'],
            ['Conservar el expediente', 'Use el PDF y el hash como identificadores de la version. Una modificacion futura debe crear una nueva version, no alterar la publicada.'],
        ],
        'done' => [
            'Los siete documentos muestran la version y el hash esperados.',
            'Politica, Aviso y Autorizacion estan publicados para el portal.',
            'Las variantes del Aviso tienen canales de publicacion registrados.',
            'No hay mensajes COPIA NO VALIDA.',
        ],
        'warning' => 'Aprobar y publicar son actos trazables. No los use como botones de vista previa.',
    ],
    'titulares' => [
        'eyebrow' => 'Operacion continua',
        'title' => 'Consultar decisiones y exclusiones de titulares',
        'intro' => 'Esta vista consolida cobertura por apartamento o casa y las decisiones individuales capturadas en el portal publico.',
        'before' => [
            'Politica, Aviso y Autorizacion publicados.',
            'Portal del Titular disponible y correo transaccional operativo.',
            'Finalidades vigentes sin cambios pendientes de regeneracion.',
        ],
        'steps' => [
            ['Abrir el portal desde Resumen', 'Use Abrir portal para probar la experiencia que vera el Titular.'],
            ['Seleccionar la unidad', 'Propietarios, residentes, arrendatarios y menores identifican primero su apartamento o casa.'],
            ['Verificar identidad', 'El Titular solicita un codigo a su correo y lo ingresa antes de decidir.'],
            ['Revisar la instancia final', 'Antes de firmar, el portal presenta el documento aplicable, finalidades y vector de decisiones.'],
            ['Registrar la decision', 'El Titular firma y confirma. El sistema conserva la instancia vista, el hash y la evidencia tecnica.'],
            ['Consultar cobertura', 'Regrese a Titulares para ver las unidades gestionadas, pendientes o asociadas a una version anterior.'],
            ['Consultar el resultado individual', 'Compruebe unidad, perfil, decision, fecha y estado de exclusiones.'],
            ['Abrir el expediente', 'Use Expediente para descargar la constancia PDF asociada a esa decision.'],
        ],
        'done' => [
            'La unidad deja de aparecer como pendiente cuando recibe una decision sobre la version vigente.',
            'La decision individual aparece con fecha, unidad y evidencia.',
            'Las negativas o revocatorias generan la exclusion correspondiente.',
            'El PDF corresponde a la instancia y version que vio el Titular.',
        ],
        'warning' => 'No cambie una decision manualmente en base de datos. Revocatoria, supresion y correccion deben recorrer el flujo de solicitudes.',
    ],
    'solicitudes' => [
        'eyebrow' => 'Operacion continua',
        'title' => 'Atender consultas, reclamos y solicitudes de derechos',
        'intro' => 'El expediente controla identidad, clasificacion, terminos, acciones por base, ordenes a Encargados y respuesta final.',
        'before' => [
            'Canal de privacidad atendido por una persona designada.',
            'Inventario completo para buscar en todas las bases activas.',
            'Acuerdos de Encargados vigentes cuando terceros tratan datos.',
        ],
        'steps' => [
            ['Radicar o recibir desde el portal', 'Seleccione el tipo correcto y registre nombre, correo y peticion. Los hechos detallados se exigen para reclamos, no para una consulta simple.'],
            ['Validar identidad y legitimacion', 'Verifique que el solicitante sea Titular o este legitimado. Complete o requiera subsanacion antes de iniciar el termino aplicable.'],
            ['Clasificar y controlar el plazo', 'Reclasifique si procede, registre traslado cuando corresponda y notifique cualquier prorroga antes del vencimiento.'],
            ['Ejecutar la ruta por base', 'En cada base indique si no se encontro, suprimio, anonimizo, bloqueo, conservo, rectifico o actualizo. Adjunte una referencia de evidencia.'],
            ['Coordinar Encargados', 'Registre la orden enviada a cada tercero y su confirmacion, o marque no aplica. No cierre un reclamo con acciones pendientes.'],
            ['Preparar la respuesta', 'Describa el resultado. Si es parcial, bloqueado o negado, documente fundamento, datos conservados y fecha limite.'],
            ['Cerrar y verificar envio', 'Pulse Cerrar y enviar respuesta. Descargue el PDF y confirme la notificacion en Trazabilidad.'],
        ],
        'done' => [
            'Todas las bases tienen una accion ejecutada o no encontrado.',
            'Los Encargados confirmaron o quedaron marcados como no aplicables.',
            'La respuesta fue enviada antes del vencimiento o tiene causa documentada.',
            'El expediente PDF contiene la decision final.',
        ],
        'warning' => 'Cerrar puede generar exclusiones, programar supresiones y enviar correo. Revise todas las acciones antes de confirmar.',
    ],
    'seguridad' => [
        'eyebrow' => 'Gobierno y control',
        'title' => 'Asignar responsabilidades, evidenciar controles y manejar incidentes',
        'intro' => 'Esta seccion convierte el manual de seguridad en actividades verificables y controla la habilitacion individual de usuarios.',
        'before' => [
            'Responsables designados mediante acta, contrato o documento equivalente.',
            'Evidencias de controles, pruebas de restauracion y revision de accesos.',
            'Usuarios del cliente creados para asignarles alcance e induccion.',
        ],
        'steps' => [
            ['Completar Gobierno y RACI', 'Registre organo, administrador, responsable de privacidad, TI y seguridad fisica con su evidencia de designacion.'],
            ['Sellar controles periodicos', 'Seleccione el control, resultado, vigencia, evidencia y detalle verificable. Corrija los no conformes y registre una nueva ejecucion.'],
            ['Preparar usuarios', 'Cree el usuario desde el detalle del cliente. Registre su induccion y genere un compromiso con bases, finalidades y operaciones limitadas.'],
            ['Obtener la firma individual', 'El firmante recibe un enlace, verifica su correo y firma la instancia. La activacion exige compromiso vigente e induccion.'],
            ['Abrir incidentes sin demora', 'Registre conocimiento, deteccion, severidad, fuente y descripcion. El sistema calcula el vencimiento de reporte.'],
            ['Documentar cada actuacion', 'Clasifique, contenga, decida reporte, registre constancia SIC, comunique a Titulares cuando corresponda, recupere y cierre.'],
            ['Cerrar accesos al terminar', 'Use Revocar acceso y cerrar con motivo y evidencia de devolucion o eliminacion. El usuario queda inactivo.'],
        ],
        'done' => [
            'Cada responsabilidad tiene persona o area y acto de designacion.',
            'Los controles criticos tienen evidencia vigente.',
            'Ningun usuario accede sin induccion y compromiso vigente.',
            'Los incidentes abiertos tienen actuacion y decision dentro del plazo.',
        ],
        'warning' => 'Abrir, reportar y cerrar incidentes deja eventos inmutables. Use datos completos y no borre evidencia para corregir una actuacion.',
    ],
    'terceros' => [
        'eyebrow' => 'Encargados y proveedores',
        'title' => 'Clasificar terceros y formalizar transmisiones',
        'intro' => 'No todo proveedor es Encargado. El test material define si procede el Documento 7 y bloquea el envio de datos hasta completar el acuerdo.',
        'before' => [
            'Contrato principal, objeto, vigencia y evidencia de representacion.',
            'Detalle de servicio, datos, operaciones, sistemas, paises y subencargados.',
            'Debida diligencia de seguridad y canal de incidentes probado.',
        ],
        'steps' => [
            ['Crear el expediente', 'Identifique entidad, representante, contrato, servicio y alcance. Complete el test segun quien decide finalidades y medios.'],
            ['Revisar la clasificacion', 'Encargado opera bajo instrucciones; Responsable independiente decide por obligacion o fines propios; sin tratamiento no accede a datos.'],
            ['Documentar alcance y riesgos', 'Seleccione bases, finalidades, titulares, categorias, operaciones, sistemas, paises y medidas verificadas.'],
            ['Registrar subencargados', 'Identifique entidad, pais, servicio, datos y evidencia contractual antes de autorizarlos.'],
            ['Generar la instancia contractual', 'Solo para Encargados. Complete verificacion de paises, volumen, perfiles y firma del Responsable; luego selle y envie.'],
            ['Obtener doble firma', 'El representante del Encargado abre el enlace, verifica identidad y firma. Solo entonces el acuerdo queda vigente y el proveedor habilitado.'],
            ['Administrar el ciclo de vida', 'Emita instrucciones documentadas. Al terminar, bloquee envios y cierre solo despues de verificar devolucion, eliminacion y no reactivacion desde respaldos.'],
        ],
        'done' => [
            'Cada tercero tiene una clasificacion material justificada.',
            'Los Encargados habilitados tienen acuerdo vigente y doble firma.',
            'Los subencargados y paises estan declarados.',
            'Los terceros terminados tienen certificacion de cierre.',
        ],
        'warning' => 'Guardar el expediente no habilita al tercero. El envio de datos permanece bloqueado hasta que el acuerdo aplicable este vigente e integro.',
    ],
    'trazabilidad' => [
        'eyebrow' => 'Supervision',
        'title' => 'Verificar notificaciones y revisiones asistidas',
        'intro' => 'Esta vista ayuda a detectar comunicaciones fallidas y conserva el resultado de revisiones de IA sobre el inventario sin identidades.',
        'before' => [
            'Proveedor de correo configurado y Acuerdo de Encargado vigente cuando aplique.',
            'Inventario suficientemente completo para que la revision sea util.',
            'Responsable asignado para resolver fallos y observaciones.',
        ],
        'steps' => [
            ['Revisar notificaciones', 'Compruebe tipo, destinatario, estado y referencia del proveedor despues de consentimientos, solicitudes y firmas.'],
            ['Investigar fallos', 'Un estado fallido o rebotado requiere revisar configuracion, acuerdo del proveedor y correo destinatario antes de reintentar el proceso.'],
            ['Ejecutar revision asistida', 'Desde Resumen pulse Revisar inventario con IA. El filtro bloquea posibles identificadores antes de cualquier envio.'],
            ['Leer el resultado', 'Abra la ejecucion y revise observaciones estructuradas. Corrija el inventario o la configuracion en su pestaña de origen.'],
            ['Regenerar cuando cambie la fuente', 'Despues de corregir Resumen o Inventario, genere nuevas versiones documentales y repita la revision cuando sea necesario.'],
            ['Escalar alertas', 'Si el filtro de identidad bloquea el envio, el sistema abre un incidente de privacidad. Atiendalo en Seguridad.'],
        ],
        'done' => [
            'No hay notificaciones criticas fallidas sin investigar.',
            'Las observaciones de IA tienen correccion o decision documentada.',
            'Los cambios de inventario se reflejan en versiones documentales nuevas.',
        ],
        'warning' => 'La IA es una segunda revision del inventario, no aprueba documentos ni reemplaza la decision juridica o administrativa.',
    ],
];
?>
<style>
    .pane-guide-bar{display:flex;justify-content:flex-end;margin:0 0 12px}.privacy-guide-trigger{display:inline-flex;align-items:center;justify-content:center;min-height:38px;border:1px solid #cfd6df;background:#fff;color:#172235;border-radius:8px;padding:8px 12px;font-weight:700;cursor:pointer}.privacy-guide-trigger:hover{border-color:#9aa7b8;background:#f7f8fa}.privacy-guide-trigger:focus-visible,.guide-close:focus-visible,.guide-nav button:focus-visible{outline:3px solid #d5a928;outline-offset:2px}.privacy-guide-modal[hidden]{display:none}.privacy-guide-modal{position:fixed;inset:0;z-index:1000;background:rgba(10,18,31,.66);display:grid;place-items:center;padding:20px}.guide-shell{width:min(1120px,100%);height:min(760px,calc(100vh - 40px));background:#fff;border-radius:8px;box-shadow:0 24px 70px rgba(0,0,0,.3);display:grid;grid-template-columns:250px minmax(0,1fr);overflow:hidden}.guide-sidebar{background:#111c2c;color:#fff;padding:24px 18px;overflow:auto}.guide-sidebar h2{font-size:1rem;margin:0 0 18px}.guide-nav{display:grid;gap:4px}.guide-nav button{width:100%;border:0;background:transparent;color:#cbd3df;text-align:left;padding:10px;border-radius:6px;font-weight:700;cursor:pointer}.guide-nav button.active{background:#29384c;color:#fff;box-shadow:inset 3px 0 #d5a928}.guide-main{min-width:0;display:flex;flex-direction:column}.guide-head{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e3e7ed;padding:14px 20px}.guide-head strong{font-size:.9rem}.guide-close{width:40px;height:40px;border:0;background:#eef1f5;color:#172235;border-radius:50%;font-size:1.5rem;line-height:1;cursor:pointer}.guide-content{padding:28px 34px 40px;overflow:auto}.guide-panel[hidden]{display:none}.guide-eyebrow{font-size:.72rem;text-transform:uppercase;color:#7b5b00;font-weight:800}.guide-panel h2{font-size:1.65rem;line-height:1.2;margin:7px 0 10px;letter-spacing:0}.guide-intro{color:#526071;line-height:1.6;margin:0 0 24px}.guide-panel h3{font-size:.92rem;margin:24px 0 10px;letter-spacing:0}.guide-before,.guide-done{margin:0;padding-left:20px;color:#334155;line-height:1.55}.guide-before li,.guide-done li{margin:6px 0}.guide-steps{list-style:none;margin:0;padding:0;counter-reset:guide-step}.guide-steps li{counter-increment:guide-step;display:grid;grid-template-columns:38px minmax(0,1fr);gap:12px;padding:15px 0;border-bottom:1px solid #e5e7eb}.guide-steps li::before{content:counter(guide-step);display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:#172235;color:#fff;font-weight:800}.guide-step-copy strong{display:block;margin:1px 0 5px}.guide-step-copy span{display:block;color:#526071;line-height:1.55}.guide-warning{margin-top:24px;border-left:4px solid #d5a928;background:#fff8df;padding:14px 16px;color:#4d3c09;line-height:1.5}.guide-progress{font-size:.78rem;color:#667085}.guide-progress b{color:#172235}@media(max-width:760px){.privacy-guide-modal{padding:0}.guide-shell{height:100vh;width:100%;border-radius:0;grid-template-columns:1fr}.guide-sidebar{padding:12px 14px}.guide-sidebar h2{margin-bottom:10px}.guide-nav{display:flex;overflow-x:auto}.guide-nav button{width:auto;white-space:nowrap}.guide-main{min-height:0}.guide-content{padding:22px 20px 36px}.guide-panel h2{font-size:1.35rem}.guide-head{padding:10px 14px}}
</style>

<div class="privacy-guide-modal" id="privacy-guide-modal" role="dialog" aria-modal="true" aria-labelledby="privacy-guide-heading" hidden>
    <div class="guide-shell">
        <aside class="guide-sidebar">
            <h2 id="privacy-guide-heading">Centro de instructivos</h2>
            <nav class="guide-nav" aria-label="Secciones del instructivo">
                <?php foreach ($privacyGuides as $guideKey => $guide): ?>
                    <button type="button" data-guide-target="<?= esc($guideKey) ?>"><?= esc($guideKey === 'general' ? 'Ruta general' : ucfirst($guideKey)) ?></button>
                <?php endforeach; ?>
            </nav>
        </aside>
        <div class="guide-main">
            <header class="guide-head">
                <div class="guide-progress">Instructivo <b id="guide-current-number">1</b> de <?= count($privacyGuides) ?></div>
                <button class="guide-close" type="button" data-guide-close aria-label="Cerrar instructivo" title="Cerrar">&times;</button>
            </header>
            <div class="guide-content">
                <?php foreach ($privacyGuides as $guideKey => $guide): ?>
                    <article class="guide-panel" data-guide-panel="<?= esc($guideKey) ?>" hidden>
                        <div class="guide-eyebrow"><?= esc($guide['eyebrow']) ?></div>
                        <h2><?= esc($guide['title']) ?></h2>
                        <p class="guide-intro"><?= esc($guide['intro']) ?></p>
                        <h3>Antes de empezar</h3>
                        <ul class="guide-before"><?php foreach ($guide['before'] as $item): ?><li><?= esc($item) ?></li><?php endforeach; ?></ul>
                        <h3>Paso a paso</h3>
                        <ol class="guide-steps">
                            <?php foreach ($guide['steps'] as [$stepTitle, $stepBody]): ?>
                                <li><div class="guide-step-copy"><strong><?= esc($stepTitle) ?></strong><span><?= esc($stepBody) ?></span></div></li>
                            <?php endforeach; ?>
                        </ol>
                        <h3>Queda listo cuando</h3>
                        <ul class="guide-done"><?php foreach ($guide['done'] as $item): ?><li><?= esc($item) ?></li><?php endforeach; ?></ul>
                        <div class="guide-warning"><strong>Atencion:</strong> <?= esc($guide['warning']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var modal=document.getElementById('privacy-guide-modal');
    if(!modal)return;
    var triggers=[].slice.call(document.querySelectorAll('[data-guide-open]'));
    var nav=[].slice.call(modal.querySelectorAll('[data-guide-target]'));
    var panels=[].slice.call(modal.querySelectorAll('[data-guide-panel]'));
    var closeButton=modal.querySelector('[data-guide-close]');
    var currentNumber=document.getElementById('guide-current-number');
    var lastFocus=null;
    function selectGuide(key){
        var index=nav.findIndex(function(button){return button.dataset.guideTarget===key});
        if(index<0){index=0;key=nav[0].dataset.guideTarget}
        nav.forEach(function(button){var active=button.dataset.guideTarget===key;button.classList.toggle('active',active);button.setAttribute('aria-current',active?'page':'false')});
        panels.forEach(function(panel){panel.hidden=panel.dataset.guidePanel!==key});
        currentNumber.textContent=String(index+1);
        modal.querySelector('.guide-content').scrollTop=0;
    }
    function openGuide(key){lastFocus=document.activeElement;selectGuide(key);modal.hidden=false;document.body.style.overflow='hidden';closeButton.focus()}
    function closeGuide(){modal.hidden=true;document.body.style.overflow='';if(lastFocus&&typeof lastFocus.focus==='function')lastFocus.focus()}
    triggers.forEach(function(button){button.addEventListener('click',function(){openGuide(button.dataset.guideOpen)})});
    nav.forEach(function(button){button.addEventListener('click',function(){selectGuide(button.dataset.guideTarget)})});
    closeButton.addEventListener('click',closeGuide);
    modal.addEventListener('click',function(event){if(event.target===modal)closeGuide()});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'&&!modal.hidden)closeGuide()});
})();
</script>
