<?php

namespace App\Libraries;

final class PrivacyConfidentialityService
{
    public const OPERATIONS = ['consultar', 'registrar', 'actualizar', 'exportar', 'suprimir', 'visualizar_cctv', 'operar_biometria'];
    public const LINK_TYPES = ['laboral', 'contratista', 'miembro_organo', 'temporal', 'tercero', 'proveedor_persona_natural'];
    public const OPERATIONAL_ROLES = ['administracion', 'consejo', 'comite', 'porteria', 'vigilancia', 'mantenimiento',
        'contabilidad', 'auditoria', 'soporte_ti', 'oficial_proteccion_datos'];

    /** @return array{html:string, hash:string} */
    public function build(array $d): array
    {
        if (! empty($d['flags']['biometric'])) {
            $d['flags']['sensitive'] = true;
        }
        $e = static fn ($value): string => esc((string) $value);
        $bases = $e(implode(', ', $d['bases']));
        $purposes = $e(implode('; ', $d['purposes']));
        $operations = $e(implode(', ', array_map(static fn ($value) => str_replace('_', ' ', $value), $d['operations'])));
        $conditional = '';
        if ($d['flags']['porteria']) {
            $conditional .= '<h2>16-A. Porteria y vigilancia</h2><p>No informare si una persona reside, trabaja, entra, sale o recibe correspondencia; registrare solo datos minimos; no retendre documentos sin instruccion; no divulgare rutinas ni ausencias; y entregare correspondencia solo al destinatario o autorizado.</p>';
        }
        if ($d['flags']['video']) {
            $conditional .= '<h2>16-B. Videovigilancia</h2><p>Solo operare CCTV si mi alcance lo permite; no grabare monitores ni entregare imagenes; remitire toda solicitud al procedimiento del Responsable; no hare seguimiento para fines ajenos a seguridad y reportare usos irregulares.</p>';
        }
        if ($d['flags']['biometric']) {
            $conditional .= '<h2>16-C. Biometria</h2><p>Reconozco que los biometricos son sensibles. No enrolare sin verificar autorizacion explicita; respetare la alternativa no biometrica; no copiare, exportare ni transmitire plantillas y reportare toda falla.</p>';
        }
        if ($d['flags']['sensitive']) {
            $conditional .= '<h2>16-D. Datos sensibles</h2><p>Los tratare solo para las finalidades y con los controles reforzados autorizados; no los comentare ni revelare; su entrega es facultativa salvo excepcion legal y sus incidentes tienen prioridad.</p>';
        }
        if ($d['flags']['minors']) {
            $conditional .= '<h2>16-E. Datos de menores</h2><p>Respetare su interes superior; verificare la representacion cuando corresponda; no publicare imagenes o datos y reportare inmediatamente cualquier exposicion.</p>';
        }
        if ($d['flags']['remote']) {
            $conditional .= '<h2>16-F. Trabajo remoto</h2><p>Accedere solo por medios autorizados; no usare equipos publicos o compartidos; evitare exposicion a terceros; no almacenare copias locales y las verificaciones BYOD se limitaran al perfil de trabajo.</p>';
        }
        if ($d['flags']['multi_tenant']) {
            $conditional .= '<h2>16-G. Varias copropiedades</h2><p>Mi alcance se limita a esta copropiedad; no trasladare, comparare ni reutilizare informacion entre clientes y cada copropiedad exige una instancia propia.</p>';
        }
        if ($d['flags']['exports']) {
            $conditional .= '<h2>16-H. Exportaciones</h2><p>Solo exportare dentro del alcance, registrare el motivo, conservare archivos en repositorios autorizados, los eliminare al cumplir la finalidad y no los remitire a destinatarios diferentes.</p>';
        }
        $body = '<article class="legal-document confidentiality-instance"><header><p><strong>' . $e($d['responsible']) . ' - NIT ' . $e($d['nit']) . '</strong></p><h1>Compromiso Individual de Confidencialidad y Uso Autorizado de Informacion</h1><p>Documento 6, version ' . $e($d['version']) . '. Instancia individual generada ' . $e($d['generated_at']) . '.</p></header>' .
            '<h2>1. Identificacion</h2><p><strong>Firmante:</strong> ' . $e($d['signer']) . ', ' . $e($d['document_type']) . ' ' . $e($d['document_number']) . '. <strong>Vinculo:</strong> ' . $e($d['link_type']) . '. <strong>Rol:</strong> ' . $e($d['role']) . '. <strong>Autorizador:</strong> ' . $e($d['authorizer']) . '. <strong>Vigencia:</strong> ' . $e($d['valid_from']) . ' a ' . $e($d['valid_until']) . '.</p><p>Documentos vinculados: Politica v' . $e($d['versions']['politica']) . ', Aviso v' . $e($d['versions']['aviso']) . ', Autorizacion v' . $e($d['versions']['autorizacion']) . ', Procedimiento v' . $e($d['versions']['procedimiento']) . ' y Manual de Seguridad v' . $e($d['versions']['seguridad']) . '.</p>' .
            '<h2>2. Naturaleza y objeto</h2><p>Este compromiso individual protege los datos de Titulares y otra informacion clasificada del Responsable. No es contrato de transmision con una persona juridica Encargada ni autorizacion para tratar mis propios datos.</p>' .
            '<h2>3. Clasificacion</h2><p>Distingo datos personales; sensibles; datos de menores; informacion publica, cuyo uso por cuenta del Responsable sigue limitado a finalidades autorizadas; y otra informacion no personal reservada por naturaleza o instruccion documentada. Esta ultima excluye lo publico, lo revelado legitimamente y lo exigido por autoridad.</p>' .
            '<h2>4. Alcance autorizado</h2><p><strong>Bases:</strong> ' . $bases . '.<br><strong>Finalidades:</strong> ' . $purposes . '.<br><strong>Operaciones:</strong> ' . $operations . '.<br><strong>Vigencia:</strong> ' . $e($d['valid_from']) . ' a ' . $e($d['valid_until']) . '.</p><p>Todo acceso fuera de este alcance esta prohibido aunque el sistema lo permita. Su cambio exige autorizacion previa y una nueva instancia; ninguna instruccion verbal la sustituye.</p>' .
            '<h2>5. Obligaciones</h2><p>Tratare datos solo dentro del alcance; guardare reserva; custodire soportes; reportare incidentes; cooperare con verificaciones limitadas y seguire instrucciones documentadas conformes a la ley.</p>' .
            '<h2>6. Prohibiciones</h2><p>No consultare por curiosidad; descargare o copiare fuera del alcance; capturare pantallas o fotografias; usare correos personales, mensajeria o USB no autorizados; tratare para fines propios; publicare o comentare datos; alterare registros; compartire credenciales; accedere a otra copropiedad; ni eludire o probare controles. Una falla se reporta sin explorarla.</p>' .
            '<h2>7. Seguridad operativa</h2><p>Usare cuenta individual, custodiare credenciales, MFA cuando corresponda, bloqueare pantalla, protegere papel, exportare solo si esta autorizado y usare dispositivos aprobados.</p>' .
            '<h2>8. Incidentes</h2><p>Reportare toda sospecha dentro de 24 horas por <strong>' . $e($d['incident_channel']) . '</strong>; preservare evidencia; no investigare ni remediare sin instruccion; y no contactare Titulares, SIC, medios o terceros. El reporte de buena fe no genera represalia.</p>' .
            '<h2>9. Instrucciones</h2><p>Seguire instrucciones documentadas y legales. Si considero una instruccion contraria a la ley, lo manifestare por el canal de incidentes antes de ejecutarla.</p>' .
            '<h2>10. Cambio, suspension y recertificacion</h2><p>Todo cambio de rol exige nueva instancia y cierre de esta. El acceso puede suspenderse con motivo registrado y se suspende si no se recertifica.</p>' .
            '<h2>11. Terminacion</h2><p>Al finalizar el vinculo, rol o vigencia cesare el acceso; devolvere soportes y eliminare copias bajo instruccion dentro de cinco dias habiles; y suscribire certificacion. Solo retendre por orden de autoridad informada al Responsable.</p>' .
            '<h2>12. Subsistencia separada</h2><p>La reserva sobre <strong>datos personales</strong> es indefinida y subsiste sin limite temporal. La reserva sobre <strong>otra informacion confidencial</strong> dura mientras conserve legitimamente ese caracter. Esto no limita derechos irrenunciables ni denuncias ante autoridades.</p>' .
            '<h2>13. Mis datos</h2><p>Este documento no autoriza el tratamiento de mis datos. Su identidad, rol y evidencia tecnica se tratan para gestionar y demostrar el compromiso conforme a la Politica; mis derechos se ejercen por el Documento 4.</p>' .
            '<h2>14. Verificacion</h2><p>El Responsable u Oficial puede revisar registros, cuentas, activos y puestos corporativos dejando acta. No accede a dispositivos, cuentas o espacios personales; en BYOD solo al perfil de trabajo autorizado.</p>' .
            '<h2>15. Consecuencias</h2><p>Segun el vinculo y con debido proceso, pueden proceder medidas del reglamento o contrato, suspension o revocacion de acceso, responsabilidad civil y acciones penales legalmente aplicables. No se crean multas privadas, sanciones automaticas ni renuncias de derechos.</p>' . $conditional .
            '<h2>17. Aceptacion y prueba</h2><p>Declaro que visualice completa esta instancia individual con variables resueltas y la acepto mediante firma electronica, codigo enviado a mi correo y acto expreso. El sistema conserva identidad, version, fecha del servidor, canal, IP y navegador con finalidad probatoria y me entrega copia.</p><p><strong>Firmante:</strong> ' . $e($d['signer']) . '. <strong>Rol:</strong> ' . $e($d['role']) . '. <strong>Vinculo:</strong> ' . $e($d['link_type']) . '. <strong>Autorizador:</strong> ' . $e($d['authorizer']) . '.</p><p>Canal de incidentes: ' . $e($d['incident_channel']) . '. Canal de derechos: ' . $e($d['privacy_email']) . ' | ' . $e($d['privacy_phone']) . '.</p></article>';
        $hash = hash('sha256', $body);
        return ['html' => $body . '<footer data-instance-seal="sha256"><p><strong>Hash SHA-256 de la instancia canonica:</strong> ' . $hash . '</p></footer>', 'hash' => $hash];
    }

    public function verify(string $html, string $expectedHash): bool
    {
        $canonical = preg_replace('#<footer data-instance-seal="sha256">.*?</footer>$#s', '', $html) ?? '';
        return hash_equals($expectedHash, hash('sha256', $canonical));
    }
}
