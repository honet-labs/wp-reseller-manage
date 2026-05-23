jQuery(document).ready(function($) {
    // Initialize Select2 on all searchable select elements
    if ($.fn.select2) {
        $('.wrpm-select2').select2({
            placeholder: '-- Pilih --',
            allowClear: true,
            width: 'resolve'
        });

        $('.wrpm-select2-tags').select2({
            placeholder: 'Pilih atau ketik tag baru...',
            tags: true,
            tokenSeparators: [','],
            width: 'resolve'
        });
    }

    // Dynamic field calculations (e.g. autofilling dates, prices, etc)
    // When selecting reseller product on active products, auto-fill price
    $('select[name="reseller_product_id"]').on('change', function() {
        var rp_id = $(this).val();
        if (rp_id) {
            // Find option elements if metadata exists or fallback
            // In a real system, you can fetch via REST or set data attributes.
        }
    });
});
