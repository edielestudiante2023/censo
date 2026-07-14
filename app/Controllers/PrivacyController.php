<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\HousingUnitConfigurator;
use App\Libraries\OpenAiPrivacyService;
use App\Libraries\PrivacyAudit;
use App\Libraries\PrivacyBusinessDays;
use App\Libraries\PrivacyConfidentialityService;
use App\Libraries\PrivacyDocumentService;
use App\Libraries\PrivacyHousingCoverage;
use App\Libraries\PrivacyPdf;
use App\Libraries\PrivacyProgramService;
use App\Libraries\PrivacyProcessorAgreementService;
use App\Libraries\PrivacyRequestWorkflow;
use App\Libraries\PrivacyPii;
use App\Libraries\PrivacyVault;
use App\Libraries\QrSvgService;
use App\Models\ClienteModel;
use App\Models\DpBaseDatosModel;
use App\Models\DpDocumentoModel;
use App\Models\DpNotificacionModel;
use App\Models\DpProgramaModel;
use App\Models\DpSolicitudModel;

class PrivacyController extends BaseController
{
    public function mine()
    {
        return $this->show(null);
    }

    public function admin(int $clienteId)
    {
        return $this->show($clienteId);
    }

    public function generateHousingHouses(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }

        try {
            $result = (new HousingUnitConfigurator())->generateHouses((int) $cliente['id'], [
                'prefix' => $this->request->getPost('housing_house_prefix'),
                'padding' => $this->request->getPost('housing_house_padding'),
                'from' => $this->request->getPost('housing_house_from'),
                'to' => $this->request->getPost('housing_house_to'),
            ]);
            PrivacyAudit::record((int) $cliente['id'], 'configurar_casas', 'unidad_habitacional', null, null, $result);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return redirect()->to($this->privacyModuleUrl($cliente) . '#titulares')->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to($this->privacyModuleUrl($cliente) . '#titulares')->with('success', sprintf(
            'Configuracion guardada: %d casas creadas y %d existentes conservadas.',
            $result['created'],
            $result['skipped']
        ));
    }

    public function generateHousingApartments(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }

        try {
            $result = (new HousingUnitConfigurator())->generateApartments((int) $cliente['id'], [
                'tower_prefix' => $this->request->getPost('housing_tower_prefix'),
                'tower_from' => $this->request->getPost('housing_tower_from'),
                'tower_to' => $this->request->getPost('housing_tower_to'),
                'floors' => $this->request->getPost('housing_floors'),
                'units_per_floor' => $this->request->getPost('housing_units_per_floor'),
                'unit_from' => $this->request->getPost('housing_unit_from'),
            ]);
            PrivacyAudit::record((int) $cliente['id'], 'configurar_apartamentos', 'unidad_habitacional', null, null, $result);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return redirect()->to($this->privacyModuleUrl($cliente) . '#titulares')->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to($this->privacyModuleUrl($cliente) . '#titulares')->with('success', sprintf(
            'Configuracion guardada: %d torres y %d apartamentos creados; %d unidades existentes conservadas.',
            $result['towers_created'],
            $result['created'],
            $result['skipped']
        ));
    }

    public function saveProgram(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $program = $this->program($cliente);
        $config = [
            'backup_rotation_days' => max(1, min(365, (int) $this->request->getPost('backup_rotation_days'))),
            'request_cutoff_time' => preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $this->request->getPost('request_cutoff_time')) ? (string) $this->request->getPost('request_cutoff_time') : '17:00',
            'request_file_years' => max(5, min(20, (int) ($this->request->getPost('request_file_years') ?: 5))),
            'privacy_app_name' => $this->nullable('privacy_app_name') ?: 'Modulo de Proteccion de Datos',
            'sendgrid_transmission_confirmed' => $this->request->getPost('sendgrid_transmission_confirmed') ? 1 : 0,
            'security_acta' => $this->nullable('security_acta'),
            'security_administrador' => $this->nullable('security_administrador'),
            'security_opd_designacion' => $this->nullable('security_opd_designacion'),
            'security_opd_reporte' => $this->nullable('security_opd_reporte'),
            'security_proveedor_ti' => $this->nullable('security_proveedor_ti'),
            'security_sedes' => $this->nullable('security_sedes'),
            'security_archivo_ubicacion' => $this->nullable('security_archivo_ubicacion'),
            'security_archivo_custodio' => $this->nullable('security_archivo_custodio'),
            'security_canal_incidentes' => $this->nullable('security_canal_incidentes'),
            'security_gestor_secretos' => $this->nullable('security_gestor_secretos'),
            'security_retencion_logs_meses' => max(1, min(60, (int) ($this->request->getPost('security_retencion_logs_meses') ?: 12))),
            'security_retencion_backups_dias' => max(1, min(365, (int) ($this->request->getPost('security_retencion_backups_dias') ?: 90))),
            'security_revision_riesgos_meses' => max(1, min(12, (int) ($this->request->getPost('security_revision_riesgos_meses') ?: 12))),
            'security_revision_accesos_meses' => max(1, min(6, (int) ($this->request->getPost('security_revision_accesos_meses') ?: 6))),
            'security_revision_inventario_meses' => max(1, min(12, (int) ($this->request->getPost('security_revision_inventario_meses') ?: 12))),
            'security_prueba_restauracion_meses' => max(1, min(12, (int) ($this->request->getPost('security_prueba_restauracion_meses') ?: 6))),
            'security_capacitacion_meses' => max(1, min(12, (int) ($this->request->getPost('security_capacitacion_meses') ?: 12))),
            'security_evaluacion_encargados_meses' => max(1, min(12, (int) ($this->request->getPost('security_evaluacion_encargados_meses') ?: 12))),
            'security_rotacion_secretos_meses' => max(1, min(12, (int) ($this->request->getPost('security_rotacion_secretos_meses') ?: 12))),
            'security_prueba_ia_meses' => max(1, min(6, (int) ($this->request->getPost('security_prueba_ia_meses') ?: 6))),
            'security_timeout_minutos' => max(5, min(30, (int) ($this->request->getPost('security_timeout_minutos') ?: 15))),
            'security_max_intentos' => max(3, min(10, (int) ($this->request->getPost('security_max_intentos') ?: 5))),
            'security_min_caracteres' => max(12, min(128, (int) ($this->request->getPost('security_min_caracteres') ?: 12))),
            'security_umbral_exportacion' => max(1, (int) ($this->request->getPost('security_umbral_exportacion') ?: 100)),
            'security_trabajo_remoto' => $this->request->getPost('security_trabajo_remoto') ? 1 : 0,
            'security_soportes_fisicos' => $this->request->getPost('security_soportes_fisicos') ? 1 : 0,
            'security_nube' => $this->request->getPost('security_nube') ? 1 : 0,
            'security_nube_proveedor' => $this->nullable('security_nube_proveedor'),
            'security_nube_pais' => $this->nullable('security_nube_pais'),
            'security_cctv_ubicacion' => $this->nullable('security_cctv_ubicacion'),
            'security_cctv_roles' => $this->nullable('security_cctv_roles'),
            'security_contenedor_destruccion' => $this->nullable('security_contenedor_destruccion'),
            'area_responsable' => $this->nullable('area_responsable'),
            'horario_atencion' => $this->nullable('horario_atencion'),
            'organo_aprobacion' => $this->nullable('organo_aprobacion'),
            'fecha_aprobacion' => $this->nullable('fecha_aprobacion'),
            'fecha_vigencia' => $this->nullable('fecha_vigencia'),
            'medio_publicacion' => $this->nullable('medio_publicacion'),
            'url_politica' => $this->nullable('url_politica'),
            'usa_videovigilancia' => $this->request->getPost('usa_videovigilancia') ? 1 : 0,
            'graba_videovigilancia' => $this->request->getPost('graba_videovigilancia') ? 1 : 0,
            'plazo_grabaciones_dias' => max(0, (int) $this->request->getPost('plazo_grabaciones_dias')),
            'justificacion_retencion_video' => $this->nullable('justificacion_retencion_video'),
            'video_identificacion_biometrica' => $this->request->getPost('video_identificacion_biometrica') ? 1 : 0,
            'usa_biometria' => $this->request->getPost('usa_biometria') ? 1 : 0,
            'tipo_biometria' => $this->nullable('tipo_biometria'),
            'alternativa_biometrica' => $this->nullable('alternativa_biometrica'),
            'finalidad_biometria' => $this->nullable('finalidad_biometria'),
            'plazo_supresion_biometria_dias' => max(0, (int) $this->request->getPost('plazo_supresion_biometria_dias')),
            'zonas_vigiladas' => $this->nullable('zonas_vigiladas'),
            'encargados_publicos' => $this->nullable('encargados_publicos'),
            'canal_entrega_copia' => $this->nullable('canal_entrega_copia'),
            'transmision_internacional' => $this->request->getPost('transmision_internacional') ? 1 : 0,
            'paises_transmision' => $this->nullable('paises_transmision'),
            'transferencia_internacional' => $this->request->getPost('transferencia_internacional') ? 1 : 0,
            'paises_transferencia' => $this->nullable('paises_transferencia'),
            'receptor_exterior' => $this->nullable('receptor_exterior'),
            'garantia_transferencia' => $this->nullable('garantia_transferencia'),
            'garantia_transferencia_detalle' => $this->nullable('garantia_transferencia_detalle'),
            'transferencia_requiere_autorizacion' => $this->request->getPost('transferencia_requiere_autorizacion') ? 1 : 0,
            'transferencia_nacional' => $this->request->getPost('transferencia_nacional') ? 1 : 0,
            'responsable_destinatario' => $this->nullable('responsable_destinatario'),
            'finalidad_transferencia' => $this->nullable('finalidad_transferencia'),
            'publica_morosos' => $this->request->getPost('publica_morosos') ? 1 : 0,
            'obligado_rnbd' => $this->request->getPost('obligado_rnbd') ? 1 : 0,
        ];
        $data = [
            'responsable_nombre' => trim((string) $this->request->getPost('responsable_nombre')),
            'responsable_documento' => $this->nullable('responsable_documento'),
            'responsable_direccion' => $this->nullable('responsable_direccion'),
            'responsable_ciudad' => $this->nullable('responsable_ciudad'),
            'canal_email' => trim((string) $this->request->getPost('canal_email')),
            'canal_telefono' => $this->nullable('canal_telefono'),
            'oficial_nombre' => $this->nullable('oficial_nombre'),
            'oficial_cargo' => $this->nullable('oficial_cargo'),
            'estado' => (string) ($this->request->getPost('estado') ?: 'configuracion'),
            'config_json' => json_encode($config, JSON_UNESCAPED_UNICODE),
        ];
        if (! $this->validateData($data, [
            'responsable_nombre' => 'required|max_length[191]', 'canal_email' => 'required|valid_email|max_length[191]',
            'estado' => 'required|in_list[configuracion,activo,en_revision,suspendido]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new DpProgramaModel())->update($program['id'], $data);
        PrivacyAudit::record((int) $cliente['id'], 'actualizar', 'programa', (int) $program['id'], $program, $data);
        return redirect()->back()->with('success', 'Configuracion del programa actualizada. Regenera los documentos para incorporar los cambios.');
    }

    public function saveBase(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $id = (int) $this->request->getPost('base_id');
        $model = new DpBaseDatosModel();
        $before = $id ? $model->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if ($id && ! $before) {
            return redirect()->back()->with('error', 'Base de datos no encontrada.');
        }
        $name = trim((string) $this->request->getPost('nombre'));
        $data = [
            'cliente_id' => $cliente['id'], 'nombre' => $name,
            'codigo' => url_title((string) ($this->request->getPost('codigo') ?: $name), '-', true),
            'responsable_interno' => $this->nullable('responsable_interno'), 'ubicacion' => $this->nullable('ubicacion'),
            'soportes_ubicacion' => $this->nullable('soportes_ubicacion'),
            'medio' => (string) ($this->request->getPost('medio') ?: 'mixto'),
            'tipos_titular_json' => json_encode($this->listPost('tipos_titular'), JSON_UNESCAPED_UNICODE),
            'categorias_datos_json' => json_encode($this->listPost('categorias_datos'), JSON_UNESCAPED_UNICODE),
            'datos_sensibles' => $this->request->getPost('datos_sensibles') ? 1 : 0,
            'datos_biometricos' => $this->request->getPost('datos_biometricos') ? 1 : 0,
            'datos_menores' => $this->request->getPost('datos_menores') ? 1 : 0,
            'origen_datos' => $this->nullable('origen_datos'), 'finalidad_resumen' => $this->nullable('finalidad_resumen'),
            'fundamento' => $this->nullable('fundamento'),
            'retencion_meses' => $this->request->getPost('retencion_meses') !== '' ? (int) $this->request->getPost('retencion_meses') : null,
            'criterio_eliminacion' => $this->nullable('criterio_eliminacion'), 'medidas_seguridad' => $this->nullable('medidas_seguridad'),
            'rnbd_aplica' => (string) ($this->request->getPost('rnbd_aplica') ?: 'por_evaluar'),
            'revisado_at' => date('Y-m-d H:i:s'), 'activo' => 1,
        ];
        if (! $this->validateData($data, [
            'nombre' => 'required|max_length[191]', 'codigo' => 'required|alpha_dash|max_length[80]',
            'medio' => 'required|in_list[digital,fisico,mixto]', 'retencion_meses' => 'permit_empty|is_natural',
            'rnbd_aplica' => 'required|in_list[por_evaluar,si,no]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($id) {
            unset($data['cliente_id']);
            $model->update($id, $data);
        } else {
            $id = (int) $model->insert($data, true);
        }
        PrivacyAudit::record((int) $cliente['id'], $before ? 'actualizar' : 'crear', 'base_datos', $id, $before, $data);
        return redirect()->back()->with('success', 'Base de datos guardada.');
    }

    public function deactivateBase(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getPost('base_id');
        $base = $cliente ? (new DpBaseDatosModel())->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $base) {
            return $this->denied();
        }
        (new DpBaseDatosModel())->update($id, ['activo' => 0]);
        PrivacyAudit::record((int) $cliente['id'], 'desactivar', 'base_datos', $id, $base, ['activo' => 0]);
        return redirect()->back()->with('success', 'Base archivada sin eliminar su trazabilidad.');
    }

    public function savePurpose(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $purposeId = (int) $this->request->getPost('finalidad_id');
        $db = db_connect();
        $before = $purposeId && $cliente ? $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('id', $purposeId)->get()->getRowArray() : null;
        if ($purposeId && ! $before) {
            return redirect()->back()->with('error', 'Finalidad no encontrada.');
        }
        $baseId = (int) $this->request->getPost('base_id');
        $base = $cliente ? (new DpBaseDatosModel())->where('cliente_id', $cliente['id'])->where('id', $baseId)->first() : null;
        $description = trim((string) $this->request->getPost('descripcion'));
        $legalType = (string) ($this->request->getPost('base_juridica_tipo') ?: 'autorizacion');
        $legalDetail = $this->nullable('base_juridica_detalle');
        $categories = $this->listPost('categorias_datos_finalidad');
        $isOptional = $this->request->getPost('es_opcional') ? 1 : 0;
        $negativeConsequence = $this->nullable('consecuencia_negativa');
        if (! $base || $description === '' || ! in_array($legalType, ['autorizacion', 'excepcion_legal'], true) || $categories === []) {
            return redirect()->back()->with('error', 'Selecciona una base, escribe la finalidad, su base juridica y las categorias de datos.');
        }
        if ($legalType === 'excepcion_legal' && ! $legalDetail) {
            return redirect()->back()->with('error', 'Todo tratamiento exceptuado debe citar su fundamento legal exacto.');
        }
        if ($legalType === 'autorizacion' && $isOptional && ! $negativeConsequence) {
            return redirect()->back()->with('error', 'Toda finalidad opcional debe explicar la consecuencia concreta de negarse.');
        }
        $purposeData = [
            'cliente_id' => $cliente['id'], 'base_id' => $baseId, 'descripcion' => $description,
            'base_juridica_tipo' => $legalType, 'base_juridica_detalle' => $legalDetail,
            'categorias_datos_json' => json_encode($categories, JSON_UNESCAPED_UNICODE),
            'es_opcional' => $isOptional, 'consecuencia_negativa' => $negativeConsequence,
            'requiere_consentimiento_explicito' => $this->request->getPost('requiere_consentimiento_explicito') ? 1 : 0,
            'activo' => 1, 'updated_at' => date('Y-m-d H:i:s'),
        ];
        $contentHash = $this->purposeContentHash($purposeData);
        $purposeData['version'] = $before && ! hash_equals((string) ($before['contenido_hash'] ?? ''), $contentHash)
            ? ((int) ($before['version'] ?? 1) + 1) : (int) ($before['version'] ?? 1);
        $purposeData['contenido_hash'] = $contentHash;
        if ($before) {
            unset($purposeData['cliente_id']);
            $db->table('dp_finalidades')->where('id', $purposeId)->update($purposeData);
            $id = $purposeId;
        } else {
            $purposeData['created_at'] = date('Y-m-d H:i:s');
            $db->table('dp_finalidades')->insert($purposeData);
            $id = (int) $db->insertID();
        }
        PrivacyAudit::record((int) $cliente['id'], $before ? 'actualizar' : 'crear', 'finalidad', (int) $id, $before, $purposeData);
        return redirect()->back()->with('success', $before ? 'Finalidad actualizada.' : 'Finalidad agregada.');
    }

    public function saveSensitiveDatum(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $purposeId = (int) $this->request->getPost('finalidad_id');
        $db = db_connect();
        $purpose = $cliente ? $db->table('dp_finalidades f')->select('f.*, b.datos_sensibles')
            ->join('dp_bases_datos b', 'b.id=f.base_id')->where('f.cliente_id', $cliente['id'])->where('f.id', $purposeId)->get()->getRowArray() : null;
        $datum = trim((string) $this->request->getPost('dato'));
        $exclusivePurpose = trim((string) $this->request->getPost('finalidad_exclusiva'));
        if (! $purpose || ! $purpose['datos_sensibles'] || $datum === '' || $exclusivePurpose === '') {
            return redirect()->back()->with('error', 'Selecciona una finalidad sensible e identifica el dato y su finalidad exclusiva.');
        }
        if ($db->table('dp_finalidad_datos_sensibles')->where('finalidad_id', $purposeId)->where('dato', $datum)->countAllResults()) {
            return redirect()->back()->with('error', 'Ese dato sensible ya esta registrado para la finalidad.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('dp_finalidad_datos_sensibles')->insert(['cliente_id' => $cliente['id'], 'base_id' => $purpose['base_id'],
            'finalidad_id' => $purposeId, 'dato' => $datum, 'finalidad_exclusiva' => $exclusivePurpose,
            'activo' => 1, 'created_at' => $now, 'updated_at' => $now]);
        PrivacyAudit::record((int) $cliente['id'], 'crear', 'dato_sensible_finalidad', (int) $db->insertID(), null, ['finalidad_id' => $purposeId, 'dato' => $datum]);
        return redirect()->back()->with('success', 'Dato sensible y finalidad exclusiva registrados.');
    }

    public function saveThirdParty(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $thirdPartyId = (int) $this->request->getPost('tercero_id');
        $name = trim((string) $this->request->getPost('nombre'));
        $email = $this->nullable('contacto_email');
        $representativeEmail = $this->nullable('representante_email');
        if ($name === '' || ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) || ($representativeEmail && ! filter_var($representativeEmail, FILTER_VALIDATE_EMAIL))) {
            return redirect()->back()->with('error', 'Completa un nombre y un correo valido para el tercero.');
        }
        $db = db_connect();
        $before = $thirdPartyId ? $db->table('dp_terceros')->where('cliente_id', $cliente['id'])->where('id', $thirdPartyId)->get()->getRowArray() : null;
        if ($before) { $before = $this->vault()->decryptRow('dp_terceros', $before); }
        if ($thirdPartyId && ! $before) { return $this->denied(); }
        $answers = ['accede_datos' => (bool) $this->request->getPost('rol_accede_datos'),
            'decide_finalidades' => (bool) $this->request->getPost('rol_decide_finalidades'),
            'decide_medios_esenciales' => (bool) $this->request->getPost('rol_decide_medios'),
            'obligacion_legal_propia' => (bool) $this->request->getPost('rol_obligacion_propia'),
            'usa_fines_propios' => (bool) $this->request->getPost('rol_fines_propios'),
            'solo_instrucciones' => (bool) $this->request->getPost('rol_solo_instrucciones')];
        $independent = $answers['decide_finalidades'] || $answers['decide_medios_esenciales'] || $answers['obligacion_legal_propia'] || $answers['usa_fines_propios'];
        $dualScope = trim((string) $this->request->getPost('operaciones_rol_dual'));
        $classification = ! $answers['accede_datos'] ? 'sin_tratamiento' : ($independent ? ($dualScope !== '' && $answers['solo_instrucciones'] ? 'rol_dual' : 'responsable_independiente') : ($answers['solo_instrucciones'] ? 'encargado' : 'pendiente'));
        $baseIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('bases'))));
        $purposeIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('finalidades'))));
        $bases = $baseIds ? $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->whereIn('id', $baseIds)->get()->getResultArray() : [];
        $highRisk = (bool) array_filter($bases, static fn ($base) => ! empty($base['datos_sensibles']) || ! empty($base['datos_biometricos']) || ! empty($base['datos_menores']));
        $risk = $highRisk ? 'alto' : (string) ($this->request->getPost('nivel_riesgo') ?: 'basico');
        if (! in_array($risk, PrivacyProcessorAgreementService::RISK_LEVELS, true)) { $risk = 'basico'; }
        $now = date('Y-m-d H:i:s');
        $data = [
            'nombre' => $name,
            'tipo' => (string) ($this->request->getPost('tipo') ?: 'encargado'),
            'clasificacion_rol' => $classification, 'clasificacion_json' => json_encode($answers, JSON_UNESCAPED_UNICODE),
            'clasificacion_justificacion' => $this->nullable('clasificacion_justificacion'), 'clasificado_at' => $classification !== 'pendiente' ? $now : null,
            'documento' => $this->nullable('documento'), 'pais' => $this->nullable('pais') ?: 'Colombia',
            'contacto_email' => $email, 'servicio' => $this->nullable('servicio'),
            'representante_nombre' => $this->nullable('representante_nombre'), 'representante_documento' => $this->nullable('representante_documento'),
            'representante_email' => $representativeEmail, 'representacion_evidencia' => $this->nullable('representacion_evidencia'),
            'facultades_verificadas_at' => $this->request->getPost('facultades_verificadas') ? $now : null,
            'contrato_principal_ref' => $this->nullable('contrato_principal_ref'), 'contrato_principal_objeto' => $this->nullable('contrato_principal_objeto'),
            'bases_json' => json_encode($baseIds), 'titulares_json' => json_encode(array_values((array) $this->request->getPost('titulares')), JSON_UNESCAPED_UNICODE),
            'categorias_json' => json_encode(array_values((array) $this->request->getPost('categorias')), JSON_UNESCAPED_UNICODE),
            'finalidades_json' => json_encode($purposeIds), 'operaciones_json' => json_encode(array_values((array) $this->request->getPost('operaciones'))),
            'sistemas_json' => json_encode(array_values(array_filter(array_map('trim', explode(',', (string) $this->request->getPost('sistemas'))))), JSON_UNESCAPED_UNICODE),
            'paises_json' => json_encode(array_values(array_filter(array_map('trim', explode(',', (string) $this->request->getPost('paises'))))), JSON_UNESCAPED_UNICODE),
            'datos_sensibles' => (int) (bool) array_filter($bases, static fn ($base) => ! empty($base['datos_sensibles'])),
            'datos_biometricos' => (int) (bool) array_filter($bases, static fn ($base) => ! empty($base['datos_biometricos'])),
            'datos_menores' => (int) (bool) array_filter($bases, static fn ($base) => ! empty($base['datos_menores'])),
            'usa_videovigilancia' => $this->request->getPost('usa_videovigilancia') ? 1 : 0, 'nivel_riesgo' => $risk,
            'medidas_json' => json_encode(array_values((array) $this->request->getPost('medidas')), JSON_UNESCAPED_UNICODE),
            'debida_diligencia_evidencia' => $this->nullable('debida_diligencia_evidencia'),
            'canal_incidentes_probado_at' => $this->request->getPost('canal_incidentes_probado') ? $now : null,
            'plazo_incidente_dias' => max(1, min(3, (int) ($this->request->getPost('plazo_incidente_dias') ?: 3))),
            'logs_retencion_meses' => max(6, (int) ($this->request->getPost('logs_retencion_meses') ?: 12)),
            'backup_rotacion_dias' => max(1, min(365, (int) ($this->request->getPost('backup_rotacion_dias') ?: 90))),
            'rto_horas' => max(1, (int) ($this->request->getPost('rto_horas') ?: 24)), 'rpo_horas' => max(1, (int) ($this->request->getPost('rpo_horas') ?: 24)),
            'seguro_evidencia' => $this->nullable('seguro_evidencia'), 'contrato_vigente' => 0,
            'contrato_fecha' => $this->nullable('contrato_fecha'), 'contrato_vence' => $this->nullable('contrato_vence'),
            'contrato_evidencia' => $this->nullable('contrato_evidencia'),
            'medidas_verificadas' => $this->request->getPost('medidas_verificadas') ? 1 : 0,
            'evaluado_at' => $this->request->getPost('medidas_verificadas') ? $now : null,
            'subencargado_autorizado' => $this->request->getPost('subencargado_autorizado') ? 1 : 0,
            'habilitado_datos' => 0, 'activo' => 1, 'updated_at' => $now,
        ];
        $stored = $this->vault()->encryptRow('dp_terceros', $data);
        if ($before) {
            $db->table('dp_terceros')->where('id', $thirdPartyId)->update($stored);
            $id = $thirdPartyId;
        } else {
            $data += ['cliente_id' => $cliente['id'], 'created_at' => date('Y-m-d H:i:s')];
            $db->table('dp_terceros')->insert($this->vault()->encryptRow('dp_terceros', $data));
            $id = (int) $db->insertID();
        }
        PrivacyAudit::record((int) $cliente['id'], $before ? 'actualizar' : 'crear', 'tercero', (int) $id, $before, ['nombre' => $name]);
        $message = in_array($classification, ['responsable_independiente', 'sin_tratamiento'], true)
            ? 'Tercero clasificado como ' . str_replace('_', ' ', $classification) . ': el Documento 7 queda bloqueado.'
            : 'Expediente del Encargado guardado; aun no esta habilitado para recibir datos.';
        return redirect()->back()->with('success', $message);
    }

    public function saveSubprocessor(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId); $db = db_connect(); $thirdId = (int) $this->request->getPost('tercero_id');
        $third = $cliente ? $db->table('dp_terceros')->where('cliente_id', $cliente['id'])->where('id', $thirdId)->get()->getRowArray() : null;
        if ($third) { $third = $this->vault()->decryptRow('dp_terceros', $third); }
        $name = trim((string) $this->request->getPost('nombre')); $document = trim((string) $this->request->getPost('documento'));
        $country = trim((string) $this->request->getPost('pais')); $service = trim((string) $this->request->getPost('servicio'));
        $contract = trim((string) $this->request->getPost('contrato_evidencia')); $data = array_values(array_filter(array_map('trim', explode(',', (string) $this->request->getPost('datos')))));
        if (! $third || $name === '' || $document === '' || $country === '' || $service === '' || $contract === '' || $data === []) {
            return redirect()->back()->with('error', 'El subencargado exige entidad, identificacion, pais, servicio, datos y DPA verificable.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('dp_subencargados')->insert($this->vault()->encryptRow('dp_subencargados', ['cliente_id' => $cliente['id'], 'tercero_id' => $thirdId, 'nombre' => $name,
            'documento' => $document, 'pais' => $country, 'servicio' => $service, 'datos_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'contrato_evidencia' => $contract, 'aprobado_por' => session()->get('user_id'), 'aprobado_at' => $now, 'activo' => 1, 'created_at' => $now, 'updated_at' => $now]));
        $db->table('dp_terceros')->where('id', $thirdId)->update(['habilitado_datos' => 0, 'subencargado_autorizado' => 1, 'updated_at' => $now]);
        PrivacyAudit::record((int) $cliente['id'], 'aprobar_subencargado', 'subencargado', (int) $db->insertID(), null, ['tercero_id' => $thirdId, 'pais' => $country]);
        return redirect()->back()->with('success', 'Subencargado aprobado. Debe generarse y firmarse una nueva instancia antes de usarlo.');
    }

    public function createProcessorAgreement(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId); $db = db_connect(); $thirdId = (int) $this->request->getPost('tercero_id');
        $third = $cliente ? $db->table('dp_terceros')->where('cliente_id', $cliente['id'])->where('id', $thirdId)->where('activo', 1)->get()->getRowArray() : null;
        if ($third) { $third = $this->vault()->decryptRow('dp_terceros', $third); }
        $master = $cliente ? $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', 'encargados')->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray() : null;
        if (! $third || ! $master || ! hash_equals((string) $master['hash_sha256'], hash('sha256', (string) $master['contenido_html']))) {
            return redirect()->back()->with('error', 'Se requiere Encargado valido y Documento 7 publicado con integridad verificada.');
        }
        if (! in_array($third['clasificacion_rol'], ['encargado', 'rol_dual'], true)) {
            return redirect()->back()->with('error', 'V-01: este receptor no esta clasificado como Encargado. Use la ruta de transferencia si es Responsable independiente.');
        }
        $basesIds = array_map('intval', json_decode((string) $third['bases_json'], true) ?: []);
        $purposeIds = array_map('intval', json_decode((string) $third['finalidades_json'], true) ?: []);
        $holders = json_decode((string) $third['titulares_json'], true) ?: []; $categories = json_decode((string) $third['categorias_json'], true) ?: [];
        $operations = json_decode((string) $third['operaciones_json'], true) ?: []; $systems = json_decode((string) $third['sistemas_json'], true) ?: [];
        $countries = json_decode((string) $third['paises_json'], true) ?: [];
        $bases = $basesIds ? $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->whereIn('id', $basesIds)->get()->getResultArray() : [];
        $purposes = $purposeIds ? $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('activo', 1)->whereIn('id', $purposeIds)->get()->getResultArray() : [];
        $measures = json_decode((string) $third['medidas_json'], true) ?: [];
        $requiredMeasures = ['control_acceso', 'tls', 'backups_cifrados', 'capacitacion', 'no_resucitar'];
        if (in_array($third['nivel_riesgo'], ['medio', 'alto'], true)) { $requiredMeasures = array_merge($requiredMeasures, ['mfa', 'cifrado_reposo', 'logs', 'segregacion', 'restauracion', 'vulnerabilidades', 'continuidad']); }
        if ($third['nivel_riesgo'] === 'alto') { $requiredMeasures[] = 'destruccion_doble_verificacion'; }
        $validUntil = (string) $third['contrato_vence']; $countryCheck = (string) $this->request->getPost('verificacion_paises_at');
        $responsibleSignature = $this->processorSignature((string) $this->request->getPost('responsable_firma'));
        $service = trim((string) $third['servicio']);
        if (empty($third['documento']) || empty($third['representante_nombre']) || empty($third['representante_documento']) || empty($third['representante_email'])
            || empty($third['representacion_evidencia']) || empty($third['facultades_verificadas_at']) || empty($third['contrato_principal_ref'])
            || empty($third['contrato_evidencia']) || empty($third['debida_diligencia_evidencia']) || empty($third['evaluado_at']) || strlen($service) < 50) {
            return redirect()->back()->with('error', 'V-02/V-03/V-09: complete representacion, contrato principal, servicio detallado y debida diligencia.');
        }
        if (count($bases) !== count($basesIds) || count($purposes) !== count($purposeIds) || $holders === [] || $categories === [] || $operations === [] || $systems === [] || $countries === []) {
            return redirect()->back()->with('error', 'V-04: el alcance contractual esta vacio o no coincide con el inventario vigente.');
        }
        if ((! empty($third['datos_biometricos']) && ! in_array('biometricos', $categories, true))
            || (! empty($third['datos_menores']) && ! in_array('menores', $categories, true))
            || (! empty($third['usa_videovigilancia']) && ! in_array('imagen_video', $categories, true))) {
            return redirect()->back()->with('error', 'V-05: las categorias declaradas contradicen las banderas de biometria, menores o videovigilancia del inventario.');
        }
        foreach ($purposes as $purpose) { if (! in_array((int) $purpose['base_id'], $basesIds, true)) { return redirect()->back()->with('error', 'V-04: cada finalidad debe pertenecer a una base transmitida.'); } }
        if (array_diff($operations, PrivacyProcessorAgreementService::OPERATIONS) || array_diff($requiredMeasures, $measures)) {
            return redirect()->back()->with('error', 'V-04/V-05: operaciones o medidas insuficientes para el nivel de riesgo.');
        }
        if (! $validUntil || $validUntil < date('Y-m-d') || ! $countryCheck || $countryCheck < date('Y-m-d', strtotime('-12 months'))
            || empty($third['canal_incidentes_probado_at']) || (int) $third['plazo_incidente_dias'] < 1 || (int) $third['plazo_incidente_dias'] > 3 || ! $responsibleSignature) {
            return redirect()->back()->with('error', 'V-06/V-08/V-09/V-10: verifique paises, vigencia, canal, plazo de incidente y firma del Responsable.');
        }
        if ($third['nivel_riesgo'] !== 'basico' && empty($third['seguro_evidencia'])) { return redirect()->back()->with('error', 'Anexo E: encargos de riesgo medio o alto requieren garantia registrada.'); }
        $subprocessors = $this->vault()->decryptRows('dp_subencargados', $db->table('dp_subencargados')->where('cliente_id', $cliente['id'])->where('tercero_id', $thirdId)->where('activo', 1)->get()->getResultArray());
        foreach ($subprocessors as &$sub) { $sub['datos'] = json_decode((string) $sub['datos_json'], true) ?: []; if (! in_array($sub['pais'], $countries, true)) { return redirect()->back()->with('error', 'V-06/V-07: el pais de un subencargado no figura en el alcance.'); } } unset($sub);
        $versions = []; $dependencies = [];
        foreach (['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad', 'confidencialidad'] as $type) {
            $doc = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', $type)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
            if (! $doc) { return redirect()->back()->with('error', 'Debe publicar los Documentos 1 a 6 antes de generar el contrato.'); }
            $versions[$type] = (int) $doc['version']; $dependencies[$type] = ['version' => (int) $doc['version'], 'hash' => $doc['hash_sha256']];
        }
        $instanceVersion = (int) ($db->table('dp_acuerdos_encargado')->selectMax('version_instancia', 'v')->where('cliente_id', $cliente['id'])->where('tercero_id', $thirdId)->get()->getRowArray()['v'] ?? 0) + 1;
        $program = $this->program($cliente); $config = json_decode((string) $program['config_json'], true) ?: []; $now = date('Y-m-d H:i:s');
        $instanceData = ['responsible' => $cliente['nombre_tercero'], 'responsible_id' => $cliente['documento'] ?? $program['responsable_documento'],
            'responsible_representative' => (string) session()->get('nombre'), 'processor' => $third['nombre'], 'processor_id' => $third['documento'],
            'processor_representative' => $third['representante_nombre'], 'processor_representative_id' => $third['representante_documento'],
            'representation_evidence' => $third['representacion_evidencia'], 'main_contract' => $third['contrato_principal_ref'], 'service' => $service,
            'bases' => array_column($bases, 'nombre'), 'holders' => $holders, 'categories' => $categories, 'purposes' => array_column($purposes, 'descripcion'),
            'operations' => $operations, 'systems' => $systems, 'countries' => $countries, 'classification' => $third['clasificacion_rol'],
            'classification_justification' => $third['clasificacion_justificacion'], 'classification_date' => $third['clasificado_at'],
            'classification_evaluator' => (string) session()->get('nombre'), 'classification_answers' => json_decode((string) $third['clasificacion_json'], true) ?: [],
            'risk' => $third['nivel_riesgo'], 'measures' => $measures, 'log_months' => $third['logs_retencion_meses'], 'backup_days' => $third['backup_rotacion_dias'],
            'rto' => $third['rto_horas'], 'rpo' => $third['rpo_horas'], 'rights_channel' => $program['canal_email'],
            'incident_channel' => $config['security_canal_incidentes'] ?? $program['canal_email'], 'incident_days' => $third['plazo_incidente_dias'],
            'instruction_channel' => $program['canal_email'], 'country_check_date' => $countryCheck, 'subprocessors' => $subprocessors,
            'flags' => ['sensitive' => (bool) $third['datos_sensibles'], 'biometric' => (bool) $third['datos_biometricos'], 'video' => (bool) $third['usa_videovigilancia'], 'minors' => (bool) $third['datos_menores']],
            'insurance' => $third['seguro_evidencia'], 'valid_from' => date('Y-m-d'), 'valid_until' => $validUntil, 'versions' => $versions,
            'instance_version' => $instanceVersion, 'generated_at' => $now, 'volume' => (string) ($this->request->getPost('volumen_frecuencia') ?: 'Operacion recurrente segun demanda del servicio'),
            'access_profiles' => (string) ($this->request->getPost('perfiles_acceso') ?: 'Personal nominal autorizado por necesidad de conocer')];
        $instance = (new PrivacyProcessorAgreementService())->build($instanceData); $token = bin2hex(random_bytes(32));
        $variables = $instanceData; unset($variables['responsible_signature']); $variables['document_dependencies'] = $dependencies;
        $db->table('dp_acuerdos_encargado')->insert($this->vault()->encryptRow('dp_acuerdos_encargado', ['cliente_id' => $cliente['id'], 'tercero_id' => $thirdId, 'documento_id' => $master['id'],
            'documento_version' => $master['version'], 'documento_hash' => $master['hash_sha256'], 'version_instancia' => $instanceVersion, 'token' => $token,
            'variables_json' => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'instancia_html' => $instance['html'], 'instancia_hash' => $instance['hash'],
            'vigencia_desde' => date('Y-m-d'), 'vigencia_hasta' => $validUntil, 'responsable_firmante' => session()->get('nombre'),
            'responsable_firma' => $responsibleSignature, 'responsable_firma_hash' => hash('sha256', $responsibleSignature), 'responsable_firmado_at' => $now,
            'responsable_ip' => $this->request->getIPAddress(), 'estado' => 'pendiente_firma', 'created_at' => $now, 'updated_at' => $now]));
        $id = (int) $db->insertID();
        foreach ($db->table('dp_acuerdos_encargado')->where('cliente_id', $cliente['id'])->where('tercero_id', $thirdId)->where('id !=', $id)->whereIn('estado', ['pendiente_firma', 'vigente'])->get()->getResultArray() as $previous) {
            $db->table('dp_acuerdos_encargado')->where('id', $previous['id'])->update($this->vault()->encryptRow('dp_acuerdos_encargado', ['estado' => 'superado', 'terminado_at' => $now, 'terminacion_motivo' => 'Nueva instancia generada', 'updated_at' => $now]));
            $this->processorEvent((int) $previous['id'], 'superado', ['nueva_instancia_id' => $id]);
        }
        $db->table('dp_terceros')->where('id', $thirdId)->update(['habilitado_datos' => 0, 'contrato_vigente' => 0, 'updated_at' => $now]);
        $this->processorEvent($id, 'generacion_y_firma_responsable', ['hash' => $instance['hash'], 'dependencies' => $dependencies]);
        $url = base_url('acuerdo-encargado/' . $token); $mail = (new EmailService())->sendPrivacyMessage($third['representante_email'], 'Acuerdo de transmision para firma', '<p>Revise y suscriba la instancia contractual individual:</p><p><a href="' . esc($url, 'attr') . '">Abrir acuerdo</a></p>', null, (int) $cliente['id']);
        return redirect()->back()->with($mail['success'] ? 'success' : 'error', $mail['success'] ? 'Instancia sellada por el Responsable y enviada al Encargado.' : 'Instancia creada, pero no fue posible enviar el correo.');
    }

    public function terminateProcessorAgreement(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId); $db = db_connect(); $id = (int) $this->request->getPost('acuerdo_id');
        $agreement = $cliente ? $db->table('dp_acuerdos_encargado')->where('cliente_id', $cliente['id'])->where('id', $id)->get()->getRowArray() : null;
        if ($agreement) { $agreement = $this->vault()->decryptRow('dp_acuerdos_encargado', $agreement); }
        $reason = trim((string) $this->request->getPost('motivo'));
        if (! $agreement || $reason === '') { return redirect()->back()->with('error', 'La terminacion exige acuerdo valido y motivo.'); }
        $now = date('Y-m-d H:i:s'); $db->table('dp_acuerdos_encargado')->where('id', $id)->update($this->vault()->encryptRow('dp_acuerdos_encargado', ['estado' => 'terminacion_pendiente', 'terminado_at' => $now, 'terminacion_motivo' => $reason, 'updated_at' => $now]));
        $db->table('dp_terceros')->where('id', $agreement['tercero_id'])->update(['habilitado_datos' => 0, 'contrato_vigente' => 0, 'updated_at' => $now]);
        $this->processorEvent($id, 'terminacion_iniciada', ['motivo' => $reason]);
        return redirect()->back()->with('success', 'Envio de datos bloqueado. El expediente queda abierto hasta verificar el Anexo F.');
    }

    public function certifyProcessorTermination(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId); $db = db_connect(); $id = (int) $this->request->getPost('acuerdo_id');
        $agreement = $cliente ? $db->table('dp_acuerdos_encargado')->where('cliente_id', $cliente['id'])->where('id', $id)->where('estado', 'terminacion_pendiente')->get()->getRowArray() : null;
        $evidence = trim((string) $this->request->getPost('evidencia')); $detail = trim((string) $this->request->getPost('detalle'));
        if (! $agreement || $evidence === '' || $detail === '') { return redirect()->back()->with('error', 'V-14: cargue referencia y detalle verificable de devolucion, produccion, respaldos y excepciones.'); }
        $now = date('Y-m-d H:i:s'); $payload = ['detalle' => $detail, 'no_resucitar_confirmado' => (bool) $this->request->getPost('no_resucitar'), 'verificado_at' => $now];
        if (! $payload['no_resucitar_confirmado']) { return redirect()->back()->with('error', 'La certificacion debe confirmar bloqueo y no reactivacion desde respaldos.'); }
        $db->table('dp_encargado_certificaciones')->insert($this->vault()->encryptRow('dp_encargado_certificaciones', ['cliente_id' => $cliente['id'], 'acuerdo_id' => $id, 'detalle_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'evidencia' => $evidence, 'evidencia_hash' => hash('sha256', $evidence . '|' . $detail), 'verificado_por' => session()->get('user_id'), 'verificado_at' => $now, 'created_at' => $now]));
        $db->table('dp_acuerdos_encargado')->where('id', $id)->update(['estado' => 'terminado', 'updated_at' => $now]); $this->processorEvent($id, 'anexo_f_verificado', $payload);
        return redirect()->back()->with('success', 'Anexo F verificado y expediente contractual cerrado.');
    }

    public function createProcessorInstruction(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId); $db = db_connect(); $id = (int) $this->request->getPost('acuerdo_id'); $content = trim((string) $this->request->getPost('contenido'));
        $agreement = $cliente ? $db->table('dp_acuerdos_encargado')->where('cliente_id', $cliente['id'])->where('id', $id)->where('estado', 'vigente')->get()->getRowArray() : null;
        if (! $agreement || $content === '') { return redirect()->back()->with('error', 'Solo puede instruirse un Encargado con acuerdo vigente.'); }
        $db->table('dp_encargado_instrucciones')->insert($this->vault()->encryptRow('dp_encargado_instrucciones', ['cliente_id' => $cliente['id'], 'acuerdo_id' => $id, 'emisor_usuario_id' => session()->get('user_id'),
            'contenido' => $content, 'contenido_hash' => hash('sha256', $content), 'estado' => 'emitida', 'created_at' => date('Y-m-d H:i:s')]));
        $this->processorEvent($id, 'instruccion_emitida', ['contenido_hash' => hash('sha256', $content)]);
        return redirect()->back()->with('success', 'Instruccion documentada y sellada.');
    }

    public function generateDocuments(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $program = $this->program($cliente);
        $db = db_connect();
        $bases = $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
        $purposes = $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
        $sensitiveItems = $db->table('dp_finalidad_datos_sensibles')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
        foreach ($purposes as &$purpose) {
            $purpose['datos_sensibles_detalle'] = array_values(array_filter($sensitiveItems, static fn ($item) => (int) $item['finalidad_id'] === (int) $purpose['id']));
        }
        unset($purpose);
        $ids = (new PrivacyDocumentService())->generateSet($cliente, $program, $bases, $purposes);
        PrivacyAudit::record((int) $cliente['id'], 'generar_versiones', 'documento', null, null, ['ids' => $ids]);
        return redirect()->back()->with('success', count($ids) . ' nuevas versiones documentales generadas para revision.');
    }

    public function saveDocument(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getPost('documento_id');
        $model = new DpDocumentoModel();
        $document = $cliente ? $model->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $document || ! in_array($document['estado'], ['borrador', 'en_revision'], true)) {
            return redirect()->back()->with('error', 'El documento no existe o su version ya no es editable.');
        }
        $content = trim((string) $this->request->getPost('contenido_html'));
        if ($content === '') {
            return redirect()->back()->with('error', 'El contenido no puede quedar vacio.');
        }
        $safe = $this->sanitizeLegalHtml($content);
        $data = ['contenido_html' => $safe, 'hash_sha256' => hash('sha256', $safe), 'estado' => 'en_revision'];
        $model->update($id, $data);
        PrivacyAudit::record((int) $cliente['id'], 'editar', 'documento', $id, ['hash' => $document['hash_sha256']], ['hash' => $data['hash_sha256']]);
        return redirect()->back()->with('success', 'Documento guardado y enviado a revision.');
    }

    public function transitionDocument(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getPost('documento_id');
        $action = (string) $this->request->getPost('action');
        $model = new DpDocumentoModel();
        $document = $cliente ? $model->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $document) {
            return $this->denied();
        }
        $data = match ($action) {
            'approve' => ['estado' => 'aprobado', 'aprobado_por' => session()->get('user_id'), 'aprobado_at' => date('Y-m-d H:i:s'), 'vigente_desde' => date('Y-m-d')],
            'publish' => $document['estado'] === 'aprobado' ? ['estado' => 'publicado', 'publicado_at' => date('Y-m-d H:i:s')] : [],
            'review' => ['estado' => 'en_revision'],
            default => [],
        };
        if ($data === []) {
            return redirect()->back()->with('error', 'Transicion documental no permitida.');
        }
        if (! hash_equals((string) $document['hash_sha256'], hash('sha256', (string) $document['contenido_html']))) {
            return redirect()->back()->with('error', 'COPIA NO VALIDA: el contenido no coincide con su hash SHA-256.');
        }
        if ($action === 'approve' && in_array($document['tipo'], ['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad', 'confidencialidad', 'encargados'], true)) {
            $problems = $this->documentReadinessProblems($cliente, $document);
            if ($problems !== []) {
                return redirect()->back()->with('error', 'No se puede aprobar: ' . implode(' ', $problems));
            }
        }
        $model->update($id, $data);
        if ($document['tipo'] === 'aviso') {
            $variantData = ['estado' => $data['estado'], 'updated_at' => date('Y-m-d H:i:s')];
            if ($action === 'approve') {
                $variantData['vigente_desde'] = $data['vigente_desde'];
            } elseif ($action === 'publish') {
                $variantData['publicado_at'] = $data['publicado_at'];
            }
            db_connect()->table('dp_aviso_variantes')->where('documento_id', $id)->update($variantData);
        }
        PrivacyAudit::record((int) $cliente['id'], $action, 'documento', $id, ['estado' => $document['estado']], $data);
        return redirect()->back()->with('success', 'Estado documental actualizado.');
    }

    public function documentPdf(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getGet('id');
        $document = $cliente ? (new DpDocumentoModel())->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $document || ! hash_equals((string) $document['hash_sha256'], hash('sha256', (string) $document['contenido_html']))) {
            return $this->denied();
        }
        $path = $document['pdf_ruta'] && is_file(WRITEPATH . $document['pdf_ruta'])
            ? $document['pdf_ruta'] : (new PrivacyPdf())->document($document, $cliente);
        PrivacyAudit::record((int) $cliente['id'], 'descargar_pdf', 'documento', $id);
        return $this->pdfResponse($path, url_title($document['titulo'], '-', true) . '-v' . $document['version'] . '.pdf');
    }

    public function consentPdf(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getGet('id');
        $consent = $cliente ? (new \App\Models\DpConsentimientoModel())->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $consent) {
            return $this->denied();
        }
        $db = db_connect();
        $document = $consent['documento_id'] ? $db->table('dp_documentos')->where('id', $consent['documento_id'])->get()->getRowArray() : null;
        $purposes = $db->table('dp_finalidades f')->select('f.*, b.nombre AS base_nombre')->join('dp_bases_datos b', 'b.id=f.base_id')
            ->where('f.cliente_id', $cliente['id'])->get()->getResultArray();
        $path = (new PrivacyPdf())->consent($consent, $cliente, $purposes, $document);
        PrivacyAudit::record((int) $cliente['id'], 'consultar_expediente', 'consentimiento', $id);
        return $this->pdfResponse($path, 'constancia-DP-C-' . $id . '.pdf');
    }

    public function publishNoticeVariant(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $variantId = (int) $this->request->getPost('variante_id');
        $db = db_connect();
        $variant = $cliente ? $db->table('dp_aviso_variantes')->where('cliente_id', $cliente['id'])->where('id', $variantId)->get()->getRowArray() : null;
        $channel = (string) $this->request->getPost('canal');
        $location = trim((string) $this->request->getPost('ubicacion'));
        if (! $variant || $variant['estado'] !== 'publicado' || ! in_array($channel, ['fisico', 'web', 'formulario', 'correo', 'otro'], true) || $location === '') {
            return redirect()->back()->with('error', 'La variante debe estar publicada y requiere un canal y una ubicacion validos.');
        }
        try {
            $evidencePath = $this->saveNoticeEvidence((int) $cliente['id']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        $now = date('Y-m-d H:i:s');
        $db->table('dp_aviso_publicaciones')->insert([
            'cliente_id' => $cliente['id'], 'variante_id' => $variantId, 'canal' => $channel,
            'ubicacion' => $location, 'evidencia_ruta' => $evidencePath,
            'evidencia_hash' => $evidencePath ? hash_file('sha256', WRITEPATH . $evidencePath) : null,
            'publicado_at' => $now, 'usuario_id' => session()->get('user_id'),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $id = (int) $db->insertID();
        PrivacyAudit::record((int) $cliente['id'], 'registrar_publicacion', 'aviso_variante', $variantId, null, ['publicacion_id' => $id, 'canal' => $channel, 'ubicacion' => $location]);
        return redirect()->back()->with('success', 'Publicacion de la variante registrada con trazabilidad.');
    }

    public function createRequest(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $type = (string) $this->request->getPost('tipo');
        $received = date('Y-m-d H:i:s');
        $config = json_decode((string) ($this->program($cliente)['config_json'] ?? '{}'), true) ?: [];
        $legalReceipt = PrivacyBusinessDays::legalReceipt($received, (string) ($config['request_cutoff_time'] ?? '17:00'));
        $text = trim((string) $this->request->getPost('solicitud_texto'));
        if ($type === 'consulta' && $text === '') {
            $text = 'Consulta general sobre los datos personales del titular.';
        }
        $model = new DpSolicitudModel();
        $data = [
            'cliente_id' => $cliente['id'], 'radicado' => $this->radicado((int) $cliente['id']), 'tipo' => $type,
            'clasificacion_original' => $type,
            'titular_nombre' => trim((string) $this->request->getPost('titular_nombre')),
            'titular_documento' => $this->nullable('titular_documento'),
            'titular_email' => trim((string) $this->request->getPost('titular_email')),
            'canal' => 'aplicativo_interno', 'calidad_solicitante' => (string) ($this->request->getPost('calidad_solicitante') ?: 'titular'),
            'legitimacion_tipo' => $this->nullable('legitimacion_tipo'), 'solicitud_texto' => $text,
            'estado' => 'recibida', 'identidad_estado' => 'verificada', 'identidad_verificada_at' => $received,
            'recibida_at' => $received, 'fecha_ingreso_real' => $received, 'fecha_recepcion_legal' => $legalReceipt,
            'acuse_at' => $received, 'completa_at' => $received,
            'vence_at' => PrivacyRequestWorkflow::initialDeadline($type, $legalReceipt), 'responsable_usuario_id' => session()->get('user_id'),
        ];
        $data['acuse_hash'] = hash('sha256', json_encode([$data['radicado'], $received, $legalReceipt, $type], JSON_UNESCAPED_UNICODE));
        if (! $this->validateData($data, [
            'tipo' => 'required|in_list[consulta,reclamo,rectificacion,actualizacion,revocatoria,supresion]',
            'titular_nombre' => 'required|max_length[191]', 'titular_email' => 'required|valid_email|max_length[191]',
            'solicitud_texto' => $type === 'consulta' ? 'required' : 'required|min_length[10]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $db = db_connect();
        $db->transStart();
        $id = (int) $model->insert($data, true);
        foreach ($db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray() as $base) {
            $db->table('dp_solicitud_bases')->insert(['solicitud_id' => $id, 'base_id' => $base['id'], 'accion' => 'por_evaluar', 'estado' => 'pendiente', 'created_at' => $received, 'updated_at' => $received]);
        }
        foreach ($db->table('dp_terceros')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray() as $thirdParty) {
            $db->table('dp_solicitud_terceros')->insert(['solicitud_id' => $id, 'tercero_id' => $thirdParty['id'],
                'accion' => 'por_evaluar', 'estado' => 'pendiente', 'created_at' => $received, 'updated_at' => $received]);
        }
        $this->requestEvent($id, 'radicacion', ['radicado' => $data['radicado'], 'recepcion_real' => $received, 'recepcion_legal' => $legalReceipt, 'acuse_hash' => $data['acuse_hash']]);
        $db->transComplete();
        PrivacyAudit::record((int) $cliente['id'], 'radicar', 'solicitud', $id, null, $data);
        return redirect()->back()->with('success', 'Solicitud radicada como ' . $data['radicado'] . '.');
    }

    public function updateRequest(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getPost('solicitud_id');
        $model = new DpSolicitudModel();
        $request = $cliente ? $model->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $request) {
            return $this->denied();
        }
        $action = (string) $this->request->getPost('action');
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $isComplaint = PrivacyRequestWorkflow::isComplaint((string) $request['tipo']);
        if ($action === 'verify') {
            if (($request['identidad_estado'] ?? '') === 'verificada') {
                return redirect()->back()->with('error', 'La identidad ya fue verificada y el plazo ya esta corriendo.');
            }
            $completeAt = $now;
            $config = json_decode((string) ($this->program($cliente)['config_json'] ?? '{}'), true) ?: [];
            $legalComplete = PrivacyBusinessDays::legalReceipt($completeAt, (string) ($config['request_cutoff_time'] ?? '17:00'));
            $data = ['estado' => 'recibida', 'identidad_estado' => 'verificada', 'identidad_verificada_at' => $completeAt,
                'completa_at' => $completeAt, 'subsanada_at' => ! empty($request['subsanacion_solicitada_at']) ? $completeAt : null,
                'vence_at' => $request['tipo'] === 'consulta' && ! empty($request['vence_at'])
                    ? $request['vence_at'] : PrivacyRequestWorkflow::initialDeadline((string) $request['tipo'], $legalComplete)];
        } elseif ($action === 'request_info') {
            $detail = trim((string) $this->request->getPost('subsanacion_detalle'));
            if (! $isComplaint || $detail === '') {
                return redirect()->back()->with('error', $isComplaint ? 'Indica exactamente que debe subsanarse.' : 'Una consulta no se somete al tramite de subsanacion de reclamos.');
            }
            $data = ['estado' => 'subsanacion', 'identidad_estado' => 'subsanacion', 'subsanacion_solicitada_at' => $now,
                'subsanacion_detalle' => $detail, 'subsanacion_limite_at' => PrivacyRequestWorkflow::abandonmentLimit($now),
                'completa_at' => null, 'vence_at' => null];
        } elseif ($action === 'abandon') {
            if (! $isComplaint || empty($request['subsanacion_limite_at']) || $request['subsanacion_limite_at'] > $now) {
                return redirect()->back()->with('error', 'El desistimiento solo procede tras dos meses sin subsanar.');
            }
            $data = ['estado' => 'cerrada', 'identidad_estado' => 'desistida', 'resultado' => 'desistida',
                'desistida_at' => $now, 'cerrada_at' => $now,
                'respuesta_texto' => 'El expediente se cierra por falta de subsanacion dentro del plazo aplicable.'];
        } elseif ($action === 'mark') {
            $reason = trim((string) $this->request->getPost('reclamo_motivo'));
            if (! $isComplaint || ($request['identidad_estado'] ?? '') !== 'verificada' || $reason === '') {
                return redirect()->back()->with('error', 'Verifica la identidad antes de iniciar el tramite.');
            }
            $data = ['estado' => 'en_tramite', 'reclamo_marcado_at' => $now, 'reclamo_motivo' => $reason];
            db_connect()->table('dp_solicitud_bases')->where('solicitud_id', $id)->update([
                'leyenda' => 'Reclamo en tramite - ' . $request['radicado'], 'leyenda_desde' => $now, 'updated_at' => $now,
            ]);
        } elseif ($action === 'extend') {
            $reason = trim((string) $this->request->getPost('prorroga_motivo'));
            if (! PrivacyRequestWorkflow::canExtend($request, $today) || $reason === '') {
                return redirect()->back()->with('error', 'La prorroga requiere motivo y comunicacion antes de vencer el termino inicial; solo puede aplicarse una vez.');
            }
            $data = ['estado' => 'prorrogada', 'prorroga_hasta' => PrivacyRequestWorkflow::extensionDeadline((string) $request['tipo'], $request['vence_at']),
                'prorroga_motivo' => $reason, 'prorroga_notificada_at' => $now];
        } elseif ($action === 'transfer') {
            $recipient = trim((string) $this->request->getPost('traslado_destinatario'));
            if (! $isComplaint || $recipient === '') {
                return redirect()->back()->with('error', 'Identifica al Responsable competente para trasladar el reclamo.');
            }
            $data = ['estado' => 'trasladada', 'resultado' => 'trasladada', 'traslado_destinatario' => $recipient,
                'trasladada_at' => $now, 'traslado_notificado_at' => $now, 'cerrada_at' => $now,
                'respuesta_texto' => 'El reclamo fue trasladado al Responsable competente: ' . $recipient . '.'];
        } elseif ($action === 'reclassify') {
            $newType = (string) $this->request->getPost('nuevo_tipo');
            $reason = trim((string) $this->request->getPost('reclasificacion_motivo'));
            if (! in_array($newType, PrivacyRequestWorkflow::TYPES, true) || $reason === '') {
                return redirect()->back()->with('error', 'Selecciona la clasificacion correcta y justifica el cambio.');
            }
            $deadline = ! empty($request['completa_at']) ? PrivacyRequestWorkflow::initialDeadline($newType, substr((string) $request['completa_at'], 0, 10)) : null;
            if ($deadline && ! empty($request['vence_at']) && $request['vence_at'] < $deadline) {
                $deadline = $request['vence_at'];
            }
            $data = ['tipo' => $newType, 'reclasificacion_motivo' => $reason, 'reclasificada_at' => $now, 'vence_at' => $deadline];
        } elseif ($action === 'close') {
            if (($request['identidad_estado'] ?? '') !== 'verificada') {
                return redirect()->back()->with('error', 'No se puede cerrar de fondo una solicitud sin identidad verificada.');
            }
            $pending = db_connect()->table('dp_solicitud_bases')->where('solicitud_id', $id)->where('estado !=', 'ejecutada')->countAllResults();
            if ($pending > 0 && $isComplaint) {
                return redirect()->back()->with('error', 'Debes decidir y ejecutar la accion en todas las bases antes de cerrar.');
            }
            $thirdPartyPending = db_connect()->table('dp_solicitud_terceros')->where('solicitud_id', $id)->whereNotIn('estado', ['confirmada', 'no_aplica'])->countAllResults();
            if ($thirdPartyPending > 0 && $isComplaint) {
                return redirect()->back()->with('error', 'Debes registrar la orden y confirmacion de cada Encargado o marcar que no aplica.');
            }
            $response = trim((string) $this->request->getPost('respuesta_texto'));
            $result = (string) $this->request->getPost('resultado');
            if ($response === '' || ! in_array($result, ['total', 'parcial', 'bloqueo', 'negada', 'informada'], true)) {
                return redirect()->back()->with('error', 'Completa la decision y el texto de respuesta.');
            }
            $foundation = $this->nullable('fundamento_conservacion');
            $kept = $this->nullable('datos_conservados');
            $until = $this->nullable('conservacion_hasta');
            if (in_array($result, ['parcial', 'bloqueo', 'negada'], true) && (! $foundation || ! $kept || ! $until)) {
                return redirect()->back()->with('error', 'La respuesta parcial, bloqueada o negada debe identificar fundamento, datos conservados y plazo.');
            }
            if (in_array($result, ['parcial', 'bloqueo', 'negada'], true) && ! str_contains($response, 'Superintendencia de Industria y Comercio')) {
                $response .= "\n\nAgotado este tramite, el Titular puede presentar queja ante la Delegatura para la Proteccion de Datos Personales de la Superintendencia de Industria y Comercio (SIC).";
            }
            $deadline = $request['prorroga_hasta'] ?: $request['vence_at'];
            $late = $deadline && $today > $deadline;
            $lateReason = trim((string) $this->request->getPost('vencimiento_causa'));
            if ($late && $lateReason === '') {
                return redirect()->back()->with('error', 'El expediente esta vencido: registra la causa sin alterar el termino legal.');
            }
            $data = ['estado' => 'cerrada', 'resultado' => $result, 'respuesta_texto' => $response,
                'respuesta_hash' => hash('sha256', $response), 'fundamento_conservacion' => $foundation,
                'datos_conservados' => $kept, 'conservacion_hasta' => $until, 'cerrada_at' => $now,
                'leyenda_retirada_at' => $now, 'vencimiento_registrado_at' => $late ? $now : null,
                'vencimiento_causa' => $late ? $lateReason : null];
            db_connect()->table('dp_solicitud_bases')->where('solicitud_id', $id)->update(['leyenda_retirada_at' => $now, 'updated_at' => $now]);
        } else {
            return redirect()->back()->with('error', 'Accion no reconocida.');
        }
        $model->update($id, $data);
        $this->requestEvent($id, $action, $data);
        PrivacyAudit::record((int) $cliente['id'], $action, 'solicitud', $id, ['estado' => $request['estado']], $data);
        if (in_array($action, ['verify', 'request_info', 'extend', 'transfer'], true)) {
            $this->notifyRequestStatus($cliente, array_merge($request, $data), $action);
        }
        if ($action === 'close') {
            $request = array_merge($request, $data);
            $sent = $this->closeRequest($cliente, $request);
        }
        $message = $action === 'close'
            ? ($sent ? 'Expediente cerrado; SendGrid acepto la respuesta.' : 'Expediente cerrado. El correo quedo fallido y requiere reintento.')
            : 'Expediente actualizado.';
        return redirect()->back()->with($action === 'close' && ! $sent ? 'error' : 'success', $message);
    }

    public function executeRequestBase(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $rowId = (int) $this->request->getPost('solicitud_base_id');
        $db = db_connect();
        $row = $cliente ? $db->table('dp_solicitud_bases sb')->select('sb.*, s.cliente_id')
            ->join('dp_solicitudes s', 's.id = sb.solicitud_id')->where('sb.id', $rowId)->where('s.cliente_id', $cliente['id'])->get()->getRowArray() : null;
        $row = $row ? $this->vault()->decryptRow('dp_solicitud_bases', $row) : null;
        if (! $row) {
            return $this->denied();
        }
        $action = (string) $this->request->getPost('accion');
        if (! in_array($action, ['no_encontrado', 'suprimir', 'anonimizar', 'bloquear', 'conservar', 'rectificar', 'actualizar'], true)) {
            return redirect()->back()->with('error', 'Selecciona una accion valida.');
        }
        $evidence = $this->nullable('evidencia');
        $oldValue = trim((string) $this->request->getPost('valor_anterior'));
        $newValue = $this->nullable('valor_nuevo');
        $source = $this->nullable('fuente_correccion');
        $blockedUntil = $this->nullable('bloqueado_hasta');
        if ($action !== 'no_encontrado' && ! $evidence) {
            return redirect()->back()->with('error', 'La ejecucion requiere evidencia verificable.');
        }
        if (in_array($action, ['rectificar', 'actualizar'], true) && ($oldValue === '' || ! $newValue || ! $source)) {
            return redirect()->back()->with('error', 'La correccion exige valor anterior, valor nuevo y fuente.');
        }
        if (in_array($action, ['bloquear', 'conservar'], true) && ! $blockedUntil) {
            return redirect()->back()->with('error', 'Indica hasta cuando se conserva o bloquea el dato.');
        }
        $data = ['accion' => $action, 'estado' => 'ejecutada', 'detalle' => $this->nullable('detalle'),
            'evidencia' => $evidence, 'valor_anterior_hash' => $oldValue !== '' ? hash('sha256', $oldValue) : null,
            'valor_nuevo' => $newValue, 'fuente_correccion' => $source, 'bloqueado_hasta' => $blockedUntil,
            'supresion_programada_at' => $blockedUntil ? $blockedUntil . ' 00:00:00' : null,
            'ejecutado_por' => session()->get('user_id'), 'ejecutado_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $db->table('dp_solicitud_bases')->where('id', $rowId)->update($this->vault()->encryptRow('dp_solicitud_bases', $data));
        $this->requestEvent((int) $row['solicitud_id'], 'accion_base', ['solicitud_base_id' => $rowId, 'base_id' => $row['base_id'], 'accion' => $action, 'evidencia_hash' => $evidence ? hash('sha256', $evidence) : null]);
        PrivacyAudit::record((int) $cliente['id'], 'ejecutar', 'solicitud_base', $rowId, $row, $data);
        return redirect()->back()->with('success', 'Accion sobre la base registrada con evidencia.');
    }

    public function executeRequestThirdParty(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $rowId = (int) $this->request->getPost('solicitud_tercero_id');
        $db = db_connect();
        $row = $cliente ? $db->table('dp_solicitud_terceros st')->select('st.*, s.cliente_id')
            ->join('dp_solicitudes s', 's.id=st.solicitud_id')->where('st.id', $rowId)->where('s.cliente_id', $cliente['id'])->get()->getRowArray() : null;
        $row = $row ? $this->vault()->decryptRow('dp_solicitud_terceros', $row) : null;
        if (! $row) {
            return $this->denied();
        }
        $state = (string) $this->request->getPost('estado');
        $evidence = $this->nullable('evidencia');
        if (! in_array($state, ['notificada', 'confirmada', 'no_aplica'], true) || ($state !== 'no_aplica' && ! $evidence)) {
            return redirect()->back()->with('error', 'Registra una orden valida y su evidencia.');
        }
        $now = date('Y-m-d H:i:s');
        $data = ['accion' => (string) ($this->request->getPost('accion') ?: 'ejecutar_decision'), 'estado' => $state,
            'notificado_at' => $state === 'no_aplica' ? null : ($row['notificado_at'] ?: $now),
            'plazo_confirmacion' => $state === 'no_aplica' ? null : PrivacyBusinessDays::add($now, 2),
            'confirmado_at' => $state === 'confirmada' ? $now : null, 'respuesta_detalle' => $this->nullable('respuesta_detalle'),
            'evidencia' => $evidence, 'updated_at' => $now];
        $db->table('dp_solicitud_terceros')->where('id', $rowId)->update($this->vault()->encryptRow('dp_solicitud_terceros', $data));
        $this->requestEvent((int) $row['solicitud_id'], 'orden_encargado', ['solicitud_tercero_id' => $rowId, 'estado' => $state, 'evidencia_hash' => $evidence ? hash('sha256', $evidence) : null]);
        PrivacyAudit::record((int) $cliente['id'], 'orden_encargado', 'solicitud_tercero', $rowId, $row, $data);
        return redirect()->back()->with('success', 'Actuacion del Encargado registrada.');
    }

    public function saveSecurityAssignment(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $role = (string) $this->request->getPost('rol');
        $responsible = trim((string) $this->request->getPost('responsable'));
        if (! $cliente || ! in_array($role, ['organo', 'administrador', 'opd', 'ti', 'seguridad_fisica'], true) || $responsible === '') {
            return redirect()->back()->with('error', 'Completa un rol de seguridad valido y su responsable.');
        }
        $db = db_connect();
        $existing = $db->table('dp_asignaciones_seguridad')->where('cliente_id', $cliente['id'])->where('rol', $role)->get()->getRowArray();
        $existing = $existing ? $this->vault()->decryptRow('dp_asignaciones_seguridad', $existing) : null;
        $data = ['responsable' => $responsible, 'acto_designacion' => $this->nullable('acto_designacion'),
            'activo' => 1, 'updated_at' => date('Y-m-d H:i:s')];
        if ($existing) {
            $db->table('dp_asignaciones_seguridad')->where('id', $existing['id'])->update($this->vault()->encryptRow('dp_asignaciones_seguridad', $data));
            $id = (int) $existing['id'];
        } else {
            $data += ['cliente_id' => $cliente['id'], 'rol' => $role, 'created_at' => date('Y-m-d H:i:s')];
            $db->table('dp_asignaciones_seguridad')->insert($this->vault()->encryptRow('dp_asignaciones_seguridad', $data));
            $id = (int) $db->insertID();
        }
        PrivacyAudit::record((int) $cliente['id'], 'asignar_raci', 'asignacion_seguridad', $id, $existing, $data);
        return redirect()->back()->with('success', 'Responsabilidad de seguridad registrada.');
    }

    public function recordSecurityControl(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $type = (string) $this->request->getPost('tipo');
        $result = (string) $this->request->getPost('resultado');
        $detail = trim((string) $this->request->getPost('detalle'));
        $evidence = trim((string) $this->request->getPost('evidencia'));
        $expires = $this->nullable('vence_at');
        $types = ['matriz_riesgos', 'revision_accesos', 'prueba_restauracion', 'capacitacion', 'evaluacion_encargados',
            'rotacion_secretos', 'prueba_filtro_ia', 'auditoria', 'mfa_roles_criticos', 'segregacion_clientes'];
        if (! $cliente || ! in_array($type, $types, true) || ! in_array($result, ['conforme', 'no_conforme'], true) || $detail === '' || $evidence === '' || ! $expires || $expires < date('Y-m-d')) {
            return redirect()->back()->with('error', 'Completa tipo, resultado, detalle, evidencia y una vigencia futura del control.');
        }
        $now = date('Y-m-d H:i:s');
        $data = ['cliente_id' => $cliente['id'], 'tipo' => $type, 'resultado' => $result, 'detalle' => $detail,
            'evidencia' => $evidence, 'evidencia_hash' => hash('sha256', $evidence), 'ejecutado_at' => $now,
            'vence_at' => $expires, 'usuario_id' => session()->get('user_id'), 'created_at' => $now];
        $db = db_connect();
        $db->table('dp_controles_seguridad')->insert($this->vault()->encryptRow('dp_controles_seguridad', $data));
        PrivacyAudit::record((int) $cliente['id'], 'registrar_evidencia', 'control_seguridad', (int) $db->insertID(), null, $data);
        return redirect()->back()->with($result === 'conforme' ? 'success' : 'error', 'Evidencia del control registrada sin posibilidad de edicion.');
    }

    public function createConfidentialityAgreement(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $db = db_connect();
        $userId = (int) $this->request->getPost('usuario_id');
        $user = $cliente ? $db->table('usuarios u')->select('u.*, r.nombre AS rol_nombre')->join('roles r', 'r.id=u.rol_id')
            ->where('u.cliente_id', $cliente['id'])->where('u.id', $userId)->where('u.deleted_at', null)->get()->getRowArray() : null;
        $master = $cliente ? $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', 'confidencialidad')
            ->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray() : null;
        if (! $user || ! $master || ! hash_equals((string) $master['hash_sha256'], hash('sha256', (string) $master['contenido_html']))) {
            return redirect()->back()->with('error', 'Se requiere usuario valido y Documento 6 publicado con integridad verificada.');
        }
        if ($userId === (int) session()->get('user_id')) {
            return redirect()->back()->with('error', 'El firmante no puede autorizar su propio alcance.');
        }
        $baseIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('bases'))));
        $purposeIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('finalidades'))));
        $operations = array_values(array_unique(array_map('strval', (array) $this->request->getPost('operaciones'))));
        $linkType = (string) $this->request->getPost('tipo_vinculo');
        $operationalRole = (string) $this->request->getPost('rol_operativo');
        $validUntil = (string) $this->request->getPost('vigencia_hasta');
        if ($baseIds === [] || $purposeIds === [] || $operations === [] || ! in_array($linkType, PrivacyConfidentialityService::LINK_TYPES, true)
            || ! in_array($operationalRole, PrivacyConfidentialityService::OPERATIONAL_ROLES, true)
            || array_diff($operations, PrivacyConfidentialityService::OPERATIONS) || $validUntil <= date('Y-m-d')
            || $validUntil > date('Y-m-d', strtotime('+12 months'))) {
            return redirect()->back()->with('error', 'Completa vinculo, bases, finalidades y operaciones; la vigencia debe ser futura y no superar doce meses.');
        }
        $bases = $this->vault()->decryptRows('dp_bases_datos', $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->whereIn('id', $baseIds)->get()->getResultArray());
        $purposes = $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('activo', 1)->whereIn('id', $purposeIds)->get()->getResultArray();
        if (count($bases) !== count($baseIds) || count($purposes) !== count($purposeIds)) {
            return redirect()->back()->with('error', 'El alcance contiene bases o finalidades inexistentes.');
        }
        foreach ($purposes as $purpose) {
            if (! in_array((int) $purpose['base_id'], $baseIds, true)) { return redirect()->back()->with('error', 'Cada finalidad debe pertenecer a una base autorizada.'); }
        }
        $allBaseCount = $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->countAllResults();
        $allPurposeCount = $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('activo', 1)->countAllResults();
        $totalJustification = $this->nullable('alcance_total_justificacion');
        if ((count($baseIds) === $allBaseCount || count($purposeIds) === $allPurposeCount
            || count($operations) === count(PrivacyConfidentialityService::OPERATIONS)) && ! $totalJustification) {
            return redirect()->back()->with('error', 'Todo alcance global exige justificacion expresa del autorizador.');
        }
        $config = json_decode((string) ($this->program($cliente)['config_json'] ?? '{}'), true) ?: [];
        if (in_array('visualizar_cctv', $operations, true) && empty($config['usa_videovigilancia'])) { return redirect()->back()->with('error', 'No puede autorizar CCTV si no esta configurado.'); }
        if (in_array('operar_biometria', $operations, true) && empty($config['usa_biometria']) && empty($config['video_identificacion_biometrica'])) { return redirect()->back()->with('error', 'No puede autorizar biometria si no esta configurada.'); }
        $versions = []; $dependencies = [];
        foreach (['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad'] as $type) {
            $doc = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', $type)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
            if (! $doc) { return redirect()->back()->with('error', 'Debe publicar los documentos 1 a 5 antes de generar una instancia.'); }
            $versions[$type] = (int) $doc['version']; $dependencies[$type] = $doc['hash_sha256'];
        }
        $baseNames = array_column($bases, 'nombre'); $purposeNames = array_column($purposes, 'descripcion');
        $flags = ['porteria' => in_array($operationalRole, ['porteria', 'vigilancia'], true),
            'video' => in_array('visualizar_cctv', $operations, true), 'biometric' => in_array('operar_biometria', $operations, true),
            'sensitive' => (bool) array_filter($bases, static fn ($base) => ! empty($base['datos_sensibles']) || ! empty($base['datos_biometricos'])),
            'minors' => (bool) array_filter($bases, static fn ($base) => ! empty($base['datos_menores'])),
            'remote' => ! empty($config['security_trabajo_remoto']), 'multi_tenant' => in_array($user['rol_nombre'], ['superadmin', 'admin'], true),
            'exports' => in_array('exportar', $operations, true)];
        if ($flags['biometric']) { $flags['sensitive'] = true; }
        $now = date('Y-m-d H:i:s');
        $instance = (new PrivacyConfidentialityService())->build(['responsible' => $cliente['nombre_tercero'], 'nit' => $cliente['documento'] ?? '',
            'signer' => $user['nombre'], 'document_type' => (string) ($this->request->getPost('tipo_documento') ?: 'CC'),
            'document_number' => trim((string) $this->request->getPost('numero_documento')), 'link_type' => $linkType,
            'role' => $operationalRole, 'authorizer' => (string) session()->get('nombre'), 'valid_from' => date('Y-m-d'), 'valid_until' => $validUntil,
            'versions' => $versions, 'version' => $master['version'], 'generated_at' => $now, 'bases' => $baseNames,
            'purposes' => $purposeNames, 'operations' => $operations, 'flags' => $flags,
            'incident_channel' => $config['security_canal_incidentes'] ?? $this->program($cliente)['canal_email'],
            'privacy_email' => $this->program($cliente)['canal_email'], 'privacy_phone' => $this->program($cliente)['canal_telefono']]);
        if (trim((string) $this->request->getPost('numero_documento')) === '') { return redirect()->back()->with('error', 'Identifica el documento del firmante.'); }
        $token = bin2hex(random_bytes(32));
        $agreementData = ['cliente_id' => $cliente['id'], 'usuario_id' => $userId,
            'documento_id' => $master['id'], 'documento_version' => $master['version'], 'documento_hash' => $master['hash_sha256'], 'token' => $token,
            'firmante_nombre' => $user['nombre'], 'tipo_documento' => (string) ($this->request->getPost('tipo_documento') ?: 'CC'),
            'numero_documento' => trim((string) $this->request->getPost('numero_documento')), 'tipo_vinculo' => $linkType,
            'rol' => $operationalRole, 'autorizador_usuario_id' => session()->get('user_id'), 'autorizador_nombre' => session()->get('nombre'),
            'bases_json' => json_encode($baseIds), 'finalidades_json' => json_encode($purposeIds), 'operaciones_json' => json_encode($operations),
            'alcance_total_justificacion' => $totalJustification, 'vigencia_desde' => date('Y-m-d'), 'vigencia_hasta' => $validUntil,
            'instancia_html' => $instance['html'], 'instancia_hash' => $instance['hash'], 'estado' => 'pendiente', 'created_at' => $now, 'updated_at' => $now];
        $db->table('dp_compromisos_confidencialidad')->insert($this->vault()->encryptRow('dp_compromisos_confidencialidad', $agreementData));
        $id = (int) $db->insertID();
        foreach ($db->table('dp_compromisos_confidencialidad')->where('cliente_id', $cliente['id'])->where('usuario_id', $userId)
            ->where('id !=', $id)->whereIn('estado', ['pendiente', 'vigente'])->get()->getResultArray() as $previous) {
            $db->table('dp_compromisos_confidencialidad')->where('id', $previous['id'])->update($this->vault()->encryptRow('dp_compromisos_confidencialidad', ['estado' => 'superado',
                'cerrado_at' => $now, 'cierre_motivo' => 'Nueva instancia generada por cambio o recertificacion', 'updated_at' => $now]));
            $this->confidentialityEvent((int) $previous['id'], 'superado', ['nueva_instancia_id' => $id]);
        }
        $this->confidentialityEvent($id, 'generacion', ['dependencies' => $dependencies, 'hash' => $instance['hash'], 'scope' => ['rol' => $operationalRole, 'bases' => $baseIds, 'purposes' => $purposeIds, 'operations' => $operations]]);
        $url = base_url('confidencialidad/' . $token);
        $html = '<p>Hola ' . esc($user['nombre']) . ',</p><p>Debes revisar y firmar tu compromiso individual para dejar evidencia de tus responsabilidades y alcance.</p><p><a href="' . esc($url, 'attr') . '">Revisar instancia individual</a></p>';
        $result = (new EmailService())->sendPrivacyMessage($user['email'], 'Compromiso individual de confidencialidad', $html, null, (int) $cliente['id']);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Instancia individual enviada al firmante.' : 'Instancia creada, pero el correo no pudo enviarse.');
    }

    public function closeConfidentialityAgreement(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId); $id = (int) $this->request->getPost('compromiso_id');
        $reason = trim((string) $this->request->getPost('cierre_motivo')); $evidence = trim((string) $this->request->getPost('devolucion_evidencia'));
        $db = db_connect(); $agreement = $cliente ? $db->table('dp_compromisos_confidencialidad')->where('cliente_id', $cliente['id'])->where('id', $id)->get()->getRowArray() : null;
        $agreement = $agreement ? $this->vault()->decryptRow('dp_compromisos_confidencialidad', $agreement) : null;
        if (! $agreement || $reason === '' || $evidence === '') { return redirect()->back()->with('error', 'La baja exige motivo y certificacion de devolucion/eliminacion.'); }
        $now = date('Y-m-d H:i:s');
        $db->table('dp_compromisos_confidencialidad')->where('id', $id)->update($this->vault()->encryptRow('dp_compromisos_confidencialidad', ['estado' => 'cerrado', 'cerrado_at' => $now,
            'cierre_motivo' => $reason, 'devolucion_certificada_at' => $now, 'devolucion_evidencia' => $evidence, 'updated_at' => $now]));
        $this->confidentialityEvent($id, 'baja', ['motivo' => $reason, 'evidencia_hash' => hash('sha256', $evidence)]);
        return redirect()->back()->with('success', 'Compromiso cerrado y baja documentada.');
    }

    public function createSecurityIncident(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $detail = trim((string) $this->request->getPost('detalle'));
        $severity = (string) ($this->request->getPost('severidad') ?: 'S4');
        $knowledgeInput = (string) ($this->request->getPost('conocimiento_at') ?: date('Y-m-d H:i:s'));
        $knowledgeAt = strtotime($knowledgeInput) !== false ? date('Y-m-d H:i:s', strtotime($knowledgeInput)) : '';
        if (! $cliente || $detail === '' || ! in_array($severity, ['S1', 'S2', 'S3', 'S4'], true) || strtotime($knowledgeAt) === false) {
            return redirect()->back()->with('error', 'Completa descripcion, severidad y fecha de conocimiento del incidente.');
        }
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $folio = 'DP-INC-' . $cliente['id'] . '-' . date('YmdHis');
        $data = ['cliente_id' => $cliente['id'], 'folio' => $folio, 'tipo' => (string) ($this->request->getPost('tipo') ?: 'seguridad_datos'),
            'severidad' => $severity, 'fuente' => $this->nullable('fuente'), 'detalle' => $detail, 'estado' => 'abierto',
            'detectado_at' => $this->request->getPost('detectado_at') && strtotime((string) $this->request->getPost('detectado_at')) !== false
                ? date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('detectado_at'))) : $now, 'conocimiento_at' => $knowledgeAt,
            'sic_vence_at' => PrivacyBusinessDays::add($knowledgeAt, 15), 'created_at' => $now, 'updated_at' => $now];
        $db->table('dp_incidentes_privacidad')->insert($this->vault()->encryptRow('dp_incidentes_privacidad', $data));
        $id = (int) $db->insertID();
        $this->incidentEvent($id, 'deteccion', $data);
        PrivacyAudit::record((int) $cliente['id'], 'crear', 'incidente_privacidad', $id, null, $data);
        return redirect()->back()->with('success', 'Incidente ' . $folio . ' registrado. Reporte SIC vence el ' . $data['sic_vence_at'] . '.');
    }

    public function updateSecurityIncident(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getPost('incidente_id');
        $db = db_connect();
        $incident = $cliente ? $db->table('dp_incidentes_privacidad')->where('cliente_id', $cliente['id'])->where('id', $id)->get()->getRowArray() : null;
        $incident = $incident ? $this->vault()->decryptRow('dp_incidentes_privacidad', $incident) : null;
        if (! $incident || $incident['estado'] === 'cerrado') {
            return $this->denied();
        }
        $action = (string) $this->request->getPost('action');
        $now = date('Y-m-d H:i:s');
        if ($action === 'classify') {
            $severity = (string) $this->request->getPost('severidad');
            if (! in_array($severity, ['S1', 'S2', 'S3', 'S4'], true)) { return redirect()->back()->with('error', 'Severidad invalida.'); }
            $data = ['severidad' => $severity, 'categorias_afectadas' => $this->nullable('categorias_afectadas'),
                'titulares_estimados' => max(0, (int) $this->request->getPost('titulares_estimados')), 'clasificado_at' => $now, 'estado' => 'clasificado'];
        } elseif ($action === 'contain') {
            $data = ['contencion_at' => $now, 'estado' => 'contenido', 'investigacion' => $this->nullable('investigacion')];
        } elseif ($action === 'decision') {
            $decision = (string) $this->request->getPost('decision_reporte');
            $reason = trim((string) $this->request->getPost('decision_motivo'));
            if (! in_array($decision, ['reportar', 'no_reportar'], true) || $reason === '') { return redirect()->back()->with('error', 'Toda decision de reporte exige motivacion escrita.'); }
            $data = ['decision_reporte' => $decision, 'decision_motivo' => $reason, 'estado' => 'decision_reporte'];
        } elseif ($action === 'report') {
            $evidence = trim((string) $this->request->getPost('sic_evidencia'));
            if (($incident['decision_reporte'] ?? '') !== 'reportar' || $evidence === '') { return redirect()->back()->with('error', 'Registra primero la decision de reportar y su constancia SIC.'); }
            $data = ['sic_reportado_at' => $now, 'sic_evidencia' => $evidence, 'estado' => 'reportado'];
        } elseif ($action === 'communicate') {
            $reason = trim((string) $this->request->getPost('titulares_comunicacion_motivo'));
            if ($reason === '') { return redirect()->back()->with('error', 'Registra contenido, constancia o motivo de la comunicacion.'); }
            $data = ['titulares_comunicados_at' => $now, 'titulares_comunicacion_motivo' => $reason];
        } elseif ($action === 'recover') {
            $data = ['recuperado_at' => $now, 'estado' => 'recuperado', 'investigacion' => $this->nullable('investigacion') ?: $incident['investigacion']];
        } elseif ($action === 'close') {
            $lessons = trim((string) $this->request->getPost('lecciones'));
            if (($incident['decision_reporte'] ?? 'pendiente') === 'pendiente' || empty($incident['decision_motivo']) || $lessons === '') {
                return redirect()->back()->with('error', 'No se cierra sin decision motivada de reporte y lecciones aprendidas.');
            }
            if (($incident['decision_reporte'] ?? '') === 'reportar' && empty($incident['sic_reportado_at'])) {
                return redirect()->back()->with('error', 'El reporte decidido debe tener constancia antes del cierre.');
            }
            if (in_array($incident['severidad'], ['S1', 'S2'], true) && empty($incident['titulares_comunicacion_motivo'])) {
                return redirect()->back()->with('error', 'Incidentes S1-S2 requieren comunicacion a titulares o motivacion de su omision.');
            }
            $data = ['estado' => 'cerrado', 'lecciones' => $lessons, 'cerrado_at' => $now, 'cerrado_por' => session()->get('user_id')];
        } else {
            return redirect()->back()->with('error', 'Actuacion de incidente no reconocida.');
        }
        $data['updated_at'] = $now;
        $db->table('dp_incidentes_privacidad')->where('id', $id)->update($this->vault()->encryptRow('dp_incidentes_privacidad', $data));
        $this->incidentEvent($id, $action, $data);
        PrivacyAudit::record((int) $cliente['id'], $action, 'incidente_privacidad', $id, ['estado' => $incident['estado']], $data);
        return redirect()->back()->with('success', 'Actuacion registrada en el expediente inmutable del incidente.');
    }

    public function requestPdf(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        $id = (int) $this->request->getGet('id');
        $request = $cliente ? (new DpSolicitudModel())->where('cliente_id', $cliente['id'])->where('id', $id)->first() : null;
        if (! $request || $request['estado'] !== 'cerrada') {
            return $this->denied();
        }
        $actions = $this->requestActions($id);
        $path = (new PrivacyPdf())->request($request, $cliente, $actions);
        return $this->pdfResponse($path, 'respuesta-' . $request['radicado'] . '.pdf');
    }

    public function reviewAi(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $db = db_connect();
        $bases = $this->vault()->decryptRows('dp_bases_datos', $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray());
        $purposes = $db->table('dp_finalidades')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
        $service = new OpenAiPrivacyService();
        try {
            $review = $service->reviewInventory($bases, $purposes, (int) $cliente['id']);
            $db->table('dp_ai_runs')->insert($this->vault()->encryptRow('dp_ai_runs', [
                'cliente_id' => $cliente['id'], 'usuario_id' => session()->get('user_id'), 'tipo' => 'revision_inventario',
                'modelo' => $review['model'], 'prompt_version' => '1.0', 'entrada_hash' => $review['input_hash'],
                'salida_json' => json_encode($review['result'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'estado' => 'completada', 'created_at' => date('Y-m-d H:i:s'),
            ]));
            $id = $db->insertID();
            PrivacyAudit::record((int) $cliente['id'], 'revisar_ia', 'ai_run', (int) $id);
            return redirect()->back()->with('success', 'Revision de IA completada y almacenada para evaluacion humana.');
        } catch (\Throwable $e) {
            $db->table('dp_ai_runs')->insert($this->vault()->encryptRow('dp_ai_runs', ['cliente_id' => $cliente['id'], 'usuario_id' => session()->get('user_id'),
                'tipo' => 'revision_inventario', 'modelo' => env('openai.model') ?: 'gpt-4o-mini', 'prompt_version' => '1.0',
                'entrada_hash' => hash('sha256', json_encode($bases)), 'estado' => 'fallida', 'error' => $e->getMessage(), 'created_at' => date('Y-m-d H:i:s')]));
            if (str_starts_with($e->getMessage(), 'FILTRO_IDENTIDAD:')) {
                $now = date('Y-m-d H:i:s');
                $folio = 'DP-INC-' . $cliente['id'] . '-' . date('YmdHis');
                $incidentData = ['cliente_id' => $cliente['id'], 'folio' => $folio,
                    'tipo' => 'falla_filtro_ia', 'severidad' => 'S2', 'fuente' => 'OpenAiPrivacyService',
                    'detalle' => 'El filtro detecto un posible identificador y bloqueo la transmision antes del envio.',
                    'estado' => 'abierto', 'detectado_at' => $now, 'conocimiento_at' => $now,
                    'sic_vence_at' => PrivacyBusinessDays::add($now, 15), 'created_at' => $now, 'updated_at' => $now];
                $db->table('dp_incidentes_privacidad')->insert($this->vault()->encryptRow('dp_incidentes_privacidad', $incidentData));
                $this->incidentEvent((int) $db->insertID(), 'deteccion_automatica', ['control' => 'filtro_identidad_openai', 'envio_bloqueado' => true]);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function export(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $rows = (new DpSolicitudModel())->where('cliente_id', $cliente['id'])->orderBy('recibida_at', 'DESC')->findAll();
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Radicado', 'Tipo', 'Titular', 'Correo', 'Estado', 'Recibida', 'Vence', 'Resultado'], ';');
        foreach ($rows as $row) {
            fputcsv($stream, [$row['radicado'], $row['tipo'], $row['titular_nombre'], $row['titular_email'], $row['estado'], $row['recibida_at'], $row['prorroga_hasta'] ?: $row['vence_at'], $row['resultado']], ';');
        }
        PrivacyAudit::record((int) $cliente['id'], 'exportar', 'solicitudes', null, null,
            ['registros' => count($rows), 'usuario_id' => session()->get('user_id'), 'finalidad' => 'Gestion operativa de derechos de Titulares']);
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="solicitudes-proteccion-datos.csv"')->setBody($content);
    }

    public function privacyPortalQr(?int $clienteId = null)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }

        $program = $this->program($cliente);
        $portalUrl = base_url('privacidad/' . $program['public_token']);
        $svg = (new QrSvgService())->render($portalUrl, $cliente['color_primario'] ?? '#111827', 420);

        return $this->response
            ->setHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->setHeader('Content-Disposition', 'inline; filename="portal-datos-personales.svg"')
            ->setBody($svg);
    }

    private function show(?int $clienteId)
    {
        $cliente = $this->cliente($clienteId);
        if (! $cliente) {
            return $this->denied();
        }
        $program = $this->program($cliente);
        $db = db_connect();
        $vault = $this->vault();
        $bases = $vault->decryptRows('dp_bases_datos', $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->orderBy('activo', 'DESC')->orderBy('nombre')->get()->getResultArray());
        $purposes = $db->table('dp_finalidades f')->select('f.*, b.nombre AS base_nombre')->join('dp_bases_datos b', 'b.id=f.base_id')
            ->where('f.cliente_id', $cliente['id'])->where('f.activo', 1)->orderBy('b.nombre')->get()->getResultArray();
        foreach ($purposes as &$purpose) {
            $purpose['base_nombre'] = $vault->decryptValue('dp_bases_datos', 'responsable_interno', $purpose['base_nombre']);
        }
        unset($purpose);
        $requests = $vault->decryptRows('dp_solicitudes', $db->table('dp_solicitudes')->where('cliente_id', $cliente['id'])->orderBy('recibida_at', 'DESC')->limit(100)->get()->getResultArray());
        foreach ($requests as &$request) {
            $request['actions'] = $this->requestActions((int) $request['id']);
            $request['third_party_actions'] = $this->requestThirdPartyActions((int) $request['id']);
            $request['events'] = $vault->decryptRows('dp_solicitud_eventos', $db->table('dp_solicitud_eventos')->where('solicitud_id', $request['id'])->orderBy('ocurrido_at')->get()->getResultArray());
        }
        unset($request);
        $metrics = [
            'bases' => count(array_filter($bases, static fn ($b) => (int) $b['activo'] === 1)),
            'documentos_publicados' => $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('estado', 'publicado')->countAllResults(),
            'negativas' => $db->table('dp_consentimientos')->where('cliente_id', $cliente['id'])->whereIn('decision', ['negado', 'parcial'])->countAllResults(),
            'abiertas' => $db->table('dp_solicitudes')->where('cliente_id', $cliente['id'])->where('estado !=', 'cerrada')->countAllResults(),
            'vencidas' => $db->table('dp_solicitudes')->where('cliente_id', $cliente['id'])->where('estado !=', 'cerrada')
                ->where('COALESCE(prorroga_hasta, vence_at) < ' . $db->escape(date('Y-m-d')), null, false)->countAllResults(),
            'incidentes_abiertos' => $db->table('dp_incidentes_privacidad')->where('cliente_id', $cliente['id'])->where('estado !=', 'cerrado')->countAllResults(),
        ];
        $incidents = $vault->decryptRows('dp_incidentes_privacidad', $db->table('dp_incidentes_privacidad')->where('cliente_id', $cliente['id'])->orderBy('detectado_at', 'DESC')->get()->getResultArray());
        foreach ($incidents as &$incident) {
            $incident['events'] = $vault->decryptRows('dp_incidente_eventos', $db->table('dp_incidente_eventos')->where('incidente_id', $incident['id'])->orderBy('ocurrido_at')->get()->getResultArray());
            $incident['sic_elapsed_days'] = PrivacyBusinessDays::elapsed((string) ($incident['conocimiento_at'] ?: $incident['detectado_at']), date('Y-m-d'));
            $incident['sic_escalated'] = $incident['estado'] !== 'cerrado' && empty($incident['sic_reportado_at']) && $incident['sic_elapsed_days'] >= 10;
        }
        unset($incident);
        $documents = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->orderBy('tipo')->orderBy('version', 'DESC')->get()->getResultArray();
        foreach ($documents as &$document) {
            $document['hash_valid'] = hash_equals((string) $document['hash_sha256'], hash('sha256', (string) $document['contenido_html']));
        }
        unset($document);
        $currentAuthorization = null;
        foreach ($documents as $document) {
            if ($document['tipo'] === 'autorizacion' && $document['estado'] === 'publicado') {
                $currentAuthorization = $document;
                break;
            }
        }
        $housingCoverage = (new PrivacyHousingCoverage())->summarize(
            (int) $cliente['id'],
            $currentAuthorization ? (int) $currentAuthorization['id'] : null
        );
        $metrics['unidades_gestionadas'] = $housingCoverage['gestionadas'];
        $metrics['unidades_pendientes'] = $housingCoverage['pendientes'] + $housingCoverage['desactualizadas'];
        $thirdParties = $vault->decryptRows('dp_terceros', $db->table('dp_terceros')->where('cliente_id', $cliente['id'])->where('activo', 1)->orderBy('nombre')->get()->getResultArray());
        $subprocessors = $vault->decryptRows('dp_subencargados', $db->table('dp_subencargados')->where('cliente_id', $cliente['id'])->where('activo', 1)->orderBy('nombre')->get()->getResultArray());
        $processorAgreements = $vault->decryptRows('dp_acuerdos_encargado', $db->table('dp_acuerdos_encargado a')->select('a.*, t.nombre AS tercero_nombre, t.representante_email')
            ->join('dp_terceros t', 't.id=a.tercero_id')->where('a.cliente_id', $cliente['id'])->orderBy('a.created_at', 'DESC')->limit(100)->get()->getResultArray());
        foreach ($processorAgreements as &$agreement) {
            $agreement['tercero_nombre'] = $vault->decryptValue('dp_terceros', 'nombre', $agreement['tercero_nombre']);
            $agreement['representante_email'] = $vault->decryptValue('dp_terceros', 'representante_email', $agreement['representante_email']);
        }
        unset($agreement);
        $processorInstructions = $vault->decryptRows('dp_encargado_instrucciones', $db->table('dp_encargado_instrucciones')->where('cliente_id', $cliente['id'])->orderBy('created_at', 'DESC')->limit(100)->get()->getResultArray());
        $consents = $vault->decryptRows('dp_consentimientos', $db->table('dp_consentimientos c')
            ->select('c.*, i.identificador AS inmueble_identificador, i.tipo AS inmueble_tipo, t.nombre AS torre_nombre')
            ->join('inmuebles i', 'i.id=c.inmueble_id AND i.cliente_id=c.cliente_id', 'left')
            ->join('torres t', 't.id=i.torre_id', 'left')
            ->where('c.cliente_id', $cliente['id'])->orderBy('c.otorgado_at', 'DESC')->limit(100)->get()->getResultArray());
        $notifications = $vault->decryptRows('dp_notificaciones', $db->table('dp_notificaciones')->where('cliente_id', $cliente['id'])->orderBy('created_at', 'DESC')->limit(30)->get()->getResultArray());
        $aiRuns = $vault->decryptRows('dp_ai_runs', $db->table('dp_ai_runs')->where('cliente_id', $cliente['id'])->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray());
        $assignments = $vault->decryptRows('dp_asignaciones_seguridad', $db->table('dp_asignaciones_seguridad')->where('cliente_id', $cliente['id'])->where('activo', 1)->orderBy('rol')->get()->getResultArray());
        $controls = $vault->decryptRows('dp_controles_seguridad', $db->table('dp_controles_seguridad')->where('cliente_id', $cliente['id'])->orderBy('ejecutado_at', 'DESC')->limit(100)->get()->getResultArray());
        $confidentialityAgreements = $vault->decryptRows('dp_compromisos_confidencialidad', $db->table('dp_compromisos_confidencialidad c')
            ->select('c.*, u.email')->join('usuarios u', 'u.id=c.usuario_id')
            ->where('c.cliente_id', $cliente['id'])->orderBy('c.created_at', 'DESC')->limit(100)->get()->getResultArray());
        return view('privacy/index', [
            'cliente' => $cliente, 'programa' => $program, 'bases' => $bases, 'finalidades' => $purposes,
            'datosSensibles' => $db->table('dp_finalidad_datos_sensibles')->where('cliente_id', $cliente['id'])->where('activo', 1)->orderBy('dato')->get()->getResultArray(),
            'terceros' => $thirdParties,
            'subencargados' => $subprocessors,
            'processorAgreements' => $processorAgreements,
            'processorInstructions' => $processorInstructions,
            'documentos' => $documents,
            'avisoVariantes' => $db->table('dp_aviso_variantes')->where('cliente_id', $cliente['id'])->orderBy('documento_id', 'DESC')->orderBy('tipo')->get()->getResultArray(),
            'avisoPublicaciones' => $db->table('dp_aviso_publicaciones')->where('cliente_id', $cliente['id'])->orderBy('publicado_at', 'DESC')->get()->getResultArray(),
            'consentimientos' => $consents,
            'housingCoverage' => $housingCoverage,
            'currentAuthorization' => $currentAuthorization,
            'exclusiones' => $db->table('dp_exclusiones')->where('cliente_id', $cliente['id'])->where('activo', 1)->countAllResults(),
            'solicitudes' => $requests, 'notificaciones' => $notifications,
            'aiRuns' => $aiRuns,
            'securityAssignments' => $assignments,
            'securityControls' => $controls,
            'securityIncidents' => $incidents,
            'securityUsers' => $db->table('usuarios u')->select('u.id, u.nombre, u.email, u.activo, r.nombre AS rol, up.confidencialidad_at, up.recertificado_at')
                ->join('roles r', 'r.id=u.rol_id')
                ->join('dp_usuario_privacidad up', 'up.usuario_id=u.id AND up.cliente_id=u.cliente_id', 'left')
                ->where('u.cliente_id', $cliente['id'])->where('u.deleted_at', null)->orderBy('u.nombre')->get()->getResultArray(),
            'confidentialityAgreements' => $confidentialityAgreements,
            'metrics' => $metrics, 'isAdmin' => in_array(session()->get('rol'), ['superadmin', 'admin'], true),
            'openAiConfigured' => (new OpenAiPrivacyService())->configured(),
            'canManage' => in_array(session()->get('rol'), ['superadmin', 'admin', 'cliente'], true),
        ]);
    }

    private function closeRequest(array $cliente, array $request): bool
    {
        $db = db_connect();
        $actions = $this->requestActions((int) $request['id']);
        $baseIds = array_values(array_unique(array_map('intval', array_column($actions, 'base_id'))));
        $purposeIds = $baseIds === [] ? [] : array_map('intval', array_column($db->table('dp_finalidades')->select('id')
            ->where('cliente_id', $cliente['id'])->whereIn('base_id', $baseIds)->where('activo', 1)->get()->getResultArray(), 'id'));
        if (in_array($request['tipo'], ['supresion', 'revocatoria'], true) && ! empty($request['titular_documento'])) {
            $hash = $this->identifierHash((string) $request['titular_documento']);
            $existing = $db->table('dp_exclusiones')->where('cliente_id', $cliente['id'])->where('identificador_hash', $hash)->get()->getRowArray();
            $row = ['cliente_id' => $cliente['id'], 'identificador_hash' => $hash,
                'email_hash' => hash('sha256', mb_strtolower($request['titular_email'])), 'alcance' => $request['resultado'] === 'total' ? 'total' : 'parcial',
                'finalidades_json' => json_encode($purposeIds), 'origen' => $request['tipo'],
                'motivo' => 'Expediente ' . $request['radicado'], 'activo' => 1, 'updated_at' => date('Y-m-d H:i:s')];
            if ($existing) {
                $db->table('dp_exclusiones')->where('id', $existing['id'])->update($row);
            } else {
                $row['created_at'] = date('Y-m-d H:i:s');
                $db->table('dp_exclusiones')->insert($row);
            }
            if ($request['tipo'] === 'revocatoria') {
                $blind = $this->vault()->blindLookup('dp_consentimientos', 'titular_documento', (string) $request['titular_documento']);
                $builder = $db->table('dp_consentimientos')->where('cliente_id', $cliente['id']);
                $builder->groupStart()->where('titular_documento_bidx', $blind);
                if (config(\Config\PrivacyEncryption::class)->blindIndexFallback) {
                    $builder->orWhere('titular_documento', $request['titular_documento']);
                }
                $consent = $builder->groupEnd()->orderBy('otorgado_at', 'DESC')->get()->getRowArray();
                $consent = $consent ? $this->vault()->decryptRow('dp_consentimientos', $consent) : null;
                if ($consent) {
                    $event = ['radicado' => $request['radicado'], 'resultado' => $request['resultado'], 'finalidades' => $purposeIds,
                        'fundamento_conservacion' => $request['fundamento_conservacion'] ?? null];
                    $eventHash = hash('sha256', json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $eventRow = [
                        'cliente_id' => $cliente['id'], 'consentimiento_id' => $consent['id'], 'tipo' => 'revocatoria',
                        'alcance_json' => json_encode($event, JSON_UNESCAPED_UNICODE),
                        'instancia_hash' => $consent['instancia_hash'] ?: hash('sha256', (string) $consent['evidencia_hash']),
                        'evidencia_hash' => $eventHash, 'canal' => 'solicitud_titular',
                        'ocurrido_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $db->table('dp_consentimiento_eventos')->insert($this->vault()->encryptRow('dp_consentimiento_eventos', $eventRow));
                }
            }
        }
        $path = (new PrivacyPdf())->request($request, $cliente, $actions);
        $subject = 'Respuesta a solicitud de datos personales - ' . $request['radicado'];
        $html = '<p>Hola ' . esc($request['titular_nombre']) . ',</p><p>Adjuntamos la respuesta formal a tu solicitud ' . esc($request['radicado']) . '.</p><p>Este correo contiene informacion confidencial dirigida exclusivamente al titular.</p>';
        $notificationModel = new DpNotificacionModel();
        $notificationId = (int) $notificationModel->insert(['cliente_id' => $cliente['id'], 'solicitud_id' => $request['id'],
            'tipo' => 'respuesta_solicitud', 'destinatario' => $request['titular_email'], 'asunto' => $subject,
            'plantilla' => 'privacy_response', 'contenido_hash' => hash('sha256', $html), 'estado' => 'pendiente', 'intentos' => 1], true);
        $result = (new EmailService())->sendPrivacyMessage($request['titular_email'], $subject, $html, WRITEPATH . $path, (int) $cliente['id']);
        $notificationModel->update($notificationId, ['proveedor_id' => $result['message_id'], 'estado' => $result['success'] ? 'aceptado' : 'fallido',
            'ultimo_error' => $result['error'], 'enviado_at' => $result['success'] ? date('Y-m-d H:i:s') : null]);
        return $result['success'];
    }

    private function notifyRequestStatus(array $cliente, array $request, string $action): void
    {
        [$subject, $message] = match ($action) {
            'verify' => ['Solicitud completa - ' . $request['radicado'], 'La identidad fue verificada. La fecha limite inicial de respuesta es ' . $request['vence_at'] . '.'],
            'request_info' => ['Subsanacion requerida - ' . $request['radicado'], 'La solicitud requiere informacion para validar la identidad o completar el reclamo. Contacta el canal de privacidad. Si no se subsana dentro de dos meses, se entendera desistida.'],
            'extend' => ['Prorroga informada - ' . $request['radicado'], 'No fue posible completar la respuesta dentro del termino inicial. La nueva fecha maxima es ' . $request['prorroga_hasta'] . '.'],
            'transfer' => ['Reclamo trasladado - ' . $request['radicado'], 'El reclamo fue trasladado a ' . $request['traslado_destinatario'] . '. Se conserva evidencia del destinatario y de la fecha del traslado.'],
            default => ['', ''],
        };
        $html = '<p>Hola ' . esc($request['titular_nombre']) . ',</p><p>' . esc($message) . '</p><p>Radicado: <strong>' . esc($request['radicado']) . '</strong>.</p>';
        $model = new DpNotificacionModel();
        $id = (int) $model->insert(['cliente_id' => $cliente['id'], 'solicitud_id' => $request['id'], 'tipo' => $action,
            'destinatario' => $request['titular_email'], 'asunto' => $subject, 'plantilla' => 'privacy_' . $action,
            'contenido_hash' => hash('sha256', $html), 'estado' => 'pendiente', 'intentos' => 1], true);
        $result = (new EmailService())->sendPrivacyMessage($request['titular_email'], $subject, $html, null, (int) $cliente['id']);
        $model->update($id, ['proveedor_id' => $result['message_id'], 'estado' => $result['success'] ? 'aceptado' : 'fallido',
            'ultimo_error' => $result['error'], 'enviado_at' => $result['success'] ? date('Y-m-d H:i:s') : null]);
    }

    private function requestActions(int $requestId): array
    {
        return $this->vault()->decryptRows('dp_solicitud_bases', db_connect()->table('dp_solicitud_bases sb')->select('sb.*, b.nombre AS base_nombre')
            ->join('dp_bases_datos b', 'b.id=sb.base_id', 'left')->where('sb.solicitud_id', $requestId)->orderBy('b.nombre')->get()->getResultArray());
    }

    private function requestThirdPartyActions(int $requestId): array
    {
        $rows = $this->vault()->decryptRows('dp_solicitud_terceros', db_connect()->table('dp_solicitud_terceros st')->select('st.*, t.nombre AS tercero_nombre')
            ->join('dp_terceros t', 't.id=st.tercero_id', 'left')->where('st.solicitud_id', $requestId)->orderBy('t.nombre')->get()->getResultArray());
        foreach ($rows as &$row) {
            $row['tercero_nombre'] = $this->vault()->decryptValue('dp_terceros', 'nombre', $row['tercero_nombre']);
        }
        return $rows;
    }

    private function requestEvent(int $requestId, string $type, array $detail): void
    {
        $occurredAt = date('Y-m-d H:i:s');
        $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = [
            'solicitud_id' => $requestId, 'tipo' => $type, 'detalle_json' => $payload,
            'evento_hash' => hash('sha256', $requestId . '|' . $type . '|' . $occurredAt . '|' . $payload),
            'usuario_id' => session()->get('user_id') ?: null, 'ocurrido_at' => $occurredAt, 'created_at' => $occurredAt,
        ];
        db_connect()->table('dp_solicitud_eventos')->insert($this->vault()->encryptRow('dp_solicitud_eventos', $row));
    }

    private function incidentEvent(int $incidentId, string $type, array $detail): void
    {
        $occurredAt = date('Y-m-d H:i:s');
        $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = [
            'incidente_id' => $incidentId, 'tipo' => $type, 'detalle_json' => $payload,
            'evento_hash' => hash('sha256', $incidentId . '|' . $type . '|' . $occurredAt . '|' . $payload),
            'usuario_id' => session()->get('user_id') ?: null, 'ocurrido_at' => $occurredAt, 'created_at' => $occurredAt,
        ];
        db_connect()->table('dp_incidente_eventos')->insert($this->vault()->encryptRow('dp_incidente_eventos', $row));
    }

    private function confidentialityEvent(int $agreementId, string $type, array $detail): void
    {
        $occurredAt = date('Y-m-d H:i:s');
        $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = ['compromiso_id' => $agreementId, 'tipo' => $type,
            'detalle_json' => $payload, 'evento_hash' => hash('sha256', $agreementId . '|' . $type . '|' . $occurredAt . '|' . $payload),
            'usuario_id' => session()->get('user_id') ?: null, 'ocurrido_at' => $occurredAt, 'created_at' => $occurredAt];
        db_connect()->table('dp_compromiso_eventos')->insert($this->vault()->encryptRow('dp_compromiso_eventos', $row));
    }

    private function processorEvent(int $agreementId, string $type, array $detail): void
    {
        $occurredAt = date('Y-m-d H:i:s'); $payload = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = ['acuerdo_id' => $agreementId, 'tipo' => $type,
            'detalle_json' => $payload, 'evento_hash' => hash('sha256', $agreementId . '|' . $type . '|' . $occurredAt . '|' . $payload),
            'usuario_id' => session()->get('user_id') ?: null, 'ocurrido_at' => $occurredAt, 'created_at' => $occurredAt];
        db_connect()->table('dp_acuerdo_encargado_eventos')->insert($this->vault()->encryptRow('dp_acuerdo_encargado_eventos', $row));
    }

    private function processorSignature(string $data): ?string
    {
        if (! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#', $data, $match)) { return null; }
        $binary = base64_decode($match[2], true);
        return $binary !== false && strlen($binary) <= 1024 * 1024 ? $data : null;
    }

    private function cliente(?int $clienteId): ?array
    {
        $role = (string) session()->get('rol');
        if (in_array($role, ['superadmin', 'admin'], true)) {
            return $clienteId ? (new ClienteModel())->find($clienteId) : null;
        }
        $ownId = (int) session()->get('cliente_id');
        if ($ownId < 1 || ($clienteId !== null && $clienteId !== $ownId)) {
            return null;
        }
        return (new ClienteModel())->find($ownId);
    }

    private function program(array $cliente): array
    {
        return (new DpProgramaModel())->where('cliente_id', $cliente['id'])->first()
            ?? (new PrivacyProgramService())->initialize($cliente);
    }

    private function denied()
    {
        return redirect()->to('/dashboard')->with('error', 'No tienes acceso a ese programa de datos personales.');
    }

    private function privacyModuleUrl(array $cliente): string
    {
        if (in_array((string) session()->get('rol'), ['superadmin', 'admin'], true)) {
            return base_url('admin/clientes/' . $cliente['id'] . '/datos-personales');
        }

        return base_url('datos-personales');
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));
        return $value === '' ? null : $value;
    }

    private function listPost(string $key): array
    {
        $raw = preg_split('/[,\r\n]+/', (string) $this->request->getPost($key)) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $raw))));
    }

    private function radicado(int $clienteId): string
    {
        return 'DP-' . date('Ymd') . '-' . $clienteId . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function identifierHash(string $identifier): string
    {
        return (new PrivacyPii())->blindIndex($identifier, 'documento');
    }

    private function sanitizeLegalHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        return strip_tags($html, '<article><header><footer><h1><h2><h3><p><strong><em><ul><ol><li><table><thead><tbody><tr><th><td><br>');
    }

    private function purposeContentHash(array $purpose): string
    {
        return hash('sha256', json_encode([
            'descripcion' => $purpose['descripcion'], 'base_juridica_tipo' => $purpose['base_juridica_tipo'],
            'base_juridica_detalle' => $purpose['base_juridica_detalle'], 'categorias' => $purpose['categorias_datos_json'],
            'opcional' => (int) $purpose['es_opcional'], 'consecuencia' => $purpose['consecuencia_negativa'],
            'explicito' => (int) $purpose['requiere_consentimiento_explicito'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function documentReadinessProblems(array $cliente, array $document): array
    {
        $program = $this->program($cliente);
        $config = json_decode((string) ($program['config_json'] ?? '{}'), true) ?: [];
        $required = [
            'responsable_documento' => $program['responsable_documento'] ?? null,
            'responsable_direccion' => $program['responsable_direccion'] ?? null,
            'responsable_ciudad' => $program['responsable_ciudad'] ?? null,
            'canal_telefono' => $program['canal_telefono'] ?? null,
            'area_responsable' => $config['area_responsable'] ?? null,
            'horario_atencion' => $config['horario_atencion'] ?? null,
            'organo_aprobacion' => $config['organo_aprobacion'] ?? null,
            'fecha_aprobacion' => $config['fecha_aprobacion'] ?? null,
            'fecha_vigencia' => $config['fecha_vigencia'] ?? null,
            'medio_publicacion' => $config['medio_publicacion'] ?? null,
        ];
        $missing = array_keys(array_filter($required, static fn ($value) => trim((string) $value) === ''));
        $problems = $missing ? ['Faltan campos del programa: ' . implode(', ', $missing) . '.'] : [];
        if (! empty($config['usa_videovigilancia']) && ! empty($config['graba_videovigilancia']) && empty($config['plazo_grabaciones_dias'])) {
            $problems[] = 'Defina el plazo de conservacion de grabaciones.';
        }
        if (! empty($config['usa_biometria']) || ! empty($config['video_identificacion_biometrica'])) {
            foreach (['tipo_biometria', 'alternativa_biometrica', 'finalidad_biometria', 'plazo_supresion_biometria_dias'] as $field) {
                if (empty($config[$field])) {
                    $problems[] = 'Complete ' . $field . ' para el tratamiento biometrico.';
                }
            }
        }
        if (! empty($config['transmision_internacional']) && empty($config['paises_transmision'])) {
            $problems[] = 'Indique los paises de transmision internacional.';
        }
        if (! empty($config['transferencia_internacional']) && empty($config['paises_transferencia'])) {
            $problems[] = 'Indique los paises de transferencia internacional.';
        }
        if (! empty($config['transferencia_internacional'])) {
            foreach (['receptor_exterior', 'garantia_transferencia'] as $field) {
                if (empty($config[$field])) {
                    $problems[] = 'Complete ' . $field . ' para la transferencia internacional.';
                }
            }
            $guarantee = (string) ($config['garantia_transferencia'] ?? '');
            if (! in_array($guarantee, ['nivel_adecuado', 'declaracion_conformidad', 'excepcion_articulo_26', 'autorizacion_expresa'], true)) {
                $problems[] = 'Seleccione una garantia internacional de la lista juridica controlada.';
            }
            if (in_array($guarantee, ['declaracion_conformidad', 'excepcion_articulo_26'], true) && empty($config['garantia_transferencia_detalle'])) {
                $problems[] = 'Identifique la declaracion de conformidad o excepcion del articulo 26 aplicable.';
            }
            if ((! empty($config['transferencia_requiere_autorizacion'])) !== ($guarantee === 'autorizacion_expresa')) {
                $problems[] = 'La marca de autorizacion expresa no coincide con la garantia internacional seleccionada.';
            }
        }
        if (! empty($config['transferencia_nacional']) && (empty($config['responsable_destinatario']) || empty($config['finalidad_transferencia']))) {
            $problems[] = 'Identifique el Responsable destinatario y la finalidad de la transferencia nacional.';
        }
        if (! empty($config['usa_videovigilancia']) && (int) ($config['plazo_grabaciones_dias'] ?? 0) > 30 && empty($config['justificacion_retencion_video'])) {
            $problems[] = 'Justifique la conservacion de videovigilancia superior a 30 dias.';
        }
        if ($document['tipo'] === 'procedimiento') {
            $html = (string) $document['contenido_html'];
            foreach (['Rectificacion y actualizacion', 'quince (15) dias habiles', 'ocho (8) dias habiles', 'dos (2) meses', 'Expediente y seguridad', 'Respaldos y prohibicion de reactivacion'] as $requiredText) {
                if (! str_contains($html, $requiredText)) {
                    $problems[] = 'El procedimiento no contiene la regla obligatoria: ' . $requiredText . '.';
                }
            }
            if ((int) ($config['request_file_years'] ?? 0) < 5 || (int) ($config['backup_rotation_days'] ?? 0) < 1 || (int) ($config['backup_rotation_days'] ?? 0) > 365) {
                $problems[] = 'Configure expediente por minimo cinco anos y rotacion de respaldos entre 1 y 365 dias.';
            }
            if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) ($config['request_cutoff_time'] ?? ''))) {
                $problems[] = 'Configure una hora de corte valida para el computo de solicitudes.';
            }
            if (trim((string) env('email.SMTPPass')) !== '' && empty($config['sendgrid_transmission_confirmed'])) {
                $problems[] = 'Documente a SendGrid como Encargado internacional y la garantia contractual antes de aprobar.';
            }
            $db = db_connect();
            foreach (['politica', 'aviso', 'autorizacion'] as $dependencyType) {
                if (! $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', $dependencyType)->where('estado', 'publicado')->countAllResults()) {
                    $problems[] = 'Debe publicar ' . $dependencyType . ' antes de aprobar el procedimiento.';
                }
            }
        }
        if ($document['tipo'] === 'seguridad') {
            $html = (string) $document['contenido_html'];
            foreach (range(1, 16) as $chapter) {
                if (! preg_match('/<h2>' . $chapter . '\./', $html)) {
                    $problems[] = 'El manual no contiene el capitulo obligatorio ' . $chapter . '.';
                }
            }
            if (! str_contains($html, 'quince (15) dias habiles') || ! str_contains($html, 'Responsables') || ! str_contains($html, 'no existe')) {
                $problems[] = 'El protocolo debe conservar la regla fija de reporte SIC en quince dias para obligados y no obligados al RNBD.';
            }
            if (preg_match('/\[[A-Z_]+(?:[:][^\]]+)?\]|\[\[SI_CONFIG|\[\[FIN_SI/u', $html)) {
                $problems[] = 'La instancia contiene variables o bloques condicionales sin resolver.';
            }
            $securityRequired = ['security_acta', 'security_administrador', 'security_opd_designacion', 'security_opd_reporte',
                'security_proveedor_ti', 'security_sedes', 'security_archivo_ubicacion', 'security_archivo_custodio',
                'security_canal_incidentes', 'security_gestor_secretos', 'security_contenedor_destruccion'];
            $missingSecurity = array_values(array_filter($securityRequired, static fn ($field) => trim((string) ($config[$field] ?? '')) === ''));
            if ($missingSecurity) {
                $problems[] = 'Faltan variables operativas del manual: ' . implode(', ', $missingSecurity) . '.';
            }
            if (! empty($config['security_nube']) && (empty($config['security_nube_proveedor']) || empty($config['security_nube_pais']))) {
                $problems[] = 'Identifique proveedor y pais de nube.';
            }
            if (! empty($config['usa_videovigilancia']) && (empty($config['security_cctv_ubicacion']) || empty($config['security_cctv_roles']))) {
                $problems[] = 'Complete ubicacion y roles nominales de videovigilancia.';
            }
            $db = db_connect();
            $documentVariables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            $boundDependencies = $documentVariables['document_dependencies'] ?? [];
            foreach (['politica', 'aviso', 'autorizacion', 'procedimiento'] as $dependencyType) {
                $dependency = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', $dependencyType)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
                if (! $dependency) {
                    $problems[] = 'Debe publicar ' . $dependencyType . ' antes de aprobar el manual de seguridad.';
                } elseif (! isset($boundDependencies[$dependencyType])
                    || (int) $boundDependencies[$dependencyType]['version'] !== (int) $dependency['version']
                    || ! hash_equals((string) ($boundDependencies[$dependencyType]['hash'] ?? ''), (string) $dependency['hash_sha256'])) {
                    $problems[] = 'El manual no esta vinculado a la version publicada vigente de ' . $dependencyType . '; regenere la instancia.';
                }
            }
            $bases = $db->table('dp_bases_datos')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
            if ($bases === []) {
                $problems[] = 'El manual requiere al menos una base de datos aprobada.';
            }
            foreach ($bases as $base) {
                if (empty($base['responsable_interno']) || empty($base['soportes_ubicacion']) || (empty($base['retencion_meses']) && empty($base['criterio_eliminacion'])) || empty($base['revisado_at'])) {
                    $problems[] = 'Complete responsable, soportes/ubicacion, conservacion y revision de la base ' . $base['nombre'] . '.';
                }
                if (! empty($base['datos_biometricos']) && empty($config['usa_biometria']) && empty($config['video_identificacion_biometrica'])) {
                    $problems[] = 'La base ' . $base['nombre'] . ' marca biometria pero la configuracion la niega.';
                }
            }
            $roles = array_column($db->table('dp_asignaciones_seguridad')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray(), 'rol');
            foreach (['organo', 'administrador', 'opd', 'ti'] as $role) {
                if (! in_array($role, $roles, true)) { $problems[] = 'Asigne el rol RACI obligatorio: ' . $role . '.'; }
            }
            $opd = $db->table('dp_asignaciones_seguridad')->where('cliente_id', $cliente['id'])->where('rol', 'opd')->where('activo', 1)->get()->getRowArray();
            if ($opd && empty($opd['acto_designacion'])) { $problems[] = 'El Oficial requiere acto de designacion identificable.'; }
            $thirdParties = $db->table('dp_terceros')->where('cliente_id', $cliente['id'])->where('activo', 1)->get()->getResultArray();
            foreach ($thirdParties as $third) {
                if (empty($third['contrato_vigente']) || empty($third['contrato_fecha']) || empty($third['contrato_evidencia']) || empty($third['evaluado_at']) || (! empty($third['contrato_vence']) && $third['contrato_vence'] < date('Y-m-d'))) {
                    $problems[] = 'El Encargado ' . $third['nombre'] . ' no tiene contrato vigente y evaluacion demostrable.';
                }
            }
            foreach ([[trim((string) env('email.SMTPPass')) !== '', 'SendGrid'], [trim((string) env('openai.apiKey')) !== '', 'OpenAI']] as [$enabled, $provider]) {
                if ($enabled && ! array_filter($thirdParties, static fn ($third) => stripos((string) $third['nombre'], $provider) !== false)) {
                    $problems[] = 'Registre a ' . $provider . ' como Encargado con contrato y evaluacion vigente.';
                }
            }
            foreach (['matriz_riesgos', 'revision_accesos', 'prueba_restauracion', 'capacitacion', 'mfa_roles_criticos', 'segregacion_clientes'] as $controlType) {
                $control = $db->table('dp_controles_seguridad')->where('cliente_id', $cliente['id'])->where('tipo', $controlType)->orderBy('ejecutado_at', 'DESC')->get()->getRowArray();
                if (! $control || $control['resultado'] !== 'conforme' || (! empty($control['vence_at']) && $control['vence_at'] < date('Y-m-d'))) {
                    $problems[] = 'Registre evidencia vigente y conforme del control: ' . $controlType . '.';
                }
            }
            if (trim((string) env('openai.apiKey')) !== '') {
                $aiTest = $db->table('dp_controles_seguridad')->where('cliente_id', $cliente['id'])->where('tipo', 'prueba_filtro_ia')->orderBy('ejecutado_at', 'DESC')->get()->getRowArray();
                if (! $aiTest || $aiTest['resultado'] !== 'conforme' || (! empty($aiTest['vence_at']) && $aiTest['vence_at'] < date('Y-m-d'))) {
                    $problems[] = 'La integracion OpenAI requiere prueba vigente y conforme del filtro anti-identidades.';
                }
            }
            $pendingUsers = $db->table('usuarios u')->select('u.id')->join('dp_usuario_privacidad up', 'up.usuario_id=u.id AND up.cliente_id=u.cliente_id', 'left')
                ->where('u.cliente_id', $cliente['id'])->where('u.activo', 1)->where('u.deleted_at', null)
                ->where('up.confidencialidad_at', null)->countAllResults();
            if ($pendingUsers > 0) {
                $problems[] = 'Hay ' . $pendingUsers . ' usuarios activos sin compromiso individual registrado.';
            }
        }
        if ($document['tipo'] === 'confidencialidad') {
            $html = (string) $document['contenido_html'];
            foreach (['Identificacion y alcance cerrado', 'Naturaleza y clasificacion', 'Uso autorizado y prohibiciones',
                'Seguridad e incidentes', 'Cambio, recertificacion y baja', 'Subsistencia diferenciada',
                'Consecuencias y debido proceso', 'Instancia y evidencia electronica'] as $requiredHeading) {
                if (! str_contains($html, $requiredHeading)) {
                    $problems[] = 'El Documento 6 no contiene la seccion obligatoria: ' . $requiredHeading . '.';
                }
            }
            if (preg_match('/\[[A-Z_]+(?:[:][^\]]+)?\]|\[\[SI_|\[\[FIN_SI|_{3,}/u', $html)) {
                $problems[] = 'El Documento 6 maestro contiene variables, condicionales o blancos sin resolver.';
            }
            foreach (['No se firma esta plantilla', 'autorizador distinto del firmante', 'reserva sobre datos personales es indefinida',
                'codigo al correo', 'hash SHA-256', 'eventos son de solo adicion'] as $requiredText) {
                if (! str_contains($html, $requiredText)) {
                    $problems[] = 'El Documento 6 omite la regla probatoria obligatoria: ' . $requiredText . '.';
                }
            }
            $db = db_connect();
            $variables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            $boundDependencies = $variables['document_dependencies'] ?? [];
            if ((int) ($variables['confidentiality_schema_version'] ?? 0) !== 2) {
                $problems[] = 'Regenera el Documento 6 con el esquema individual versionado vigente.';
            }
            foreach (['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad'] as $dependencyType) {
                $dependency = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', $dependencyType)
                    ->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
                if (! $dependency) {
                    $problems[] = 'Debe publicar ' . $dependencyType . ' antes de aprobar el Documento 6.';
                } elseif (! isset($boundDependencies[$dependencyType])
                    || (int) ($boundDependencies[$dependencyType]['version'] ?? 0) !== (int) $dependency['version']
                    || ! hash_equals((string) ($boundDependencies[$dependencyType]['hash'] ?? ''), (string) $dependency['hash_sha256'])) {
                    $problems[] = 'El Documento 6 no esta vinculado a la version publicada vigente de ' . $dependencyType . '; regenere la instancia.';
                }
            }
        }
        if ($document['tipo'] === 'encargados') {
            $html = (string) $document['contenido_html'];
            foreach (range(1, 21) as $clause) {
                if (! preg_match('/<h2>' . $clause . '\./', $html)) { $problems[] = 'El Documento 7 no contiene la clausula maestra ' . $clause . '.'; }
            }
            foreach (['No se firma esta plantilla', 'Circular 002 de 2025 trata transferencia de tecnologia',
                'Circular 003 de 2025', 'lista de no resucitar', 'doble firma electronica'] as $requiredText) {
                if (! str_contains($html, $requiredText)) { $problems[] = 'El Documento 7 omite la regla obligatoria: ' . $requiredText . '.'; }
            }
            if (preg_match('/\[[A-Z_]+(?:[:][^\]]+)?\]|_{3,}|\[DETALLAR\]/u', $html)) { $problems[] = 'El maestro contiene variables o blancos firmables.'; }
            $variables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            if ((int) ($variables['processor_agreement_schema_version'] ?? 0) !== 2) { $problems[] = 'Regenera el Documento 7 con el esquema contractual vigente.'; }
            $bound = $variables['document_dependencies'] ?? []; $db = db_connect();
            foreach (['politica', 'aviso', 'autorizacion', 'procedimiento', 'seguridad', 'confidencialidad'] as $type) {
                $dependency = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', $type)->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
                if (! $dependency) { $problems[] = 'Debe publicar ' . $type . ' antes de aprobar el Documento 7.'; }
                elseif (! isset($bound[$type]) || (int) ($bound[$type]['version'] ?? 0) !== (int) $dependency['version'] || ! hash_equals((string) ($bound[$type]['hash'] ?? ''), $dependency['hash_sha256'])) {
                    $problems[] = 'El Documento 7 no esta vinculado a la version publicada vigente de ' . $type . '.';
                }
            }
        }
        if ($document['tipo'] === 'autorizacion') {
            $db = db_connect();
            foreach (['Responsable y alcance', 'Derechos del Titular', 'Revocatoria y supresion', 'Evidencia de la decision'] as $requiredHeading) {
                if (! str_contains((string) $document['contenido_html'], $requiredHeading)) {
                    $problems[] = 'La autorizacion no contiene la seccion obligatoria: ' . $requiredHeading . '.';
                }
            }
            if (preg_match('/Autorizo todas|Autorizo parcialmente|entre otros|y demas|fines conexos/ui', (string) $document['contenido_html'])) {
                $problems[] = 'La autorizacion contiene una decision global o una finalidad abierta prohibida.';
            }
            $policy = $db->table('dp_documentos')->where('cliente_id', $cliente['id'])->where('tipo', 'politica')->where('estado', 'publicado')->orderBy('version', 'DESC')->get()->getRowArray();
            if (! $policy) {
                $problems[] = 'Debe existir una Politica de Tratamiento publicada antes de aprobar la autorizacion.';
            }
            $purposes = $db->table('dp_finalidades f')->select('f.*, b.datos_sensibles, b.datos_menores')
                ->join('dp_bases_datos b', 'b.id=f.base_id')->where('f.cliente_id', $cliente['id'])->where('f.activo', 1)->get()->getResultArray();
            $sensitiveCounts = [];
            foreach ($db->table('dp_finalidad_datos_sensibles')->select('finalidad_id, COUNT(*) AS total')->where('cliente_id', $cliente['id'])->where('activo', 1)->groupBy('finalidad_id')->get()->getResultArray() as $count) {
                $sensitiveCounts[(int) $count['finalidad_id']] = (int) $count['total'];
            }
            foreach ($purposes as $purpose) {
                $categories = json_decode((string) ($purpose['categorias_datos_json'] ?? '[]'), true) ?: [];
                if ($categories === []) {
                    $problems[] = 'La finalidad ' . $purpose['id'] . ' no tiene categorias de datos especificas.';
                }
                if (($purpose['base_juridica_tipo'] ?? 'autorizacion') === 'excepcion_legal' && empty($purpose['base_juridica_detalle'])) {
                    $problems[] = 'La finalidad ' . $purpose['id'] . ' no tiene base legal documentada.';
                }
                if (! empty($purpose['es_opcional']) && empty($purpose['consecuencia_negativa'])) {
                    $problems[] = 'La finalidad opcional ' . $purpose['id'] . ' no informa la consecuencia concreta de negarse.';
                }
                if (preg_match('/\b(entre otros|y demas|fines conexos|cualquier finalidad)\b/ui', (string) $purpose['descripcion'])) {
                    $problems[] = 'La finalidad ' . $purpose['id'] . ' contiene una formula abierta o indeterminada.';
                }
                if (! empty($purpose['es_opcional']) && preg_match('/se vera obligado|perdera todos|no podremos garantizar su seguridad/ui', (string) $purpose['consecuencia_negativa'])) {
                    $problems[] = 'La consecuencia de la finalidad ' . $purpose['id'] . ' utiliza lenguaje disuasivo.';
                }
                if (($purpose['base_juridica_tipo'] ?? '') === 'excepcion_legal' && ! preg_match('/(articulo|art\.)\s*10.{0,40}ley\s*1581|ley\s*675|orden\s+(judicial|de\s+autoridad|administrativa)/ui', (string) $purpose['base_juridica_detalle'])) {
                    $problems[] = 'La base legal de la finalidad ' . $purpose['id'] . ' no pertenece a la lista admitida: articulo 10 Ley 1581, Ley 675 u orden de autoridad.';
                }
                if (preg_match('/salud|huella|rostro|facial|biometr|religion|politic|sindical|sexual|genetic/ui', implode(' ', $categories))) {
                    $problems[] = 'La finalidad ' . $purpose['id'] . ' incluye categorias sensibles en el bloque de datos comunes.';
                }
                if ((! empty($purpose['datos_sensibles']) || ! empty($purpose['requiere_consentimiento_explicito'])) && empty($sensitiveCounts[(int) $purpose['id']])) {
                    $problems[] = 'La finalidad sensible ' . $purpose['id'] . ' no desglosa cada dato y su finalidad exclusiva.';
                }
                if (! empty($purpose['datos_menores']) && preg_match('/mercadeo|marketing|perfilamiento|publicidad/ui', (string) $purpose['descripcion'])) {
                    $problems[] = 'La finalidad ' . $purpose['id'] . ' aplica mercadeo o perfilamiento a menores.';
                }
            }
            $documentVariables = json_decode((string) ($document['variables_json'] ?? '{}'), true) ?: [];
            $boundPurposes = $documentVariables['purpose_versions'] ?? [];
            if (count($boundPurposes) !== count($purposes)) {
                $problems[] = 'El inventario de finalidades cambio despues de generar esta version; regenere la autorizacion.';
            } else {
                foreach ($purposes as $purpose) {
                    $bound = $boundPurposes[(string) $purpose['id']] ?? [];
                    if ((int) ($bound['version'] ?? 0) !== (int) ($purpose['version'] ?? 1) || ! hash_equals((string) ($bound['hash'] ?? ''), (string) ($purpose['contenido_hash'] ?? ''))) {
                        $problems[] = 'La finalidad ' . $purpose['id'] . ' cambio despues de generar esta version; regenere la autorizacion.';
                    }
                }
            }
            if (empty($config['canal_entrega_copia'])) {
                $problems[] = 'Defina el canal de entrega de la copia aceptada.';
            }
            if (empty($config['encargados_publicos'])) {
                $problems[] = 'Declare los Encargados informados al Titular o indique expresamente que no existen.';
            }
            if (! empty($config['usa_videovigilancia']) && empty($config['zonas_vigiladas'])) {
                $problems[] = 'Identifique las zonas vigiladas.';
            }
        }
        if (preg_match('/Por completar|Por definir|\[INCLUIR|\[DEFINIR/i', (string) $document['contenido_html'])) {
            $problems[] = 'El documento conserva marcadores editoriales sin resolver; regenere la version despues de completar la configuracion.';
        }
        return $problems;
    }

    private function saveNoticeEvidence(int $clienteId): ?string
    {
        $file = $this->request->getFile('evidencia');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024 || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
            throw new \RuntimeException('La evidencia debe ser JPG, PNG, WebP o PDF y pesar maximo 5 MB.');
        }
        $dir = 'uploads/privacy/notices/cliente-' . $clienteId . '/' . date('Ymd');
        $absolute = WRITEPATH . $dir;
        if (! is_dir($absolute) && ! mkdir($absolute, 0775, true) && ! is_dir($absolute)) {
            throw new \RuntimeException('No fue posible crear el directorio de evidencias.');
        }
        $name = $file->getRandomName();
        $file->move($absolute, $name);
        return $dir . '/' . $name;
    }

    private function vault(): PrivacyVault
    {
        return new PrivacyVault();
    }

    private function pdfResponse(string $path, string $filename)
    {
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . str_replace('"', '', $filename) . '"')
            ->setBody((new PrivacyPdf())->contents($path));
    }
}
