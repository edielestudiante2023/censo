<style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #f3f4f6; color: #111827; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; padding: 18px; }
    .wrap { max-width: 980px; margin: 0 auto; }
    .top { background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; color: #fff; border-radius: 16px 16px 0 0; padding: 24px; }
    .top h1 { margin: 0; font-size: 1.35rem; }
    .top p { margin: 6px 0 0; color: rgba(255,255,255,.9); }
    form, .panel { background: #fff; border-radius: 0 0 16px 16px; box-shadow: 0 12px 36px rgba(0,0,0,.12); padding: 22px; }
    h2 { font-size: 1rem; margin: 22px 0 12px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
    h2:first-child { margin-top: 0; padding-top: 0; border-top: 0; }
    .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .full { grid-column: 1 / -1; }
    label { display: block; font-size: .82rem; font-weight: 800; color: #374151; margin-bottom: 6px; }
    input, select, textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 11px; font-size: .94rem; background: #fff; }
    textarea { min-height: 90px; resize: vertical; }
    .repeat { display: grid; gap: 10px; }
    .row { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; background: #f9fafb; }
    .btn { border: 0; border-radius: 10px; padding: 11px 14px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; justify-content: center; align-items: center; }
    .btn-primary { background: <?= esc($cliente['color_primario'] ?: '#1f2937') ?>; color: #fff; }
    .btn-muted { background: #e5e7eb; color: #111827; }
    .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
    .signature { border: 1px solid #d1d5db; border-radius: 12px; background: #fff; width: 100%; height: 190px; touch-action: none; display: block; }
    .submit { width: 100%; margin-top: 22px; font-size: 1rem; }
    @media (max-width: 720px) {
        body { padding: 0; }
        .top, form { border-radius: 0; }
        .grid { grid-template-columns: 1fr; }
        .full { grid-column: auto; }
        .btn { width: 100%; }
    }
</style>
