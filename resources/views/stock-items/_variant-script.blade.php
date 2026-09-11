@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('has_variants');
    const wrap = document.getElementById('variantsWrap');
    const rows = document.getElementById('variantRows');
    const addBtn = document.getElementById('addVariant');
    if (!toggle || !wrap || !rows) return;

    function rowCount() {
        return rows.querySelectorAll('.variant-row').length;
    }

    function reindex() {
        rows.querySelectorAll('.variant-row').forEach(function (row, i) {
            row.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/variants\[\d+]/, 'variants[' + i + ']');
            });
        });
    }

    toggle.addEventListener('change', function () {
        wrap.classList.toggle('d-none', !toggle.checked);
        if (toggle.checked && rowCount() === 0) addBtn.click();
    });

    addBtn.addEventListener('click', function () {
        const i = rowCount();
        const tr = document.createElement('tr');
        tr.className = 'variant-row';
        tr.innerHTML =
            '<td><input type="hidden" name="variants[' + i + '][id]" value="">' +
            '<input name="variants[' + i + '][name]" class="form-control form-control-sm" placeholder="e.g. Glass bottle"></td>' +
            '<td><input name="variants[' + i + '][size]" class="form-control form-control-sm" placeholder="10ml / 30ml"></td>' +
            '<td><input name="variants[' + i + '][price]" class="form-control form-control-sm text-end ams-amount-input" placeholder="0.00"></td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-variant">×</button></td>';
        rows.appendChild(tr);
    });

    rows.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-variant')) return;
        if (rowCount() === 1) {
            e.target.closest('tr').querySelectorAll('input').forEach(function (input) {
                if (input.type !== 'hidden') input.value = '';
                else input.value = '';
            });
            return;
        }
        e.target.closest('tr').remove();
        reindex();
    });
})();
</script>
@endpush
