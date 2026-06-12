<script>
(function() {
    document.querySelectorAll('[data-add]').forEach(function(button) {
        button.addEventListener('click', function() {
            var target = document.getElementById(button.getAttribute('data-add'));
            if (!target || !target.firstElementChild) return;

            var clone = target.firstElementChild.cloneNode(true);
            clone.querySelectorAll('input, select, textarea').forEach(function(field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                    return;
                }
                field.value = '';
            });
            target.appendChild(clone);
        });
    });
})();
</script>
