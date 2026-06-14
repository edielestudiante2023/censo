<?php $brand = $cliente['color_primario'] ?: '#1f2937'; ?>
<style>
    :root { --brand: <?= esc($brand) ?>; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #f1f3f6; color: #111827; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; padding: 18px; }
    .wrap { max-width: 980px; margin: 0 auto; }
    .top { background: var(--brand); color: #fff; border-radius: 16px 16px 0 0; padding: 22px 24px; display: flex; align-items: center; gap: 16px; }
    .top .logo { width: 54px; height: 54px; border-radius: 13px; background: #fff; object-fit: contain; padding: 5px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,.18); }
    .top h1 { margin: 0; font-size: 1.3rem; }
    .top p { margin: 5px 0 0; color: rgba(255,255,255,.92); font-size: .9rem; }
    form, .panel { background: #fff; border-radius: 0 0 16px 16px; box-shadow: 0 16px 40px rgba(16,22,35,.13); padding: 24px; }
    h2 { font-size: 1.02rem; margin: 24px 0 13px; padding-top: 18px; border-top: 1px solid #eceef1; color: var(--brand); }
    h2:first-of-type { margin-top: 4px; padding-top: 0; border-top: 0; }
    .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .full { grid-column: 1 / -1; }
    label { display: block; font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
    input, select, textarea { width: 100%; border: 1.5px solid #d6dae0; border-radius: 11px; padding: 11px 12px; font-size: .95rem; background: #fff; outline: none; transition: border-color .15s, box-shadow .15s; font-family: inherit; }
    input:focus, select:focus, textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(31,41,55,.12); }
    textarea { min-height: 92px; resize: vertical; }
    .repeat { display: grid; gap: 11px; }
    .row { border: 1px solid #e6e9ed; border-radius: 13px; padding: 13px; background: #fafbfc; }
    .row.grid { display: grid; }
    .btn { border: 0; border-radius: 11px; padding: 11px 16px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; justify-content: center; align-items: center; gap: 7px; transition: filter .12s, transform .12s; }
    .btn:hover { transform: translateY(-1px); }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-muted { background: #eaedf1; color: #111827; }
    .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
    .signature { border: 1.5px dashed #c7ccd4; border-radius: 13px; background: #fff; width: 100%; height: 190px; touch-action: none; display: block; }
    .hint { font-size: .76rem; color: #6b7280; margin-top: 5px; }
    .submit { width: 100%; margin-top: 24px; font-size: 1.02rem; padding: 14px; box-shadow: 0 10px 22px rgba(16,22,35,.18); }
    .alert { padding: 12px 14px; border-radius: 11px; font-size: .9rem; margin-bottom: 14px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    @media (max-width: 720px) {
        body { padding: 0; }
        .top, form, .panel { border-radius: 0; }
        .grid { grid-template-columns: 1fr; }
        .full { grid-column: auto; }
        .btn { width: 100%; }
    }
</style>
