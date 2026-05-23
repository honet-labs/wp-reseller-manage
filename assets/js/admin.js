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
    // When selecting master product reference, auto-fill the Seller dropdown and Duration field
    $('select[name="price_id"]').on('change', function() {
        var selectedOpt = $(this).find(':selected');
        var seller_id = selectedOpt.data('seller-id');
        var duration = selectedOpt.data('duration');

        if (seller_id) {
            $('select[name="seller_id"]').val(seller_id).trigger('change');
        } else {
            $('select[name="seller_id"]').val('').trigger('change');
        }

        if (duration !== undefined && duration !== null && duration !== '') {
            $('input[name="duration_days"]').val(duration).trigger('change');
        }
    });

    // Calculate and auto-fill Expiration Date based on Purchase Date + Duration
    function calculateExpiryDate() {
        var purchaseDateStr = $('input[name="purchase_date"]').val();
        var duration = parseInt($('input[name="duration_days"]').val());
        if (purchaseDateStr && !isNaN(duration) && duration >= 0) {
            purchaseDateStr = purchaseDateStr.replace(/\//g, '-');
            var parts = purchaseDateStr.split('-');
            if (parts.length === 3) {
                var year = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1; // 0-indexed
                var day = parseInt(parts[2], 10);
                var date = new Date(year, month, day);
                date.setDate(date.getDate() + duration);
                var y = date.getFullYear();
                var m = String(date.getMonth() + 1).padStart(2, '0');
                var d = String(date.getDate()).padStart(2, '0');
                $('input[name="expires_at"]').val(y + '-' + m + '-' + d);
            }
        }
    }

    $('input[name="purchase_date"], input[name="duration_days"]').on('input change', function() {
        calculateExpiryDate();
    });

    // Trigger on page load for existing edit data
    calculateExpiryDate();

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

    // Modal display logic for seller details
    $('.wrpm-view-seller-detail').on('click', function(e) {
        e.preventDefault();
        var name = $(this).data('name');
        var email = $(this).data('email') || '-';
        var phone = $(this).data('phone') || '-';
        var telegram = $(this).data('telegram') || '-';
        var whatsapp = $(this).data('whatsapp') || '-';

        $('#wrpmSellerName').text(name);
        $('#wrpmSellerEmail').text(email);
        $('#wrpmSellerPhone').text(phone);
        $('#wrpmSellerTelegram').text(telegram);
        $('#wrpmSellerWhatsapp').text(whatsapp);

        $('#wrpmSellerModal').css('display', 'flex');
    });

    $('.wrpm-seller-modal-close, .wrpm-seller-modal-close-btn').on('click', function() {
        $('#wrpmSellerModal').css('display', 'none');
    });

    // Modal display logic for customer details
    $('.wrpm-view-customer-detail').on('click', function(e) {
        e.preventDefault();
        var name = $(this).data('name');
        var email = $(this).data('email') || '-';
        var phone = $(this).data('phone') || '-';
        var telegram = $(this).data('telegram') || '-';
        var whatsapp = $(this).data('whatsapp') || '-';

        $('#wrpmCustomerName').text(name);
        $('#wrpmCustomerEmail').text(email);
        $('#wrpmCustomerPhone').text(phone);
        $('#wrpmCustomerTelegram').text(telegram);
        $('#wrpmCustomerWhatsapp').text(whatsapp);

        $('#wrpmCustomerModal').css('display', 'flex');
    });

    $('.wrpm-customer-modal-close, .wrpm-customer-modal-close-btn').on('click', function() {
        $('#wrpmCustomerModal').css('display', 'none');
    });

    // Modal display logic for payment attachment details
    $('.wrpm-view-payment-proof').on('click', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        $('#wrpmAttachmentImg').attr('src', url);
        $('#wrpmAttachmentDownloadBtn').attr('href', url);
        $('#wrpmAttachmentModal').css('display', 'flex');
    });

    $('.wrpm-attachment-modal-close, .wrpm-attachment-modal-close-btn').on('click', function() {
        $('#wrpmAttachmentModal').css('display', 'none');
    });

    // Close modals when clicking outside of them
    $(window).on('click', function(e) {
        if ($(e.target).is('#wrpmDetailModal')) {
            $('#wrpmDetailModal').css('display', 'none');
        }
        if ($(e.target).is('#wrpmSellerModal')) {
            $('#wrpmSellerModal').css('display', 'none');
        }
        if ($(e.target).is('#wrpmCustomerModal')) {
            $('#wrpmCustomerModal').css('display', 'none');
        }
        if ($(e.target).is('#wrpmAttachmentModal')) {
            $('#wrpmAttachmentModal').css('display', 'none');
        }
    });
});
