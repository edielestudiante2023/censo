<?php

namespace App\Libraries;

final class PrivacyProcessorAgreementService
{
    public const CLASSIFICATIONS = ['encargado', 'rol_dual', 'responsable_independiente', 'sin_tratamiento'];
    public const OPERATIONS = ['recoleccion', 'registro', 'consulta', 'almacenamiento', 'actualizacion', 'circulacion', 'supresion', 'soporte', 'envio_correo', 'analisis_ia', 'cctv', 'biometria'];
    public const RISK_LEVELS = ['basico', 'medio', 'alto'];

    /** @return array{html:string,hash:string} */
    public function build(array $d): array
    {
        $e = static fn ($value): string => esc((string) $value);
        $list = static fn (array $values): string => esc(implode(', ', $values));
        $scope = '<table><tbody><tr><th>Servicio</th><td>' . $e($d['service']) . '</td></tr><tr><th>Bases</th><td>' . $list($d['bases']) .
            '</td></tr><tr><th>Titulares</th><td>' . $list($d['holders']) . '</td></tr><tr><th>Categorias</th><td>' . $list($d['categories']) .
            '</td></tr><tr><th>Finalidades</th><td>' . $list($d['purposes']) . '</td></tr><tr><th>Operaciones</th><td>' . $list($d['operations']) .
            '</td></tr><tr><th>Sistemas y soportes</th><td>' . $list($d['systems']) . '</td></tr><tr><th>Paises</th><td>' . $list($d['countries']) . '</td></tr></tbody></table>';
        $subprocessors = '<p>El Encargado declara que no utiliza subencargados; toda vinculacion futura requiere aprobacion previa y nueva instancia.</p>';
        if ($d['subprocessors'] !== []) {
            $subprocessors = '<table><thead><tr><th>Entidad</th><th>ID</th><th>Pais</th><th>Servicio</th><th>Datos</th><th>DPA</th><th>Aprobacion</th></tr></thead><tbody>';
            foreach ($d['subprocessors'] as $sub) {
                $subprocessors .= '<tr><td>' . $e($sub['nombre']) . '</td><td>' . $e($sub['documento']) . '</td><td>' . $e($sub['pais']) . '</td><td>' . $e($sub['servicio']) . '</td><td>' . $list($sub['datos']) . '</td><td>' . $e($sub['contrato_evidencia']) . '</td><td>' . $e($sub['aprobado_at']) . '</td></tr>';
            }
            $subprocessors .= '</tbody></table>';
        }
        $special = [];
        if ($d['flags']['sensitive']) { $special[] = 'Datos sensibles: tratamiento facultativo salvo excepcion legal, autorizacion explicita verificada y controles reforzados.'; }
        if ($d['flags']['biometric']) { $special[] = 'Biometria: alternativa no biometrica, plantillas cifradas y separadas, y supresion instruida por el Responsable.'; }
        if ($d['flags']['video']) { $special[] = 'Videovigilancia: avisos visibles, retencion declarada, acceso registrado y entrega solo por el procedimiento de derechos o a autoridad competente.'; }
        if ($d['flags']['minors']) { $special[] = 'Menores: interes superior, derechos prevalentes y representacion verificada cuando corresponda.'; }
        $specialHtml = $special ? '<ul><li>' . implode('</li><li>', array_map($e, $special)) . '</li></ul>' : '<p>No se declararon categorias especiales para esta instancia.</p>';
        $versions = [];
        foreach ($d['versions'] as $type => $version) { $versions[] = ucfirst($type) . ' v' . $version; }
        $body = '<article class="legal-document processor-agreement"><header><p><strong>' . $e($d['responsible']) . '</strong></p><h1>Acuerdo de Transmision y Tratamiento de Datos Personales con Encargado</h1><p>Documento 7. Instancia v' . $e($d['instance_version']) . ', fecha ' . $e($d['generated_at']) . '.</p></header>' .
            '<p><strong>PARTES.</strong> De una parte, ' . $e($d['responsible']) . ', persona juridica de propiedad horizontal, NIT ' . $e($d['responsible_id']) . ', representada por ' . $e($d['responsible_representative']) . ', como Responsable; y de otra, ' . $e($d['processor']) . ', identificado con ' . $e($d['processor_id']) . ', representado por ' . $e($d['processor_representative']) . ' (' . $e($d['processor_representative_id']) . '), como Encargado. Sus facultades constan en ' . $e($d['representation_evidence']) . '. Este acuerdo es accesorio al contrato ' . $e($d['main_contract']) . ' y constituye contrato de transmision conforme al articulo 2.2.2.25.5.2 del Decreto 1074 de 2015.</p>' .
            '<h2>1. Definiciones</h2><p>Dato personal, Dato sensible, Titular, Tratamiento, Responsable, Encargado, Base de Datos, Transmision y Transferencia tienen el significado de la Ley 1581 de 2012 y el Decreto 1074 de 2015. Instruccion documentada es la orden emitida por canal autorizado y registrada. Los dias son habiles colombianos salvo indicacion contraria.</p>' .
            '<h2>2. Objeto y alcance</h2><p>El Encargado tratara datos exclusivamente por cuenta e instrucciones documentadas del Responsable dentro del siguiente alcance:</p>' . $scope . '<p>Se prohiben finalidades propias, cruces no instruidos, perfiles no autorizados, conservacion fuera del alcance, analitica comercial y entrenamiento de modelos propios o de terceros. El tratamiento por fuera del alcance constituye incumplimiento grave y puede cambiar materialmente el rol del receptor.</p>' .
            '<h2>3. Naturaleza de la operacion</h2><p>La comunicacion al Encargado es transmision. Si un pais declarado es distinto de Colombia, es transmision internacional bajo este contrato. Este acuerdo no autoriza transferencias a otro Responsable; estas exigen instruccion y analisis separado del articulo 26 de la Ley 1581.</p>' .
            '<h2>4. Declaracion de rol</h2><p>El test del Anexo A arrojo <strong>' . $e($d['classification']) . '</strong>: ' . $e($d['classification_justification']) . '. Todo cambio que permita decidir finalidades, medios esenciales o usos propios se informa dentro de cinco dias y suspende la operacion afectada hasta reclasificarla.</p>' .
            '<h2>5. Instrucciones documentadas</h2><p>Solo vinculan instrucciones emitidas por el modulo o correo oficial por personas facultadas. Las verbales se confirman dentro de dos dias. El Encargado acusa recibo y objeta dentro de tres dias toda instruccion que considere contraria al regimen, suspendiendola hasta decision escrita.</p>' .
            '<h2>6. Confidencialidad y personal</h2><p>La reserva sobre datos personales subsiste indefinidamente. El acceso se limita a personal necesario con compromiso individual equivalente al Documento 6, capacitacion anual y revocacion a mas tardar el dia habil siguiente a la desvinculacion o cambio.</p>' .
            '<h2>7. Deberes del Encargado</h2><p>Se incorporan los deberes del articulo 18 de la Ley 1581: garantizar habeas data; conservar seguridad; actualizar, rectificar y suprimir por instruccion; tramitar derechos; insertar leyendas; restringir circulacion; informar incidentes; cumplir instrucciones SIC y mantener evidencia.</p>' .
            '<h2>8. Seguridad</h2><p>Riesgo <strong>' . $e($d['risk']) . '</strong>. Medidas: ' . $list($d['measures']) . '. Incluyen acceso por necesidad, MFA para administracion y remoto, cifrado en transito y reposo, auditoria por ' . $e($d['log_months']) . ' meses, segregacion, respaldos cifrados, restauracion probada, vulnerabilidades, continuidad RTO ' . $e($d['rto']) . 'h/RPO ' . $e($d['rpo']) . 'h y destruccion segura. Reducir medidas exige nueva instancia y no puede disminuir proteccion.</p>' .
            '<h2>9. Derechos de Titulares</h2><p>El Encargado remite toda solicitud en maximo dos dias por ' . $e($d['rights_channel']) . ' y asiste en maximo tres. No decide ni responde de fondo salvo instruccion. Las supresiones en produccion se ejecutan y confirman en cinco dias.</p>' .
            '<h2>10. Incidentes</h2><p>Notificara por ' . $e($d['incident_channel']) . ' dentro de ' . $e($d['incident_days']) . ' dias habiles desde deteccion o conocimiento. Informara naturaleza, bases, categorias, Titulares estimados, sistemas, fechas, contencion y contacto; actualizara cada cinco dias, preservara evidencia seis meses y entregara informe final dentro de quince dias del cierre. No comunicara a Titulares, medios o terceros salvo deber legal propio.</p>' .
            '<h2>11. Subencargados</h2><p>Solo operan los del Anexo C. Todo cambio exige debida diligencia, contrato equivalente, aprobacion previa expresa y nueva instancia; el silencio no aprueba. El Encargado responde por ellos como por actos propios.</p>' .
            '<h2>12. Ubicaciones e internacional</h2><p>Los datos solo se almacenan o acceden desde ' . $list($d['countries']) . '. Verificacion de lista vigente efectuada el ' . $e($d['country_check_date']) . '. La lista de pais adecuado no sustituye el contrato. Las clausulas tipo de la Circular Externa SIC 003 de 2025 solo obligan si las partes las adoptan expresamente.</p>' .
            '<h2>13. Autoridades</h2><p>El Encargado verifica competencia, revela lo minimo, registra autoridad, fundamento, datos y fecha, y avisa antes al Responsable salvo prohibicion; en tal caso avisa al cesar la restriccion.</p>' .
            '<h2>14. Registro, evidencia y auditoria</h2><p>Conservara registros durante la vigencia y dos anos mas. Entregara autoevaluacion anual, informes de terceros que invoque y permitira auditoria maximo anual o por incidente grave, con diez dias de aviso, sin revelar datos de otros clientes ni secretos y sin interrumpir la operacion.</p>' .
            '<h2>15. Responsabilidad e indemnidad</h2><p>Cada parte responde por su incumplimiento demostrado. El Encargado mantiene indemne al Responsable por actos propios o de subencargados; el Responsable por instrucciones ilicitas mantenidas tras advertencia. No hay responsabilidad objetiva ni sanciones privadas. Limites del contrato principal no cubren datos personales, confidencialidad, dolo o culpa grave.</p>' .
            '<h2>16. Vigencia</h2><p>Rige de ' . $e($d['valid_from']) . ' a ' . $e($d['valid_until']) . ', sin exceder el contrato principal. Sobreviven confidencialidad, incidentes anteriores, evidencia, responsabilidad y devolucion o supresion.</p>' .
            '<h2>17. Terminacion, devolucion y supresion</h2><p>El Encargado cesa el tratamiento y, por instruccion, devuelve en formato estructurado dentro de quince dias y/o suprime produccion dentro de treinta dias calendario. Respaldos quedan bloqueados y rotan en maximo ' . $e($d['backup_days']) . ' dias; toda restauracion reaplica exclusiones antes de habilitar. Conservacion legal exige norma, plazo y bloqueo. Entregara el Anexo F firmado dentro de cinco dias del cumplimiento.</p>' .
            '<h2>18. Condiciones especiales</h2>' . $specialHtml .
            '<h2>19. Documentos y prelacion</h2><p>Integran el acuerdo los Anexos A-F y ' . $e(implode(', ', $versions)) . '. En proteccion de datos prevalece este acuerdo; en lo demas, el contrato principal.</p>' .
            '<h2>20. Incumplimiento y terminacion anticipada</h2><p>El incumplido dispone de diez dias para subsanar, salvo uso propio, subencargado o pais no autorizado, ocultamiento de incidente, negativa a devolver o suprimir, o categorias especiales no declaradas, que permiten terminacion inmediata y activan la clausula 17.</p>' .
            '<h2>21. Ley, controversias y firma</h2><p>Rige la ley colombiana. Habra arreglo directo por treinta dias y luego jurisdiccion ordinaria, sin limitar competencias SIC. La firma electronica conforme a la Ley 527 vincula identidad, facultades, texto, anexos, version, fecha, canal y hash. Todo cambio exige nueva instancia.</p>' .
            '<h2>Anexo A. Test de clasificacion resuelto</h2><p>Evaluado el ' . $e($d['classification_date']) . ' por ' . $e($d['classification_evaluator']) . '. Resultado: ' . $e($d['classification']) . '. Respuestas: ' . $e(json_encode($d['classification_answers'], JSON_UNESCAPED_UNICODE)) . '. Justificacion: ' . $e($d['classification_justification']) . '.</p>' .
            '<h2>Anexo B. Alcance y seguridad</h2>' . $scope . '<p>Volumen/frecuencia: ' . $e($d['volume']) . '. Perfiles: ' . $e($d['access_profiles']) . '. Riesgo: ' . $e($d['risk']) . '. Medidas: ' . $list($d['measures']) . '. Logs: ' . $e($d['log_months']) . ' meses. Backups: ' . $e($d['backup_days']) . ' dias. RTO/RPO: ' . $e($d['rto']) . '/' . $e($d['rpo']) . ' horas. Canales: instrucciones ' . $e($d['instruction_channel']) . '; incidentes ' . $e($d['incident_channel']) . '; derechos ' . $e($d['rights_channel']) . '.</p>' .
            '<h2>Anexo C. Subencargados autorizados</h2>' . $subprocessors .
            '<h2>Anexo D. Condiciones especiales</h2>' . $specialHtml .
            '<h2>Anexo E. Seguros y garantias</h2><p>' . ($d['risk'] === 'basico' ? 'No se exigen garantias adicionales a las del contrato principal.' : $e($d['insurance'])) . '</p>' .
            '<h2>Anexo F. Certificacion de devolucion y supresion</h2><p>Al terminar, el representante declarara cese, devoluciones, sistemas y fechas de supresion, estado y rotacion de respaldos, excepciones con norma/plazo/bloqueo, fecha y firma, referidas a esta instancia.</p>' .
            '<h2>Suscripcion electronica</h2><p>Por el Responsable firma ' . $e($d['responsible_representative']) . '; por el Encargado firma ' . $e($d['processor_representative']) . '. Instancia v' . $e($d['instance_version']) . '.</p></article>';
        $hash = hash('sha256', $body);
        return ['html' => $body . '<footer data-processor-seal="sha256"><p><strong>Hash SHA-256 canonico:</strong> ' . $hash . '</p></footer>', 'hash' => $hash];
    }

    public function verify(string $html, string $hash): bool
    {
        $canonical = preg_replace('#<footer data-processor-seal="sha256">.*?</footer>$#s', '', $html) ?? '';
        return hash_equals($hash, hash('sha256', $canonical));
    }
}
