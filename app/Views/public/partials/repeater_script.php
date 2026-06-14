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

            var index = target.children.length - 1;
            clone.querySelectorAll('[data-indexed-name]').forEach(function(field) {
                field.name = field.getAttribute('data-indexed-name') + '[' + index + ']';
            });
            clone.querySelectorAll('[data-file-prefix]').forEach(function(field) {
                field.name = field.getAttribute('data-file-prefix') + '_' + index;
            });
        });
    });
})();
</script>
