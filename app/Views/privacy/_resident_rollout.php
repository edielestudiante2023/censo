<?php
$portalUrl = base_url('privacidad/' . $programa['public_token']);
$portalQrUrl = base_url($basePath . '/portal/qr.svg');
$requiredPublications = [
    'politica' => 'Politica publicada',
    'aviso' => 'Aviso publicado',
    'autorizacion' => 'Autorizacion publicada',
];
$publishedDocumentTypes = [];
foreach ($documentos as $document) {
    if (($document['estado'] ?? '') === 'publicado') {
        $publishedDocumentTypes[$document['tipo']] = true;
    }
}
$portalReady = true;
foreach (array_keys($requiredPublications) as $requiredType) {
    if (empty($publishedDocumentTypes[$requiredType])) {
        $portalReady = false;
    }
}
?>
<style>
    .resident-rollout{margin-top:16px}.rollout-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px}.rollout-status{display:flex;flex-wrap:wrap;gap:7px;margin:12px 0 18px}.rollout-grid{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:24px;align-items:start}.rollout-steps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;border:1px solid #e1e6ed;border-radius:8px;overflow:hidden}.rollout-step{padding:16px;border-right:1px solid #e1e6ed}.rollout-step:last-child{border-right:0}.rollout-number{width:30px;height:30px;display:grid;place-items:center;border-radius:50%;background:#172235;color:#fff;font-weight:800;margin-bottom:10px}.rollout-step strong{display:block;margin-bottom:6px}.rollout-step p{font-size:.84rem;line-height:1.5;color:#566477;margin:0}.rollout-qr{text-align:center;border-left:1px solid #e1e6ed;padding-left:24px}.rollout-qr img{display:block;width:170px;height:170px;margin:0 auto 10px;background:#fff}.rollout-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}.copy-feedback{min-height:20px;margin-top:7px;font-size:.78rem;color:#176b42}.rollout-note{margin-top:14px;padding:12px 14px;background:#fff8df;border-left:4px solid #d5a928;color:#4d3c09;font-size:.84rem;line-height:1.5}@media(max-width:950px){.rollout-grid{grid-template-columns:1fr}.rollout-qr{border-left:0;border-top:1px solid #e1e6ed;padding:18px 0 0}.rollout-steps{grid-template-columns:1fr 1fr}.rollout-step:nth-child(2){border-right:0}.rollout-step:nth-child(-n+2){border-bottom:1px solid #e1e6ed}}@media(max-width:600px){.rollout-head{display:block}.rollout-steps{grid-template-columns:1fr}.rollout-step{border-right:0;border-bottom:1px solid #e1e6ed}.rollout-step:last-child{border-bottom:0}.rollout-actions .btn{width:100%;text-align:center}}
</style>
<section class="card resident-rollout" aria-labelledby="resident-rollout-title">
    <div class="rollout-head">
        <div>
            <span class="kicker">Puesta en marcha</span>
            <h3 id="resident-rollout-title" style="margin:5px 0 0">Socializacion y firma de residentes</h3>
        </div>
        <span class="badge <?= $portalReady ? 'badge-on' : 'badge-off' ?>"><?= $portalReady ? 'Listo para socializar' : 'Publicaciones pendientes' ?></span>
    </div>
    <div class="rollout-status">
        <?php foreach ($requiredPublications as $type => $label): $published = ! empty($publishedDocumentTypes[$type]); ?>
            <span class="badge <?= $published ? 'badge-on' : 'badge-off' ?>"><?= esc($label) ?>: <?= $published ? 'si' : 'no' ?></span>
        <?php endforeach; ?>
        <span class="badge <?= $avisoPublicaciones ? 'badge-on' : '' ?>">Evidencias de socializacion: <?= count($avisoPublicaciones) ?></span>
    </div>
    <div class="rollout-grid">
        <div>
            <div class="rollout-steps">
                <div class="rollout-step"><span class="rollout-number">1</span><strong>Publicar</strong><p>La administracion aprueba y publica Politica, Aviso y Autorizacion. Sin esos tres documentos el portal no permite firmar.</p></div>
                <div class="rollout-step"><span class="rollout-number">2</span><strong>Socializar</strong><p>Comparta este enlace o QR por correo, WhatsApp, cartelera, asamblea o circular. Registre cada canal en Documentos, dentro de las variantes del Aviso.</p></div>
                <div class="rollout-step"><span class="rollout-number">3</span><strong>Firmar</strong><p>El residente entra sin usuario, verifica su correo, decide finalidad por finalidad, revisa la instancia final y firma en pantalla.</p></div>
                <div class="rollout-step"><span class="rollout-number">4</span><strong>Verificar</strong><p>El sistema envia la constancia al correo. La administracion consulta la decision y su expediente en Titulares, y el envio en Trazabilidad.</p></div>
            </div>
            <div class="rollout-actions">
                <button type="button" class="btn btn-primary" data-copy-privacy-url="<?= esc($portalUrl) ?>">Copiar enlace para socializar</button>
                <a class="btn btn-muted" target="_blank" rel="noopener" href="<?= esc($portalUrl) ?>">Probar como residente</a>
                <a class="btn btn-muted" href="<?= esc($portalQrUrl) ?>" download="portal-datos-personales.svg">Descargar QR</a>
                <button type="button" class="btn btn-muted" data-pane-jump="documentos">Ir a publicar documentos</button>
            </div>
            <div class="copy-feedback" aria-live="polite"></div>
            <div class="rollout-note"><strong>Correo:</strong> el codigo de verificacion y la constancia requieren que SendGrid este registrado como Encargado y tenga un Acuerdo vigente con doble firma. Si la compuerta no esta lista, el sistema bloquea el envio.</div>
        </div>
        <div class="rollout-qr">
            <img src="<?= esc($portalQrUrl) ?>" alt="Codigo QR del portal de datos personales">
            <strong>QR unico de esta copropiedad</strong>
            <p class="mini muted">Puede incluirlo en la circular o cartelera. Siempre lleva al portal vigente.</p>
        </div>
    </div>
</section>
<script>
(function(){
    var copyButton=document.querySelector('[data-copy-privacy-url]');
    if(copyButton){copyButton.addEventListener('click',function(){
        var feedback=copyButton.closest('.resident-rollout').querySelector('.copy-feedback');
        navigator.clipboard.writeText(copyButton.dataset.copyPrivacyUrl).then(function(){feedback.textContent='Enlace copiado. Ya puede pegarlo en el canal de socializacion.'},function(){feedback.textContent='No fue posible copiar automaticamente. Seleccione el enlace del Portal del titular.'});
    })}
    document.querySelectorAll('[data-pane-jump]').forEach(function(button){button.addEventListener('click',function(){var target=document.querySelector('[data-pane="'+button.dataset.paneJump+'"]');if(target){target.click();window.scrollTo({top:0,behavior:'smooth'})}})});
})();
</script>
