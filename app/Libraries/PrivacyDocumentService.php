<?php

namespace App\Libraries;

use App\Models\DpDocumentoModel;

final class PrivacyDocumentService
{
    private const DOCUMENTS = [
        'politica' => 'Politica de Tratamiento de Datos Personales',
        'aviso' => 'Aviso de Privacidad',
        'autorizacion' => 'Autorizacion para el Tratamiento de Datos Personales',
        'procedimiento' => 'Procedimiento de Consultas, Reclamos, Rectificacion, Actualizacion, Revocatorias y Supresion',
        'seguridad' => 'Manual Interno de Seguridad de la Informacion Personal',
        'confidencialidad' => 'Compromiso de Confidencialidad y Uso Autorizado',
        'encargados' => 'Acuerdo de Transmision y Tratamiento con Encargados',
    ];

    public function generateSet(array $cliente, array $programa, array $bases, array $finalidades, bool $initial = false): array
    {
        $created = [];
        $model = new DpDocumentoModel();

        foreach (self::DOCUMENTS as $type => $title) {
            $last = $model->where('cliente_id', $cliente['id'])->where('tipo', $type)
                ->orderBy('version', 'DESC')->first();
            if ($initial && $last) {
                continue;
            }

            $version = $last ? ((int) $last['version'] + 1) : 1;
            $content = $this->render($type, $cliente, $programa, $bases, $finalidades);
            $code = strtoupper('DP-' . substr($type, 0, 4));
            $dependencies = null;
            if (in_array($type, ['procedimiento', 'seguridad', 'confidencialidad', 'encargados'], true)) {
                $dependencies = [];
                $dependencyTypes = match ($type) {
                    'seguridad' => ['politica', 'aviso', 'autorizacion', 'procedimiento'],
                    'confidencialidad' => ['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad'],
                    'encargados' => ['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad', 'confidencialidad'],
                    default => ['politica', 'aviso', 'autorizacion'],
                };
                foreach ($dependencyTypes as $dependencyType) {
                    $dependency = $model->where('cliente_id', $cliente['id'])->where('tipo', $dependencyType)
                        ->where('estado', 'publicado')->orderBy('version', 'DESC')->first();
                    $dependencies[$dependencyType] = $dependency ? ['version' => (int) $dependency['version'], 'hash' => $dependency['hash_sha256']] : null;
                }
            }
            $id = $model->insert([
                'cliente_id' => $cliente['id'],
                'codigo' => $code,
                'tipo' => $type,
                'titulo' => $title,
                'version' => $version,
                'estado' => 'borrador',
                'contenido_html' => $content,
                'variables_json' => json_encode([
                    'programa_updated_at' => $programa['updated_at'] ?? null,
                    'bases' => array_column($bases, 'id'),
                    'consent_schema_version' => $type === 'autorizacion' ? 2 : null,
                    'purpose_versions' => $type === 'autorizacion' ? array_reduce($finalidades, static function (array $carry, array $purpose): array {
                        $carry[(string) $purpose['id']] = ['version' => (int) ($purpose['version'] ?? 1), 'hash' => $purpose['contenido_hash'] ?? null];
                        return $carry;
                    }, []) : null,
                    'document_dependencies' => $dependencies,
                    'security_schema_version' => $type === 'seguridad' ? 2 : null,
                    'confidentiality_schema_version' => $type === 'confidencialidad' ? 2 : null,
                    'processor_agreement_schema_version' => $type === 'encargados' ? 2 : null,
                ], JSON_UNESCAPED_UNICODE),
                'hash_sha256' => hash('sha256', $content),
            ], true);
            $created[] = (int) $id;

            if ($type === 'aviso') {
                $this->storeNoticeVariants((int) $cliente['id'], (int) $id, $version, $cliente, $programa, $bases, $finalidades);
            }
        }

        return $created;
    }

    public function render(string $type, array $cliente, array $programa, array $bases, array $finalidades, array $runtime = []): string
    {
        $context = $this->context($cliente, $programa);

        return match ($type) {
            'politica' => $this->policy($context, $bases, $finalidades),
            'aviso' => $this->notice($context, $bases, $finalidades),
            'autorizacion' => $this->authorization($context, $bases, $finalidades),
            'procedimiento' => $this->procedure($context),
            'seguridad' => $this->security($context, $bases, $finalidades, $runtime),
            'confidencialidad' => $this->confidentiality($context),
            'encargados' => $this->processors($context),
            default => throw new \InvalidArgumentException('Tipo documental no soportado.'),
        };
    }

    public function renderNoticeVariants(array $cliente, array $programa, array $bases, array $finalidades): array
    {
        $c = $this->context($cliente, $programa);
        $purposes = $this->purposeSummary($bases, $finalidades);
        $policyAccess = $this->policyAccess($c);
        $rights = $this->rightsAndChannels($c);
        $variants = [
            'formulario' => [
                'title' => 'Aviso de Privacidad para Formularios Fisicos y Digitales',
                'html' => $this->document('Aviso de Privacidad para Formularios Fisicos y Digitales', $c,
                    '<p>Antes de entregar sus datos, se informa que <strong>' . $c['name'] . '</strong> es el Responsable de su tratamiento.</p>' .
                    '<h2>Finalidades</h2>' . $purposes . $rights . $policyAccess .
                    '<p>La autorizacion, cuando sea necesaria, se solicita de forma separada y permite conservar evidencia de la decision del Titular.</p>'),
            ],
            'porteria' => [
                'title' => 'Aviso de Privacidad para Porteria y Control de Acceso',
                'html' => $this->document('Aviso de Privacidad para Porteria y Control de Acceso', $c,
                    '<p><strong>' . $c['name'] . '</strong> trata los datos suministrados en porteria o en los canales de control de acceso para verificar la autorizacion de ingreso, registrar entradas y salidas, atender emergencias, preservar la seguridad y gestionar incidentes relacionados con la copropiedad.</p>' .
                    '<p>Solo se solicitaran datos adecuados y necesarios para estas finalidades. Las respuestas sobre datos sensibles son facultativas, salvo que exista una excepcion legal debidamente informada.</p>' .
                    $rights . $policyAccess),
            ],
        ];

        if ($c['uses_video']) {
            $videoAction = $c['records_video']
                ? 'capta, monitorea, graba y almacena imagenes'
                : 'capta y monitorea imagenes en tiempo real, sin grabarlas ni almacenarlas';
            $retention = $c['records_video'] && $c['video_retention_days']
                ? '<p>Las grabaciones se conservan ordinariamente hasta por <strong>' . $c['video_retention_days'] . ' dias</strong>, salvo bloqueo por incidente, investigacion o deber legal.</p>'
                : '';
            $variants['videovigilancia'] = [
                'title' => 'Aviso de Privacidad de Videovigilancia',
                'html' => $this->document('Aviso de Privacidad de Videovigilancia', $c,
                    '<p>Esta zona cuenta con un sistema que ' . $videoAction . ' para proteger personas y bienes, controlar accesos, prevenir y gestionar incidentes y aportar evidencia cuando sea procedente.</p>' .
                    '<p>La ubicacion y el angulo de las camaras se limitan a lo necesario y procuran minimizar la captacion de espacios publicos o privados ajenos a la copropiedad.</p>' . $retention .
                    '<p>El acceso a imagenes se encuentra restringido. La entrega o consulta de registros que involucren a terceros se realizara mediante extractos, enmascaramiento o acceso controlado cuando sea necesario para proteger sus derechos.</p>' .
                    $rights . $policyAccess),
            ];
        }

        if ($c['uses_biometrics'] || $c['video_biometric_identification']) {
            $variants['biometria'] = [
                'title' => 'Aviso de Privacidad para Datos Biometricos',
                'html' => $this->document('Aviso de Privacidad para Datos Biometricos', $c,
                    '<p>El sistema trata <strong>' . ($c['biometric_type'] ?: 'datos biometricos') . '</strong> para ' . ($c['biometric_purpose'] ?: 'la finalidad especifica informada antes de su recoleccion') . '. Cuando permiten identificar de manera unica a una persona, estos datos son sensibles.</p>' .
                    '<p>La autorizacion explicita es voluntaria, salvo excepcion legal aplicable. Negarse no genera sanciones ni impide utilizar la alternativa <strong>' . ($c['biometric_alternative'] ?: 'no biometrica informada por la administracion') . '</strong>.</p>' .
                    $rights . $policyAccess),
            ];
        }

        return $variants;
    }

    private function context(array $cliente, array $programa): array
    {
        $config = json_decode((string) ($programa['config_json'] ?? '{}'), true);
        $config = is_array($config) ? $config : [];
        $context = [
            'client_id' => (int) $cliente['id'],
            'name' => $this->e($programa['responsable_nombre'] ?? $cliente['nombre_tercero']),
            'document' => $this->e($programa['responsable_documento'] ?? $cliente['documento'] ?? 'Por completar'),
            'address' => $this->e($programa['responsable_direccion'] ?? $cliente['direccion'] ?? 'Por completar'),
            'city' => $this->e($programa['responsable_ciudad'] ?? $cliente['ciudad'] ?? 'Por completar'),
            'email' => $this->e($programa['canal_email'] ?? $cliente['email'] ?? 'Por completar'),
            'phone' => $this->e($programa['canal_telefono'] ?? $cliente['telefono'] ?? 'Por completar'),
            'officer' => $this->e($programa['oficial_nombre'] ?? 'Administracion'),
            'app_name' => $this->e($config['privacy_app_name'] ?? 'Modulo de Proteccion de Datos'),
            'request_cutoff' => $this->e($config['request_cutoff_time'] ?? '17:00'),
            'request_file_years' => max(5, (int) ($config['request_file_years'] ?? 5)),
            'backup_rotation_days' => max(1, (int) ($config['backup_rotation_days'] ?? 90)),
            'security_acta' => $this->e($config['security_acta'] ?? ''),
            'security_administrator' => $this->e($config['security_administrador'] ?? ''),
            'security_opd_designation' => $this->e($config['security_opd_designacion'] ?? ''),
            'security_opd_report' => $this->e($config['security_opd_reporte'] ?? ''),
            'security_it_provider' => $this->e($config['security_proveedor_ti'] ?? ''),
            'security_locations' => $this->e($config['security_sedes'] ?? ''),
            'security_archive_location' => $this->e($config['security_archivo_ubicacion'] ?? ''),
            'security_archive_custodian' => $this->e($config['security_archivo_custodio'] ?? ''),
            'security_incident_channel' => $this->e($config['security_canal_incidentes'] ?? ''),
            'security_secret_manager' => $this->e($config['security_gestor_secretos'] ?? ''),
            'security_log_months' => max(1, (int) ($config['security_retencion_logs_meses'] ?? 12)),
            'security_backup_days' => max(1, (int) ($config['security_retencion_backups_dias'] ?? 90)),
            'security_risk_months' => max(1, (int) ($config['security_revision_riesgos_meses'] ?? 12)),
            'security_access_months' => max(1, (int) ($config['security_revision_accesos_meses'] ?? 6)),
            'security_inventory_months' => max(1, (int) ($config['security_revision_inventario_meses'] ?? 12)),
            'security_restore_months' => max(1, (int) ($config['security_prueba_restauracion_meses'] ?? 6)),
            'security_training_months' => max(1, (int) ($config['security_capacitacion_meses'] ?? 12)),
            'security_processor_months' => max(1, (int) ($config['security_evaluacion_encargados_meses'] ?? 12)),
            'security_secret_months' => max(1, (int) ($config['security_rotacion_secretos_meses'] ?? 12)),
            'security_ai_months' => max(1, (int) ($config['security_prueba_ia_meses'] ?? 6)),
            'security_timeout' => max(5, (int) ($config['security_timeout_minutos'] ?? 15)),
            'security_attempts' => max(3, (int) ($config['security_max_intentos'] ?? 5)),
            'security_password_length' => max(12, (int) ($config['security_min_caracteres'] ?? 12)),
            'security_export_threshold' => max(1, (int) ($config['security_umbral_exportacion'] ?? 100)),
            'security_remote_work' => ! empty($config['security_trabajo_remoto']),
            'security_physical' => ! empty($config['security_soportes_fisicos']),
            'security_cloud' => ! empty($config['security_nube']),
            'security_cloud_provider' => $this->e($config['security_nube_proveedor'] ?? ''),
            'security_cloud_country' => $this->e($config['security_nube_pais'] ?? ''),
            'security_cctv_location' => $this->e($config['security_cctv_ubicacion'] ?? ''),
            'security_cctv_roles' => $this->e($config['security_cctv_roles'] ?? ''),
            'security_destruction_container' => $this->e($config['security_contenedor_destruccion'] ?? ''),
            'sendgrid_enabled' => trim((string) env('email.SMTPPass')) !== '',
            'openai_enabled' => trim((string) env('openai.apiKey')) !== '',
            'area' => $this->e($config['area_responsable'] ?? 'Administracion'),
            'hours' => $this->e($config['horario_atencion'] ?? 'Por completar'),
            'approval_body' => $this->e($config['organo_aprobacion'] ?? 'Por completar'),
            'approval_date' => $this->e($config['fecha_aprobacion'] ?? 'Por completar'),
            'effective_date' => $this->e($config['fecha_vigencia'] ?? 'Por completar'),
            'publication_medium' => $this->e($config['medio_publicacion'] ?? 'Por completar'),
            'policy_url' => $this->e($config['url_politica'] ?? ''),
            'uses_video' => ! empty($config['usa_videovigilancia']),
            'records_video' => ! empty($config['graba_videovigilancia']),
            'video_retention_days' => max(0, (int) ($config['plazo_grabaciones_dias'] ?? 0)),
            'video_retention_reason' => $this->e($config['justificacion_retencion_video'] ?? ''),
            'video_biometric_identification' => ! empty($config['video_identificacion_biometrica']),
            'uses_biometrics' => ! empty($config['usa_biometria']),
            'biometric_type' => $this->e($config['tipo_biometria'] ?? ''),
            'biometric_alternative' => $this->e($config['alternativa_biometrica'] ?? ''),
            'biometric_purpose' => $this->e($config['finalidad_biometria'] ?? ''),
            'biometric_deletion_days' => max(0, (int) ($config['plazo_supresion_biometria_dias'] ?? 0)),
            'video_zones' => $this->e($config['zonas_vigiladas'] ?? ''),
            'public_processors' => $this->e($config['encargados_publicos'] ?? ''),
            'copy_channel' => $this->e($config['canal_entrega_copia'] ?? 'correo electronico informado por el Titular'),
            'international_transmission' => ! empty($config['transmision_internacional']),
            'transmission_countries' => $this->e($config['paises_transmision'] ?? ''),
            'international_transfer' => ! empty($config['transferencia_internacional']),
            'transfer_countries' => $this->e($config['paises_transferencia'] ?? ''),
            'foreign_recipient' => $this->e($config['receptor_exterior'] ?? ''),
            'transfer_guarantee' => $this->e($this->transferGuaranteeLabel((string) ($config['garantia_transferencia'] ?? ''))),
            'transfer_guarantee_detail' => $this->e($config['garantia_transferencia_detalle'] ?? ''),
            'transfer_requires_consent' => ! empty($config['transferencia_requiere_autorizacion']),
            'national_transfer' => ! empty($config['transferencia_nacional']),
            'national_recipient' => $this->e($config['responsable_destinatario'] ?? ''),
            'national_transfer_purpose' => $this->e($config['finalidad_transferencia'] ?? ''),
            'publishes_debtors' => ! empty($config['publica_morosos']),
            'rnbd_required' => ! empty($config['obligado_rnbd']),
        ];
        return $context;
    }

    private function policy(array $c, array $bases, array $purposes): string
    {
        $sections = [
            ['Identificacion del Responsable', $this->identity($c) . '<p><strong>Area que atiende derechos:</strong> ' . $c['area'] . '. <strong>Horario:</strong> ' . $c['hours'] . '.</p>', true],
            ['Objeto, alcance y marco aplicable', '<p>Esta politica regula la recoleccion, almacenamiento, uso, circulacion, actualizacion, conservacion y supresion de datos personales en soportes fisicos, digitales y audiovisuales. Obliga a la administracion, trabajadores, contratistas, proveedores y Encargados que intervengan en el tratamiento. Se aplica conforme a la Constitucion Politica, la Ley 1581 de 2012, sus normas reglamentarias y las instrucciones de la autoridad de proteccion de datos.</p>', true],
            ['Principios rectores', '<p>El tratamiento observara legalidad, finalidad, libertad, veracidad o calidad, transparencia, acceso y circulacion restringida, seguridad y confidencialidad. La propiedad horizontal y sus obligaciones legales o contractuales no sustituyen automaticamente la autorizacion: cada tratamiento debe contar con una finalidad informada y una base juridica aplicable.</p>', true],
            ['Categorias de Titulares, datos y finalidades', $this->publicDataSummary($bases, $purposes), true],
            ['Derechos de los Titulares', '<p>El Titular puede conocer, actualizar y rectificar sus datos; solicitar prueba de la autorizacion; conocer el uso dado a su informacion; acceder gratuitamente a ella; presentar consultas y reclamos; y solicitar revocatoria o supresion cuando resulte procedente. Puede presentar queja ante la Superintendencia de Industria y Comercio una vez agotado el tramite ante el Responsable o Encargado.</p>', true],
            ['Autorizacion y deber de informar', '<p>Cuando sea exigible, la autorizacion sera previa, expresa e informada y quedara en un medio consultable posteriormente. Antes de solicitarla se informaran las finalidades, los derechos, los canales del Responsable y el caracter facultativo de responder preguntas sobre datos sensibles o de menores. Las excepciones a la autorizacion se documentaran y aplicaran de manera restrictiva.</p>', true],
            ['Datos sensibles, imagenes y datos de menores', '<p>Las fotografias y grabaciones son datos personales cuando permiten identificar a una persona, pero no son sensibles por ese solo hecho. Las plantillas biometricas y demas datos destinados a identificar de manera unica a una persona son sensibles. Su suministro es facultativo, salvo excepcion legal, y exige autorizacion explicita y controles reforzados. Los datos de ninos, ninas y adolescentes se trataran respetando su interes superior, derechos prevalentes y participacion segun su madurez.</p>', true],
            ['Videovigilancia', $this->videoPolicy($c), $c['uses_video']],
            ['Identificacion biometrica', '<p>Se trataran ' . ($c['biometric_type'] ?: 'datos biometricos') . ' exclusivamente para ' . ($c['biometric_purpose'] ?: 'la finalidad informada antes de la recoleccion') . '. La autorizacion explicita es voluntaria y se ofrece la alternativa <strong>' . ($c['biometric_alternative'] ?: 'no biometrica definida por la administracion') . '</strong>. La identificacion biometrica mediante video, cuando se encuentre habilitada, constituye un tratamiento distinto de la videovigilancia ordinaria y requiere informacion y evaluacion separadas.</p>', $c['uses_biometrics'] || $c['video_biometric_identification']],
            ['Transmision de datos', $this->transmissionPolicy($c), true],
            ['Transferencia internacional', '<p>La entrega de datos a otro Responsable ubicado fuera de Colombia se limita a los paises o territorios <strong>' . $c['transfer_countries'] . '</strong> y se realizara cuando exista autorizacion, nivel adecuado de proteccion, declaracion de conformidad u otra excepcion legal aplicable.</p>', $c['international_transfer']],
            ['Informacion sobre obligaciones en mora', '<p>Cuando resulte necesario informar incumplimientos dentro de la copropiedad, solo se divulgara el hecho de la mora y los identificadores indispensables. La comunicacion se ubicara en espacios internos sin transito constante de visitantes, se mantendra actualizada y permitira rectificacion inmediata. El valor adeudado no se publicara de forma automatica y la medida debera ser necesaria y proporcional.</p>', $c['publishes_debtors']],
            ['Seguridad, conservacion y eliminacion', '<p>Se aplicaran medidas administrativas, humanas, fisicas y tecnicas proporcionales al riesgo. Los datos se conservaran solo durante el plazo necesario para la finalidad o una obligacion aplicable y despues se suprimiran, anonimizaran o bloquearan de forma controlada. El inventario interno contiene ubicaciones, plazos y controles detallados y no se publica por razones de seguridad.</p>', true],
            ['Consultas, reclamos, revocatorias y supresion', '<p>Las solicitudes se presentan por los canales indicados en esta politica. Las consultas se atienden en diez (10) dias habiles, prorrogables por cinco (5); los reclamos completos, en quince (15) dias habiles, prorrogables por ocho (8), con informacion previa de la causa. La supresion puede limitarse cuando exista un deber legal o contractual documentado de conservacion.</p>', true],
            ['Registro Nacional de Bases de Datos', '<p>El Responsable mantendra actualizada la informacion exigida en el Registro Nacional de Bases de Datos y efectuara los reportes que correspondan mientras permanezca obligado.</p>', $c['rnbd_required']],
            ['Aprobacion, vigencia y cambios', '<p>La politica fue aprobada por <strong>' . $c['approval_body'] . '</strong> el <strong>' . $c['approval_date'] . '</strong>, rige desde el <strong>' . $c['effective_date'] . '</strong> y se publica por <strong>' . $c['publication_medium'] . '</strong>. Los cambios sustanciales en la identidad del Responsable o las finalidades seran comunicados y, cuando corresponda, se obtendra una nueva autorizacion.</p>', true],
        ];
        return $this->document('Politica de Tratamiento de Datos Personales', $c, $this->numberedSections($sections));
    }

    private function notice(array $c, array $bases, array $purposes): string
    {
        return $this->document('Aviso de Privacidad', $c,
            $this->identity($c) . '<h2>Finalidades del tratamiento</h2>' . $this->purposeSummary($bases, $purposes) .
            '<p>Las respuestas relacionadas con datos sensibles o datos de ninos, ninas y adolescentes son facultativas, salvo una excepcion legal aplicable y debidamente informada.</p>' .
            $this->rightsAndChannels($c) . $this->policyAccess($c) .
            '<p>Este aviso resume la politica y no reemplaza la autorizacion cuando sea necesaria. Cada canal de recoleccion utiliza la variante vigente que corresponde a su contexto, identificada con version y hash propios.</p>');
    }

    private function authorization(array $c, array $bases, array $purposes): string
    {
        $excepted = $this->authorizationPurposeItems($bases, $purposes, 'excepcion_legal');
        $consented = $this->authorizationPurposeItems($bases, $purposes, 'autorizacion');
        $sensitive = $this->sensitiveAuthorizationBlock($bases, $purposes);
        $hasMinors = count(array_filter($bases, static fn ($base) => ! empty($base['datos_menores']))) > 0;
        $processors = $c['public_processors'] !== ''
            ? '<p>Los siguientes Encargados tratan datos por cuenta e instrucciones del Responsable, bajo obligaciones de seguridad y confidencialidad: <strong>' . $c['public_processors'] . '</strong>.</p>'
            : '<p>Cuando intervengan Encargados, su identidad y funcion se informaran en la instancia presentada al Titular antes de solicitar su decision.</p>';
        $transfer = ($c['national_transfer'] ? '<p>Transferencia nacional a <strong>' . $c['national_recipient'] . '</strong>, como Responsable, para <strong>' . $c['national_transfer_purpose'] . '</strong>.</p>' : '') .
            ($c['international_transfer'] ? '<p>Transferencia internacional a <strong>' . $c['foreign_recipient'] . '</strong>, en <strong>' . $c['transfer_countries'] . '</strong>, bajo la garantia <strong>' . $c['transfer_guarantee'] . '</strong>' . ($c['transfer_guarantee_detail'] ? ' (' . $c['transfer_guarantee_detail'] . ')' : '') . '.' . ($c['transfer_requires_consent'] ? ' La instancia exige una decision expresa e independiente.' : '') . '</p>' : '');

        return $this->document('Autorizacion para el Tratamiento de Datos Personales', $c,
            '<h2>1. Responsable y alcance</h2>' . $this->identity($c) . '<p>La instancia de esta autorizacion identifica el perfil del Titular, las bases aplicables y la version vigente de la Politica, disponible en <strong>' . ($c['policy_url'] ?: $c['publication_medium']) . '</strong>.</p>' .
            '<h2>2. Tratamientos y decisiones</h2><h3>2.1 Tratamientos informados que no dependen de esta autorizacion</h3><p>Solo pueden incluirse aqui tratamientos respaldados por una excepcion legal documentada. La negativa no los impide, pero el Titular conserva sus derechos de conocimiento, actualizacion, rectificacion y reclamo.</p>' . $excepted .
            '<h3>2.2 Finalidades que requieren decision</h3><p>Cada finalidad se presenta con sus datos, base y consecuencia concreta. El Titular debe responder individualmente Autorizo o No autorizo; no existe una decision general, las opciones aparecen sin preseleccion y el silencio no constituye autorizacion.</p>' . $consented .
            $sensitive .
            ($c['uses_biometrics'] || $c['video_biometric_identification'] ? '<h3>2.4 Biometria: decision independiente</h3><p>El tratamiento de <strong>' . $c['biometric_type'] . '</strong> para <strong>' . $c['biometric_purpose'] . '</strong> requiere una decision expresa separada. Negarse no restringe el ingreso ni genera cobro o consecuencia adversa: se encuentra disponible la alternativa permanente y gratuita <strong>' . $c['biometric_alternative'] . '</strong>. El dato se suprimira al terminar la relacion o, como maximo, dentro de <strong>' . $c['biometric_deletion_days'] . ' dias</strong>, salvo deber legal documentado.</p>' : '') .
            ($c['uses_video'] ? '<h3>2.5 Videovigilancia: informacion</h3><p>Las zonas <strong>' . ($c['video_zones'] ?: 'identificadas mediante senalizacion') . '</strong> cuentan con camaras para seguridad. ' . ($c['records_video'] ? 'Las grabaciones se conservan ordinariamente hasta ' . $c['video_retention_days'] . ' dias.' : 'El monitoreo no graba ni almacena imagenes.') . ' Este bloque es informativo y no se presenta como autorizacion individual. Si existe identificacion biometrica sobre video, se activa el bloque biometrico independiente.</p>' : '') .
            ($hasMinors ? '<h3>2.6 Datos de menores</h3><p>La instancia identifica al menor y a su representante legal, verifica la calidad de representacion y deja constancia de que se escucho y valoro la opinion del menor segun su madurez. El tratamiento debe responder a su interes superior y respetar sus derechos prevalentes; no se admiten finalidades de mercadeo o perfilamiento.</p>' : '') .
            '<h2>3. Derechos del Titular</h2><p>El Titular puede conocer, actualizar y rectificar sus datos; solicitar prueba de la autorizacion; ser informado sobre el uso; presentar quejas ante la Superintendencia de Industria y Comercio; revocar la autorizacion y solicitar supresion cuando proceda; y acceder gratuitamente a sus datos. Puede ejercerlos por los canales del Responsable indicados al inicio.</p>' .
            '<h2>4. Acceso por terceros</h2>' . $processors . $transfer .
            '<h2>5. Conservacion</h2><p>Los datos se tratan durante la relacion que justifica cada finalidad y durante los plazos legales aplicables. Despues se suprimen, anonimizan o bloquean conforme a la Politica.</p>' .
            '<h2>6. Libertad de la decision</h2><p>Negar una finalidad opcional solo produce la consecuencia concreta informada junto a ella. Negarse a entregar datos sensibles o biometricos nunca restringe el acceso a la copropiedad ni a servicios esenciales.</p>' .
            '<h2>7. Revocatoria y supresion</h2><p>La autorizacion puede revocarse total o parcialmente y puede solicitarse la supresion sin costo. No procede respecto de datos que deban conservarse por un deber legal o contractual vigente; fuera de esos casos se atiende conforme a la Politica. Cada revocatoria se registra como un nuevo evento sin alterar la evidencia original.</p>' .
            '<h2>8. Evidencia de la decision</h2><p>Antes de registrar la decision, el sistema genera la instancia final que el Titular visualiza. Conserva esa instancia completa, su hash SHA-256, vector de decisiones, fecha y hora del servidor, zona horaria, canal, tipo de aceptacion y metodo de verificacion de identidad. En canal digital tambien conserva IP e identificador del navegador con finalidad exclusivamente probatoria; no captura geolocalizacion ni huella ampliada del dispositivo. Se entrega copia por <strong>' . $c['copy_channel'] . '</strong>.</p>' .
            '<h2>9. Aceptacion</h2><p>La instancia registra la identidad y calidad del otorgante, su mecanismo de aceptacion y un resumen de solo lectura generado desde las decisiones individuales. Ninguna decision general sustituye las respuestas por finalidad.</p>');
    }

    private function procedure(array $c): string
    {
        return $this->document('Procedimiento de Consultas, Reclamos, Rectificacion, Actualizacion, Revocatorias y Supresion', $c,
            '<p><strong>' . $c['name'] . '</strong>, como Responsable, adopta este procedimiento conforme a la Ley 1581 de 2012 y el Decreto 1074 de 2015. Se aplica mediante <strong>' . $c['app_name'] . '</strong>.</p>' .
            '<h2>1. Canales, gratuidad y radicacion</h2><p>Las solicitudes se reciben gratuitamente por ' . $c['email'] . ', mediante comunicacion escrita en ' . $c['address'] . ', ' . $c['city'] . ', y en ' . $c['app_name'] . '. No se exige presentacion personal, autenticacion notarial, huella ni formalidades adicionales.</p><p>Cada solicitud recibe radicado unico y acuse con fecha y hora real, clasificacion, termino y fecha estimada. Las recibidas en dia inhabil o despues de las <strong>' . $c['request_cutoff'] . '</strong> se entienden recibidas el siguiente dia habil para el computo, conservando la hora real. Se excluyen sabados, domingos y festivos colombianos.</p>' .
            '<h2>2. Clasificacion</h2><p>Los tramites son consulta; reclamo; rectificacion; actualizacion; revocatoria total o parcial; y supresion. La consulta permite conocer toda la informacion vinculada al Titular. Rectificacion, actualizacion, revocatoria y supresion siguen el tramite de reclamo. Una reclasificacion se motiva, conserva la radicacion original y no perjudica los terminos.</p>' .
            '<h2>3. Legitimacion e identidad</h2><p>Pueden actuar el Titular; causahabientes que acrediten fallecimiento y vinculo; representantes o apoderados; quien actue por estipulacion a favor de otro; y representantes facultados de ninos, ninas y adolescentes. La prueba sera proporcional al canal. No se retienen copias innecesarias ni se solicitan documentos que el Responsable ya posea. Todo rechazo se motiva y, si se trata de reclamo, se tramita como subsanacion.</p>' .
            '<h2>4. Consultas</h2><p>Solo requieren identificar al Titular o legitimado y el medio de respuesta; no se exigen hechos, motivos ni documentos distintos de identidad o legitimacion. Se atienden en diez (10) dias habiles desde su recibo. Antes del vencimiento puede informarse una prorroga motivada, hasta cinco (5) dias habiles adicionales. La respuesta cubre todas las bases propias y de Encargados, sus finalidades y las bases revisadas. No se aplican subsanacion ni desistimiento de reclamos.</p><p>La consulta es gratuita al menos una vez por mes calendario y cuando cambie sustancialmente la Politica. Solo copias extraordinarias pueden generar costos efectivos previamente informados.</p>' .
            '<h2>5. Reclamos: tramite comun</h2><p>El reclamo contiene identificacion, hechos, medio de contacto y documentos que se quieran hacer valer. Si esta incompleto, dentro de cinco (5) dias habiles se indica precisamente lo faltante; el termino de fondo no inicia hasta completarse. El desistimiento solo puede declararse y comunicarse despues de dos (2) meses contados desde el requerimiento, sin impedir un nuevo reclamo.</p><p>Si el receptor no es competente, traslada en dos (2) dias habiles, informa destinatario y fecha y deja constancia. Recibido el reclamo completo, en maximo dos (2) dias habiles se marca cada base con "reclamo en tramite" y motivo; la leyenda permanece hasta la decision y su retiro deja traza.</p><p>Se resuelve en quince (15) dias habiles desde el dia siguiente al recibo completo. Si hubo subsanacion, desde el dia siguiente a completarse. Solo antes del vencimiento puede comunicarse prorroga motivada, con fecha no superior a ocho (8) dias habiles despues del termino inicial.</p>' .
            '<h2>6. Rectificacion y actualizacion</h2><p>Se tramitan como reclamo. Una decision favorable corrige o actualiza bases activas, archivos, integraciones, Encargados y destinatarios. Dentro de dos (2) dias habiles se ejecuta en sistemas propios y se ordena a terceros, exigiendo confirmacion. El expediente conserva hash del valor anterior, valor nuevo, fuente y fecha; ninguna restauracion puede reintroducir el valor anterior.</p>' .
            '<h2>7. Revocatoria y supresion</h2><p>Se tramitan como reclamo, con termino de quince (15) dias habiles y prorroga maxima de ocho (8). La revocatoria puede afectar una o varias finalidades y actualiza el historial de consentimiento y la lista de exclusion. Una sola solicitud de supresion cubre todas las bases, archivos, integraciones, Encargados, destinatarios y respaldos.</p><p>Supresion significa eliminacion irreversible; anonimizar exige ruptura irreversible del vinculo; si es reversible es seudonimizacion y no equivale a supresion. El bloqueo solo procede por deber legal o contractual documentado, restringe todo uso distinto de conservacion y auditoria, identifica datos y plazo y programa la supresion al vencimiento.</p><p>Dentro de dos (2) dias habiles de la decision se ordena a Encargados, destinatarios y subencargados ejecutar y confirmar. La respuesta reporta el estado de cada tercero.</p>' .
            '<h2>8. Respaldos y prohibicion de reactivacion</h2><p>La lista de exclusion almacena identificadores mediante hash, radicado, fecha y alcance, sin documento o correo en claro. Se consulta obligatoriamente en restauraciones, integraciones, importaciones y altas. Los respaldos inmutables rotan en maximo <strong>' . $c['backup_rotation_days'] . ' dias</strong>.</p><p>Antes de restaurar se reaplica la lista y se registra respaldo, version, registros filtrados y verificacion. La reaparicion de un dato suprimido se bloquea y genera incidente de seguridad.</p>' .
            '<h2>9. Respuesta y cierre</h2><p>Toda respuesta identifica radicado, tipo, bases revisadas, decision, fundamento, acciones por base, excepciones con datos y plazo, terceros notificados y fecha efectiva. Si es negativa o parcial, informa que agotado el tramite puede presentarse queja ante la Delegatura para la Proteccion de Datos Personales de la SIC. El PDF se sella con hash y se conserva evidencia de envio, entrega o rebote; el rebote activa reintento.</p>' .
            '<h2>10. Expediente y seguridad</h2><p>El expediente cronologico contiene acuse, verificaciones, requerimientos, subsanaciones, traslados, leyendas, prorrogas, decision, ordenes, confirmaciones, ejecucion y entrega. Cada actuacion se sella con hash y hora de servidor. Se minimizan datos, se restringe y audita el acceso y se conserva durante <strong>' . $c['request_file_years'] . ' anos</strong> desde el cierre, para luego eliminarse o anonimizarse.</p>' .
            '<h2>11. Alertas y vencimientos</h2><p>El sistema calcula terminos con calendario colombiano, alerta al 60 %, escala al 85 %, impide prorrogas extemporaneas y registra los vencimientos sin ocultarlos. Si falta calendario anual, bloquea calculos que lo atraviesen.</p>' .
            '<h2>12. Coherencia documental</h2><p>Canales, responsables, finalidades, Encargados y conservacion coinciden con la Politica, Avisos y Autorizacion. Todo cambio exige revisar y versionar este procedimiento.</p>');
    }

    private function security(array $c, array $bases, array $purposes, array $runtime = []): string
    {
        $db = null;
        $thirdParties = $runtime['third_parties'] ?? [];
        if (! array_key_exists('third_parties', $runtime)) {
            try {
                $db = db_connect();
                $thirdParties = $db->table('dp_terceros')->where('cliente_id', $c['client_id'])->where('activo', 1)->orderBy('nombre')->get()->getResultArray();
                $thirdParties = (new PrivacyVault())->decryptRows('dp_terceros', $thirdParties);
            } catch (\Throwable) {
                // Rendering remains deterministic in isolated template tests without a database driver.
            }
        }
        $versions = $runtime['document_versions'] ?? [];
        foreach (['politica', 'aviso', 'autorizacion', 'procedimiento'] as $type) {
            if (isset($versions[$type])) { continue; }
            $dependency = $db ? $db->table('dp_documentos')->select('version')->where('cliente_id', $c['client_id'])
                ->where('tipo', $type)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray() : null;
            $versions[$type] = $dependency ? (int) $dependency['version'] : 'pendiente';
        }
        $sensitive = array_filter($bases, static fn ($base) => ! empty($base['datos_sensibles']));
        $minors = array_filter($bases, static fn ($base) => ! empty($base['datos_menores']));
        $biometric = $c['uses_biometrics'] || $c['video_biometric_identification'] || array_filter($bases, static fn ($base) => ! empty($base['datos_biometricos']));
        $nextReview = $c['approval_date'] !== 'Por completar' ? date('Y-m-d', strtotime($c['approval_date'] . ' +1 year')) : 'definida al aprobar';
        $processorRows = '';
        foreach ($thirdParties as $third) {
            $processorRows .= '<tr><td>' . $this->e($third['nombre']) . '</td><td>' . $this->e($third['servicio'] ?? '') . '</td><td>' . $this->e($third['pais'] ?? 'Colombia') . '</td><td>' . (! empty($third['contrato_vigente']) ? 'Vigente' : 'Pendiente') . '</td><td>' . $this->e(substr((string) ($third['evaluado_at'] ?? ''), 0, 10)) . '</td></tr>';
        }
        $processors = '<table><thead><tr><th>Encargado</th><th>Funcion</th><th>Pais</th><th>Contrato</th><th>Evaluacion</th></tr></thead><tbody>' . ($processorRows ?: '<tr><td colspan="5">Sin Encargados aprobados</td></tr>') . '</tbody></table>';
        $video = $c['uses_video'] ? '<h3>10.5 Videovigilancia</h3><p>Las grabaciones se almacenan en <strong>' . $c['security_cctv_location'] . '</strong>; acceden exclusivamente ' . $c['security_cctv_roles'] . '. Toda visualizacion o copia registra persona, fecha y motivo. Se conservan ' . $c['video_retention_days'] . ' dias y luego se eliminan o sobrescriben, salvo preservacion segregada por autoridad o actuacion en curso. La entrega de imagenes sigue el Documento 4 y protege a terceros.</p>' : '';
        $biometrics = $biometric ? '<h3>8.8 Biometria</h3><p>Las plantillas se cifran en reposo, no se exportan y se suprimen dentro de ' . $c['biometric_deletion_days'] . ' dias del cese del vinculo o la revocatoria. La alternativa no biometrica operativa es <strong>' . $c['biometric_alternative'] . '</strong>. El tratamiento exige la decision explicita del Documento 3 y, cuando exista decision automatizada, revision humana por el Documento 4.</p>' : '';
        $minorRules = $minors ? '<h3>8.9 Menores</h3><p>Los datos marcados como pertenecientes a menores no se exportan, no alimentan integraciones externas y no se usan en pruebas. Solo se exceptua requerimiento de autoridad registrado y validado por el Oficial.</p>' : '';
        $sensitiveRules = $sensitive ? '<p>Los datos sensibles tienen lista nominal de acceso; toda consulta queda registrada sin copiar el contenido sensible al log.</p>' : '';
        $cloud = $c['security_cloud'] ? '<h3>8.10 Nube</h3><p>El alojamiento en ' . $c['security_cloud_provider'] . ' (' . $c['security_cloud_country'] . ') es una transmision a Encargado y exige contrato, pais y garantia documentados antes de operar.</p>' : '';
        $remote = $c['security_remote_work'] ? '<h3>10.6 Trabajo remoto</h3><p>Todo acceso remoto usa MFA y canal cifrado desde equipos con bloqueo de pantalla y disco cifrado. Se prohibe almacenar exportaciones en equipos personales, usar redes publicas sin proteccion o retirar papel sin prestamo registrado.</p>' : '';
        $sendgrid = $c['sendgrid_enabled'] ? '<h3>11.4 SendGrid</h3><p>El correo transaccional opera como transmision internacional a Encargado. Existe contrato o DPA vigente; se minimiza el contenido, nunca se incluyen datos sensibles en asuntos y aplica el aviso contractual de incidentes del numeral 11.1.</p>' : '';
        $openai = $c['openai_enabled'] ? '<h3>11.5 OpenAI</h3><p>La integracion solo recibe estructura del inventario, nunca identidades ni registros de Titulares. Un filtro previo se prueba cada ' . $c['security_ai_months'] . ' meses y en cada cambio. Una falla bloquea la integracion y abre incidente. El contrato o DPA y las condiciones de no entrenamiento se conservan como evidencia.</p>' : '';

        return $this->document('Manual Interno de Seguridad de la Informacion Personal', $c,
            '<p><strong>Acta de aprobacion:</strong> ' . $c['security_acta'] . '. <strong>Proxima revision:</strong> ' . $nextReview . '. Documento controlado de circulacion interna; el visor verifica el hash SHA-256 de la instancia final resuelta.</p>' .
            '<h2>1. Objeto, alcance y marco</h2><p>Este manual desarrolla los principios de seguridad y confidencialidad y el deber de adoptar procedimientos internos efectivos y verificables. Complementa la Politica v' . $versions['politica'] . ', el Aviso v' . $versions['aviso'] . ', la Autorizacion v' . $versions['autorizacion'] . ' y el Procedimiento de derechos v' . $versions['procedimiento'] . '.</p><p>Aplica a todos los datos personales, soportes, sedes ' . $c['security_locations'] . ', aplicativo, correo, archivos, usuarios y Encargados, durante recoleccion, almacenamiento, uso, circulacion, respaldo, bloqueo y supresion.</p>' .
            '<h2>2. Clasificacion y activos</h2><p>El inventario clasifica informacion publica, privada o semiprivada, sensible y de menores. Son activos las bases, MySQL, respaldos, auditoria, expedientes, correo y archivos fisicos' . ($c['uses_video'] ? ', videovigilancia y grabaciones' : '') . ($biometric ? ', sistema biometrico y plantillas' : '') . '.</p>' . $sensitiveRules .
            '<h2>3. Gobierno y RACI</h2><p><strong>' . $c['approval_body'] . ':</strong> aprueba manual, Oficial, recursos, riesgos altos y excepciones. <strong>Administrador ' . $c['security_administrator'] . ':</strong> ejecuta el manual, autoriza accesos, firma reportes SIC y contratos. <strong>Oficial ' . $c['officer'] . ':</strong> designado mediante ' . $c['security_opd_designation'] . ', mantiene inventario, riesgos, incidentes, Encargados, capacitacion e indicadores y reporta a ' . $c['security_opd_report'] . '. <strong>TI ' . $c['security_it_provider'] . ':</strong> ejecuta y evidencia controles tecnicos. Porteria custodia registros; cada usuario usa cuenta individual, avisa incidentes y firma confidencialidad.</p>' .
            '<h2>4. Inventario sujeto a controles</h2>' . $this->securityInventory($bases, $purposes, $thirdParties) . '<p>El Oficial lo revisa cada ' . $c['security_inventory_months'] . ' meses y ante cambios. Sin inventario completo y aprobado no se publica el manual; un cambio exige nueva version y hash.</p>' .
            '<h2>5. Evaluacion de riesgos</h2><p>El Oficial, TI y Administrador documentan amenaza, probabilidad, impacto, controles, riesgo residual y plan cada ' . $c['security_risk_months'] . ' meses y ante nueva base, Encargado, canal, incidente grave o cambio normativo. Todo riesgo alto tiene responsable y fecha; solo ' . $c['approval_body'] . ' puede aceptarlo.</p>' .
            '<h2>6. Control de acceso logico</h2><p>Rigen necesidad de conocer, minimo privilegio, segregacion por cliente y cuentas individuales. Administrador solicita altas/cambios y TI ejecuta; cada cuenta debe tener rol y alcance autorizados. La desvinculacion se notifica el mismo dia y TI retira acceso en un dia habil. Cada ' . $c['security_access_months'] . ' meses se recertifican cuenta y permiso; lo no certificado se suspende. La sesion cierra tras ' . $c['security_timeout'] . ' minutos y bloquea luego de ' . $c['security_attempts'] . ' fallos.</p>' .
            '<h2>7. Autenticacion</h2><p>Administracion, soporte y acceso remoto usan MFA. Las contrasenas tienen minimo ' . $c['security_password_length'] . ' caracteres, no se comparten y cambian ante compromiso. La recuperacion no revela existencia de cuentas y queda auditada.</p>' .
            '<h2>8. Controles tecnicos</h2><p>Toda conexion usa TLS. Respaldos y datos de riesgo elevado se cifran en reposo. Llaves y secretos se administran en ' . $c['security_secret_manager'] . ', fuera del codigo, con custodio e inventario sin valores y rotacion cada ' . $c['security_secret_months'] . ' meses o ante retiro o sospecha.</p><p>Auditoria registra autenticacion, datos sensibles, cambios, permisos, exportaciones, supresiones y restauraciones en solo adicion durante ' . $c['security_log_months'] . ' meses. Parches criticos: 15 dias; otros: 90 dias. Pruebas usan datos ficticios o anonimizados. Exportaciones registran usuario, base, filtro, cantidad y finalidad; mas de ' . $c['security_export_threshold'] . ' registros alerta al Oficial.</p>' . $biometrics . $minorRules . $cloud .
            '<h2>9. Respaldos y restauracion</h2><p>Se realizan respaldos cifrados con retencion de ' . $c['security_backup_days'] . ' dias, copia separada y acceso auditado. Toda restauracion reaplica automaticamente la lista de exclusiones antes de habilitar datos y deja acta con ordenante, motivo, alcance, version de lista, conteo filtrado y verificacion. El proceso se bloquea si el filtro no opera. Cada ' . $c['security_restore_months'] . ' meses se prueba en ambiente aislado; una falla abre plan correctivo e incidente si reaparece un dato suprimido.</p>' .
            '<h2>10. Seguridad fisica</h2><p>El archivo en ' . $c['security_archive_location'] . ' queda bajo custodia de ' . $c['security_archive_custodian'] . '. Cada prestamo registra documento, persona, fecha, devolucion y firma. Porteria impide fotografia o consulta de minutas; impresiones se retiran de inmediato y el descarte va a ' . $c['security_destruction_container'] . '. Llaves fisicas tienen asignacion nominal y su perdida es incidente.</p>' . $video . $remote .
            '<h2>11. Encargados y subencargados</h2><p>Ningun tercero recibe datos sin contrato de transmision que limite instrucciones, imponga confidencialidad y seguridad, apoyo a derechos, aviso de incidente en tres dias calendario, autorizacion previa de subencargados y devolucion o supresion certificada. El Oficial evalua cada Encargado antes de contratar y cada ' . $c['security_processor_months'] . ' meses.</p>' . $processors . $sendgrid . $openai .
            '<h2>12. Gestion de incidentes</h2><p>Incidente es la violacion de seguridad, perdida, robo, alteracion, divulgacion o acceso no autorizado a datos administrados por el Responsable o sus Encargados. Se avisa por <strong>' . $c['security_incident_channel'] . '</strong> dentro de 24 horas. El Oficial fija folio y fecha de conocimiento, preserva evidencia antes de corregir, clasifica S1-S4, contiene, investiga, recupera y cierra con lecciones.</p><p><strong>Reporte obligatorio:</strong> cuando el evento encuadra en el numeral 2.1.f.(ii) del Titulo V de la Circular Unica SIC, se reporta dentro de quince (15) dias habiles desde deteccion y conocimiento del area encargada: por RNBD si existe obligacion de registro o por el micrositio/canal habilitado de la Delegatura si no existe. El plazo es fijo y no configurable. Toda decision, incluido no reportar por no encuadrar, exige motivacion y evidencia. Si puede causar perjuicio o requiere accion del Titular, se comunica en diez dias calendario desde clasificacion, o se motiva la omision.</p>' .
            '<h2>13. Conservacion, bloqueo y destruccion</h2><p>Cada base sigue el inventario. El bloqueo restringe todo uso salvo el deber que lo justifica y programa supresion al vencer. La supresion ordenada por el Documento 4 alimenta exclusiones y queda auditada. Anonimizar exige irreversibilidad. Papel se destruye por corte cruzado o certificado; discos mediante borrado verificado o destruccion fisica; Encargados certifican; respaldos expiran y siempre aplican exclusiones.</p>' .
            '<h2>14. Capacitacion y compromiso</h2><p>Las personas autorizadas reciben lineamientos de seguridad y proteccion de datos acordes con sus funciones. El refuerzo ocurre cada ' . $c['security_training_months'] . ' meses, incluye porteria y terceros permanentes y deja evidencia mediante los procedimientos internos definidos por el Responsable. Meta minima: 95%; los incumplimientos se gestionan conforme a su gravedad y al vinculo aplicable.</p>' .
            '<h2>15. Verificacion, excepciones y disciplina</h2><p>El Oficial presenta indicadores semestrales. Auditor independiente cada 24 meses o tras incidente S1. Toda excepcion es escrita, evaluada, aprobada por ' . $c['approval_body'] . ', dura maximo 90 dias y tiene control compensatorio. Incumplimientos se gestionan conforme al vinculo y como incidente cuando exponen datos.</p>' .
            '<h2>16. Control de cambios</h2><p>Revision anual y extraordinaria ante cambio normativo, incidente S1-S2 o cambio de aplicativo, inventario o Encargado. Toda modificacion genera version, aprobacion, hash sobre instancia resuelta e historial inalterable.</p><p>Los derechos de los Titulares se ejercen conforme al Documento 4 y la Politica.</p>');
    }

    private function confidentiality(array $c): string
    {
        return $this->document('Compromiso de Confidencialidad y Uso Autorizado', $c,
            '<p>Este documento maestro define las clausulas obligatorias de cada instancia individual. No se firma esta plantilla: el sistema resuelve identidad, vinculo, rol, autorizador, bases, finalidades, operaciones, vigencia, condiciones aplicables y versiones documentales antes de habilitar la aceptacion.</p>' .
            '<h2>1. Identificacion y alcance cerrado</h2><p>Cada instancia identifica firmante, documento, vinculo, rol, autorizador distinto del firmante, fechas de acceso, bases concretas, finalidades pertenecientes a esas bases y operaciones de lista cerrada. Se prohiben expresiones como todas las bases, funciones inherentes o actividades relacionadas sin seleccion estructurada y justificacion.</p>' .
            '<h2>2. Naturaleza y clasificacion</h2><p>El compromiso protege datos personales, sensibles y de menores y otra informacion no personal legitimamente reservada. No es contrato de transmision de un Encargado persona juridica ni autorizacion para tratar datos del firmante. La informacion publica conserva las restricciones de finalidad cuando se usa por cuenta del Responsable.</p>' .
            '<h2>3. Uso autorizado y prohibiciones</h2><p>El firmante solo puede consultar, registrar, actualizar, exportar, suprimir, visualizar CCTV u operar biometria cuando cada operacion figure en su instancia. Se prohiben curiosidad, copias locales, capturas, fotografias, correo o mensajeria personal, USB no autorizado, fines propios, publicaciones, alteracion de registros, credenciales compartidas, acceso entre copropiedades y exploracion de fallas.</p>' .
            '<h2>4. Seguridad e incidentes</h2><p>Se exige cuenta individual, MFA segun rol, bloqueo de pantalla, custodia fisica, dispositivos autorizados y exportaciones trazables. Toda sospecha se reporta dentro de 24 horas por <strong>' . $c['security_incident_channel'] . '</strong>; se preserva evidencia y el firmante no investiga ni contacta Titulares, SIC, medios o terceros.</p>' .
            '<h2>5. Cambio, recertificacion y baja</h2><p>El cambio de rol cierra la instancia anterior y exige documentar el nuevo alcance. Los permisos tecnicos se administran mediante usuarios y roles, de forma independiente al flujo documental. Al terminar el vinculo se revocan credenciales, se devuelven soportes, se eliminan copias bajo instruccion y se certifica lo actuado.</p>' .
            '<h2>6. Subsistencia diferenciada</h2><p>La reserva sobre datos personales es indefinida y subsiste sin limite temporal. La reserva sobre otra informacion confidencial dura mientras conserve legitimamente ese caracter. No se limitan derechos irrenunciables ni denuncias ante autoridades.</p>' .
            '<h2>7. Consecuencias y debido proceso</h2><p>Las consecuencias dependen del vinculo y de la ley o contrato aplicable, con defensa y debido proceso. El compromiso no crea multas privadas, sanciones automaticas, responsabilidad objetiva ni renuncias.</p>' .
            '<h2>8. Instancia y evidencia electronica</h2><p>Solo se firma la instancia completa vista por el propio firmante. Se prueba con codigo al correo, firma, version, texto canonico, hash SHA-256, hora de servidor, canal, IP, navegador y entrega de copia. Los eventos son de solo adicion y toda divergencia de integridad bloquea la instancia.</p>');
    }

    private function processors(array $c): string
    {
        return $this->document('Acuerdo de Transmision y Tratamiento con Encargado', $c,
            '<p>Este maestro define las reglas obligatorias de cada contrato individual. No se firma esta plantilla: el aplicativo resuelve partes, representacion, contrato principal, test de rol, servicio, bases, Titulares, categorias, finalidades, operaciones, sistemas, paises, subencargados, medidas, vigencia, anexos y versiones antes de permitir la suscripcion.</p>' .
            '<h2>1. Definiciones</h2><p>Responsable, Encargado, Transmision y Transferencia conservan el alcance de la Ley 1581 y del Decreto 1074. Una etiqueta contractual no sustituye el test material de rol por operacion.</p>' .
            '<h2>2. Objeto y alcance cerrado</h2><p>Cada instancia individualiza las actividades por cuenta del Responsable y prohibe fines propios, cruces, perfiles, entrenamiento de IA y conservacion no instruida.</p>' .
            '<h2>3. Naturaleza de la operacion</h2><p>Se distingue transmision nacional o internacional de transferencia a otro Responsable; este acuerdo no autoriza transferencias.</p>' .
            '<h2>4. Declaracion de rol</h2><p>Solo se genera para Encargado o rol dual delimitado. Revisor fiscal, abogado con autonomia y cualquier receptor que decida finalidades siguen la ruta de Responsable independiente.</p>' .
            '<h2>5. Instrucciones documentadas</h2><p>Emisor facultado, canal, fecha, contenido, acuse y objecion por ilicitud quedan registrados.</p>' .
            '<h2>6. Confidencialidad y personal</h2><p>Acceso necesario, compromiso individual equivalente al Documento 6, capacitacion y baja o cambio en un dia habil.</p>' .
            '<h2>7. Deberes del Encargado</h2><p>Se incorporan los deberes del articulo 18 de la Ley 1581 sin trasladar decisiones exclusivas del Responsable.</p>' .
            '<h2>8. Seguridad</h2><p>La instancia fija nivel basico, medio o alto y medidas verificables de acceso, MFA, cifrado, auditoria, segregacion, respaldo, restauracion, vulnerabilidades, continuidad y destruccion.</p>' .
            '<h2>9. Derechos</h2><p>Remision al Responsable en dos dias, asistencia en tres y supresion instruida en produccion en cinco, sin respuesta de fondo autonoma.</p>' .
            '<h2>10. Incidentes</h2><p>Aviso por canal probado en uno a tres dias habiles, contenido minimo, actualizaciones, preservacion e informe final, para proteger el termino regulatorio del Responsable.</p>' .
            '<h2>11. Subencargados</h2><p>Inventario, DPA equivalente, pais, datos y aprobacion previa expresa; el silencio no aprueba y el Encargado principal responde.</p>' .
            '<h2>12. Ubicaciones e internacional</h2><p>Paises declarados y verificacion fechada de la lista vigente. La Circular 002 de 2025 trata transferencia de tecnologia; las clausulas tipo internacionales corresponden a la Circular 003 de 2025 y son voluntarias salvo adopcion.</p>' .
            '<h2>13. Autoridades</h2><p>Competencia, minimizacion, registro y aviso previo o diferido.</p>' .
            '<h2>14. Evidencia y auditoria</h2><p>Autoevaluacion, informes disponibles y auditoria proporcional, limitada y trazable.</p>' .
            '<h2>15. Responsabilidad</h2><p>Responsabilidad demostrada por el propio incumplimiento, indemnidades reciprocas y exclusiones proporcionadas, sin sanciones privadas.</p>' .
            '<h2>16. Vigencia</h2><p>No excede el contrato principal y conserva obligaciones que por naturaleza sobreviven.</p>' .
            '<h2>17. Devolucion y supresion</h2><p>Instruccion escrita, retorno estructurado, supresion de produccion, bloqueo y rotacion de respaldos, lista de no resucitar, excepcion legal y certificacion.</p>' .
            '<h2>18. Categorias especiales</h2><p>Sensibles, biometria, CCTV y menores activan condiciones reforzadas y nivel alto.</p>' .
            '<h2>19. Documentos y prelacion</h2><p>La instancia vincula versiones publicadas de los Documentos 1 a 6 y anexos A a F.</p>' .
            '<h2>20. Incumplimiento</h2><p>Subsanacion y causales graves de terminacion inmediata claramente delimitadas.</p>' .
            '<h2>21. Ley, controversias y firma</h2><p>Ley colombiana, arreglo directo, jurisdiccion y doble firma electronica sobre texto y anexos con hash SHA-256. Todo cambio exige nueva instancia.</p>');
    }

    private function document(string $title, array $c, string $body): string
    {
        return '<article class="legal-document"><header><p><strong>' . $c['name'] . '</strong></p><h1>' . $title . '</h1><p>Documento controlado. La copia valida es la version aprobada en el sistema.</p></header>' . $body . '<footer><p>Canal de proteccion de datos: ' . $c['email'] . ' | ' . $c['phone'] . '</p></footer></article>';
    }

    private function identity(array $c): string
    {
        return '<p><strong>Responsable:</strong> ' . $c['name'] . '<br><strong>Documento:</strong> ' . $c['document'] . '<br><strong>Domicilio:</strong> ' . $c['address'] . ', ' . $c['city'] . '<br><strong>Correo:</strong> ' . $c['email'] . '<br><strong>Telefono:</strong> ' . $c['phone'] . '</p>';
    }

    private function inventory(array $bases, array $purposes): string
    {
        if ($bases === []) {
            return '<p><em>El inventario debe completarse y aprobarse antes de publicar este documento.</em></p>';
        }
        $byBase = [];
        foreach ($purposes as $purpose) {
            $byBase[(int) $purpose['base_id']][] = $purpose['descripcion'];
        }
        $html = '<table><thead><tr><th>Base</th><th>Titulares y datos</th><th>Finalidades</th><th>Conservacion</th></tr></thead><tbody>';
        foreach ($bases as $base) {
            $holders = implode(', ', $this->jsonList($base['tipos_titular_json'] ?? null));
            $categories = implode(', ', $this->jsonList($base['categorias_datos_json'] ?? null));
            $purposeText = implode('; ', $byBase[(int) $base['id']] ?? []) ?: ($base['finalidad_resumen'] ?? 'Por completar');
            $retention = ! empty($base['retencion_meses']) ? $base['retencion_meses'] . ' meses' : ($base['criterio_eliminacion'] ?? 'Segun finalidad y obligacion aplicable');
            $html .= '<tr><td>' . $this->e($base['nombre']) . '</td><td>' . $this->e(trim($holders . ($holders && $categories ? ': ' : '') . $categories)) . '</td><td>' . $this->e($purposeText) . '</td><td>' . $this->e($retention) . '</td></tr>';
        }
        return $html . '</tbody></table>';
    }

    private function securityInventory(array $bases, array $purposes, array $thirdParties): string
    {
        if ($bases === []) {
            return '<p><strong>Inventario inexistente: esta instancia no puede aprobarse.</strong></p>';
        }
        $byBase = [];
        foreach ($purposes as $purpose) {
            $byBase[(int) $purpose['base_id']][] = $purpose['descripcion'];
        }
        $processorsByBase = [];
        foreach ($thirdParties as $third) {
            foreach (json_decode((string) ($third['bases_json'] ?? '[]'), true) ?: [] as $baseId) {
                $processorsByBase[(int) $baseId][] = $third['nombre'];
            }
        }
        $html = '<table><thead><tr><th>Base</th><th>Titulares y categorias</th><th>Finalidades</th><th>Soporte/ubicacion</th><th>Encargados</th><th>Responsable</th><th>Conservacion</th><th>Revision</th></tr></thead><tbody>';
        foreach ($bases as $base) {
            $marks = [];
            if (! empty($base['datos_sensibles'])) { $marks[] = 'sensible'; }
            if (! empty($base['datos_biometricos'])) { $marks[] = 'biometria'; }
            if (! empty($base['datos_menores'])) { $marks[] = 'menores'; }
            $holders = implode(', ', $this->jsonList($base['tipos_titular_json'] ?? null));
            $categories = implode(', ', $this->jsonList($base['categorias_datos_json'] ?? null));
            $retention = ! empty($base['retencion_meses']) ? $base['retencion_meses'] . ' meses' : ($base['criterio_eliminacion'] ?? 'Pendiente');
            $html .= '<tr><td>' . $this->e($base['nombre']) . '</td><td>' . $this->e(trim($holders . ': ' . $categories . ($marks ? ' [' . implode(', ', $marks) . ']' : ''))) . '</td><td>' . $this->e(implode('; ', $byBase[(int) $base['id']] ?? []) ?: ($base['finalidad_resumen'] ?? 'Pendiente')) . '</td><td>' . $this->e($base['soportes_ubicacion'] ?? $base['ubicacion'] ?? '') . '</td><td>' . $this->e(implode(', ', $processorsByBase[(int) $base['id']] ?? []) ?: 'Ninguno') . '</td><td>' . $this->e($base['responsable_interno'] ?? '') . '</td><td>' . $this->e($retention) . '</td><td>' . $this->e(substr((string) ($base['revisado_at'] ?? ''), 0, 10)) . '</td></tr>';
        }
        return $html . '</tbody></table>';
    }

    private function purposeChecklist(array $bases, array $purposes): string
    {
        $names = array_column($bases, 'nombre', 'id');
        if ($purposes === []) {
            return '<p>[ ] Finalidades informadas en el canal de recoleccion.</p>';
        }
        $html = '<ul>';
        foreach ($purposes as $purpose) {
            $optional = (int) $purpose['es_opcional'] === 1 ? ' (opcional)' : '';
            $html .= '<li>[ ] ' . $this->e(($names[$purpose['base_id']] ?? 'Base') . ': ' . $purpose['descripcion'] . $optional) . '</li>';
        }
        return $html . '</ul>';
    }

    private function authorizationPurposeItems(array $bases, array $purposes, string $type): string
    {
        $baseMap = [];
        foreach ($bases as $base) {
            $baseMap[(int) $base['id']] = $base;
        }
        $items = array_filter($purposes, static fn ($purpose) => ($purpose['base_juridica_tipo'] ?? 'autorizacion') === $type);
        if ($items === []) {
            return '<p><em>No se han declarado tratamientos en esta categoria.</em></p>';
        }
        $html = '<ol>';
        foreach ($items as $purpose) {
            $base = $baseMap[(int) $purpose['base_id']] ?? [];
            $categories = $this->jsonList($purpose['categorias_datos_json'] ?? null);
            if ($categories === []) {
                $categories = $this->jsonList($base['categorias_datos_json'] ?? null);
            }
            $html .= '<li><strong>' . $this->e($purpose['descripcion']) . '</strong><br>Base: ' . $this->e($base['nombre'] ?? 'Base asociada') . '. Datos: ' . $this->e(implode(', ', $categories) ?: 'Por definir') . '.';
            if ($type === 'excepcion_legal') {
                $html .= ' Base legal: ' . $this->e($purpose['base_juridica_detalle'] ?? 'Por definir') . '.';
            } else {
                $optional = ! empty($purpose['es_opcional']);
                $html .= $optional ? ' <strong>OPCIONAL.</strong>' : ' <strong>Necesaria para la relacion informada.</strong>';
                if ($optional) {
                    $html .= ' Si se niega: ' . $this->e($purpose['consecuencia_negativa'] ?? 'Por definir') . '. Ninguna otra consecuencia es aplicable.';
                }
                $html .= ' Opciones de la instancia: <strong>Autorizo</strong> o <strong>No autorizo</strong>.';
            }
            $html .= '</li>';
        }
        return $html . '</ol>';
    }

    private function sensitiveAuthorizationBlock(array $bases, array $purposes): string
    {
        $sensitiveBaseIds = [];
        $categories = [];
        foreach ($bases as $base) {
            if (empty($base['datos_sensibles'])) {
                continue;
            }
            $sensitiveBaseIds[] = (int) $base['id'];
            $categories = array_merge($categories, $this->jsonList($base['categorias_datos_json'] ?? null));
        }
        if ($sensitiveBaseIds === []) {
            return '';
        }
        $items = [];
        foreach ($purposes as $purpose) {
            foreach ((array) ($purpose['datos_sensibles_detalle'] ?? []) as $item) {
                $items[] = $item;
            }
        }
        $html = '<h3>2.3 Datos sensibles: decision separada</h3><p>Su suministro es facultativo y negarse no genera consecuencias adversas. Cada dato se presenta con finalidad exclusiva y decision independiente.</p><ul>';
        foreach ($items as $item) {
            $html .= '<li><strong>' . $this->e($item['dato']) . ':</strong> ' . $this->e($item['finalidad_exclusiva']) . '. Opciones: Autorizo expresamente o No autorizo.</li>';
        }
        return $html . '</ul>';
    }

    private function storeNoticeVariants(int $clienteId, int $documentId, int $masterVersion, array $cliente, array $programa, array $bases, array $purposes): void
    {
        $db = db_connect();
        foreach ($this->renderNoticeVariants($cliente, $programa, $bases, $purposes) as $type => $variant) {
            $content = $variant['html'];
            $db->table('dp_aviso_variantes')->insert([
                'cliente_id' => $clienteId,
                'documento_id' => $documentId,
                'tipo' => $type,
                'version_maestra' => $masterVersion,
                'version' => 1,
                'titulo' => $variant['title'],
                'contenido_html' => $content,
                'hash_sha256' => hash('sha256', $content),
                'estado' => 'borrador',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function publicDataSummary(array $bases, array $purposes): string
    {
        if ($bases === []) {
            return '<p><em>El resumen publico de categorias y finalidades se completara a partir del inventario aprobado.</em></p>';
        }
        $byBase = [];
        foreach ($purposes as $purpose) {
            $byBase[(int) $purpose['base_id']][] = (string) $purpose['descripcion'];
        }
        $html = '<ul>';
        foreach ($bases as $base) {
            $holders = implode(', ', $this->jsonList($base['tipos_titular_json'] ?? null)) ?: 'Titulares relacionados con la copropiedad';
            $categories = implode(', ', $this->jsonList($base['categorias_datos_json'] ?? null)) ?: 'Datos adecuados y necesarios para la finalidad';
            $purposeText = implode('; ', $byBase[(int) $base['id']] ?? []) ?: ($base['finalidad_resumen'] ?? 'Gestion asociada a la copropiedad');
            $html .= '<li><strong>' . $this->e($base['nombre']) . ':</strong> Titulares: ' . $this->e($holders) . '. Categorias: ' . $this->e($categories) . '. Finalidades: ' . $this->e($purposeText) . '.</li>';
        }
        return $html . '</ul>';
    }

    private function purposeSummary(array $bases, array $purposes): string
    {
        if ($purposes === []) {
            return '<p>Los datos se trataran exclusivamente para las finalidades especificas informadas en el canal de recoleccion y asociadas a la administracion de la copropiedad.</p>';
        }
        $baseNames = array_column($bases, 'nombre', 'id');
        $html = '<ul>';
        foreach ($purposes as $purpose) {
            $label = ($baseNames[$purpose['base_id']] ?? 'Tratamiento') . ': ' . $purpose['descripcion'];
            $html .= '<li>' . $this->e($label) . (! empty($purpose['es_opcional']) ? ' <em>(opcional)</em>' : '') . '</li>';
        }
        return $html . '</ul>';
    }

    private function videoPolicy(array $c): string
    {
        $action = $c['records_video']
            ? 'capta, monitorea, graba y almacena imagenes'
            : 'capta y monitorea imagenes en tiempo real sin grabarlas ni almacenarlas';
        $retention = $c['records_video'] && $c['video_retention_days']
            ? ' El plazo ordinario de conservacion es de hasta ' . $c['video_retention_days'] . ' dias, salvo bloqueo justificado por incidentes, investigaciones o deberes legales.'
            : '';
        return '<p>El sistema ' . $action . ' para proteger personas y bienes, controlar accesos, prevenir y gestionar incidentes y aportar evidencia cuando proceda.' . $retention . ' La ubicacion y orientacion de las camaras debe minimizar la captacion de espacios ajenos.</p><p>El acceso esta restringido. Cuando un Titular solicite imagenes propias que incluyan a terceros, se evaluara la entrega mediante extractos, enmascaramiento o consulta controlada para proteger los derechos concurrentes.</p>';
    }

    private function transmissionPolicy(array $c): string
    {
        $international = $c['international_transmission']
            ? ' Cuando el Encargado opere desde otro pais, la transmision internacional involucra <strong>' . $c['transmission_countries'] . '</strong> y se somete a las reglas contractuales aplicables.'
            : '';
        return '<p>Los Encargados trataran los datos por cuenta e instrucciones del Responsable mediante acuerdos que definan alcance, seguridad, confidencialidad, atencion de derechos, incidentes y devolucion o eliminacion.' . $international . '</p>';
    }

    private function rightsAndChannels(array $c): string
    {
        return '<h2>Derechos y canales</h2><p>El Titular puede conocer, actualizar y rectificar sus datos; solicitar prueba de la autorizacion; conocer el uso dado; acceder gratuitamente; presentar consultas y reclamos; y solicitar revocatoria o supresion cuando proceda. Puede ejercerlos ante <strong>' . $c['area'] . '</strong> por el correo <strong>' . $c['email'] . '</strong>, el telefono <strong>' . $c['phone'] . '</strong> o en <strong>' . $c['address'] . ', ' . $c['city'] . '</strong>, en el horario ' . $c['hours'] . '.</p>';
    }

    private function policyAccess(array $c): string
    {
        $location = $c['policy_url'] !== '' ? $c['policy_url'] : $c['publication_medium'];
        return '<h2>Consulta de la politica</h2><p>La Politica de Tratamiento de Datos Personales puede consultarse en <strong>' . $location . '</strong>.</p>';
    }

    private function numberedSections(array $sections): string
    {
        $html = '';
        $number = 1;
        foreach ($sections as [$title, $body, $enabled]) {
            if (! $enabled) {
                continue;
            }
            $html .= '<h2>' . $number++ . '. ' . $title . '</h2>' . $body;
        }
        return $html;
    }

    private function transferGuaranteeLabel(string $code): string
    {
        return match ($code) {
            'nivel_adecuado' => 'pais con nivel adecuado de proteccion',
            'declaracion_conformidad' => 'declaracion de conformidad de la SIC',
            'excepcion_articulo_26' => 'excepcion aplicable del articulo 26 de la Ley 1581 de 2012',
            'autorizacion_expresa' => 'autorizacion expresa e inequivoca del Titular',
            default => $code,
        };
    }

    private function jsonList(?string $json): array
    {
        $value = $json ? json_decode($json, true) : [];
        return is_array($value) ? array_values(array_filter(array_map('strval', $value))) : [];
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
