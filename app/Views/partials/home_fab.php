<a href="<?= base_url('dashboard') ?>" class="home-fab" title="Ir al inicio" aria-label="Ir al inicio">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 11.5 12 4l9 7.5"/>
        <path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/>
        <path d="M9.5 20v-5.5h5V20"/>
    </svg>
</a>
<style>
    .home-fab { position: fixed; right: 20px; bottom: 20px; width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #1f2937, #0f1623); color: #e3bd45; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 12px 26px rgba(15,22,35,.4), inset 0 0 0 1px rgba(201,162,39,.4); z-index: 1000; text-decoration: none; transition: transform .14s, box-shadow .14s; }
    .home-fab:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(15,22,35,.5), inset 0 0 0 1px rgba(201,162,39,.6); }
    .home-fab:active { transform: translateY(0); }
    .home-fab svg { width: 25px; height: 25px; }
    @media print { .home-fab { display: none; } }
</style>
