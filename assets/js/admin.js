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

        $('.wrpm-select2-category').select2({
            placeholder: 'Pilih atau ketik kategori baru...',
            tags: true,
            allowClear: true,
            width: 'resolve'
        });
    }

    // Dynamic field calculations (e.g. autofilling dates, prices, etc)
    // When selecting master product reference, auto-fill the Seller dropdown
    $('select[name="price_id"]').on('change', function() {
        var seller_id = $(this).find(':selected').data('seller-id');
        if (seller_id) {
            $('select[name="seller_id"]').val(seller_id).trigger('change');
        } else {
            $('select[name="seller_id"]').val('').trigger('change');
        }
    });

    // Live table search filtering
    $('.wrpm-table-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $(this).closest('.wrpm-card-body').find('.wrpm-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Modal display logic for product details
    $('.wrpm-view-detail').on('click', function(e) {
        e.preventDefault();
        var name = $(this).data('name');
        var desc = $(this).data('description') || '<i>Tidak ada deskripsi.</i>';
        var notes = $(this).data('notes') || '<i>Tidak ada catatan tambahan.</i>';

        $('#wrpmModalTitle').text(name);
        $('#wrpmModalDescription').html(desc);
        $('#wrpmModalNotes').html(notes);

        $('#wrpmDetailModal').css('display', 'flex');
    });

    $('.wrpm-modal-close, .wrpm-modal-close-btn').on('click', function() {
        $('#wrpmDetailModal').css('display', 'none');
    });

    // Close modal when clicking outside of it
    $(window).on('click', function(e) {
        if ($(e.target).is('#wrpmDetailModal')) {
            $('#wrpmDetailModal').css('display', 'none');
        }
    });
});
