<script>
(function() {
    var canvas = document.getElementById('signaturePad');
    var input = document.getElementById('firma_imagen');
    var clear = document.getElementById('clearSignature');
    var drawing = false;
    var hasInk = false;
    var ctx = canvas.getContext('2d');

    function resize() {
        var rect = canvas.getBoundingClientRect();
        var previous = hasInk ? canvas.toDataURL('image/png') : null;
        canvas.width = Math.floor(rect.width * window.devicePixelRatio);
        canvas.height = Math.floor(rect.height * window.devicePixelRatio);
        ctx.setTransform(window.devicePixelRatio, 0, 0, window.devicePixelRatio, 0, 0);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111827';
        if (previous) {
            var img = new Image();
            img.onload = function() { ctx.drawImage(img, 0, 0, rect.width, rect.height); };
            img.src = previous;
        }
    }

    function point(event) {
        var rect = canvas.getBoundingClientRect();
        var touch = event.touches ? event.touches[0] : event;
        return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
    }

    function start(event) {
        event.preventDefault();
        drawing = true;
        hasInk = true;
        var p = point(event);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function move(event) {
        if (!drawing) return;
        event.preventDefault();
        var p = point(event);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        input.value = canvas.toDataURL('image/png');
    }

    function end() {
        drawing = false;
        if (hasInk) input.value = canvas.toDataURL('image/png');
    }

    resize();
    window.addEventListener('resize', resize);
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);
    clear.addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        input.value = '';
        hasInk = false;
    });
})();
</script>
