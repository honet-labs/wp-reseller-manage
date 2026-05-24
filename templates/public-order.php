<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$track_id = isset($_GET['track_order']) ? sanitize_text_field($_GET['track_order']) : '';

// Retrieve settings for company details
$settings = get_option('okj_settings_v1', []);
$company_name = !empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name');
$company_address = !empty($settings['pdf_company_address']) ? $settings['pdf_company_address'] : '';
$wa_confirm_no = !empty($settings['waha_sender_number']) ? $settings['waha_sender_number'] : '';

// Render the page
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Self-Service Order | <?php echo esc_html($company_name); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo includes_url('css/dashicons.min.css'); ?>">
    <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --primary-dark: #3730a3;
            --success: #16a34a;
            --success-light: #dcfce7;
            --warning: #ea580c;
            --warning-light: #ffedd5;
            --dark: #0f172a;
            --gray: #64748b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --glass: rgba(255, 255, 255, 0.85);
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.5;
            padding-bottom: 80px; /* Safe space for floating bar */
        }

        /* Layout Main Wrapper */
        .okj-client-wrap {
            max-width: 500px;
            margin: 0 auto;
            background: #ffffff;
            min-height: 100vh;
            box-shadow: 0 0 40px rgba(15, 23, 42, 0.08);
            position: relative;
        }

        /* Header Area styles */
        .okj-client-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: #ffffff;
            padding: 24px 20px 28px 20px;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .okj-client-header h1 {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .okj-client-header p {
            font-size: 0.825rem;
            opacity: 0.8;
        }

        /* Floating live badge */
        .okj-client-table-badge {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .okj-client-pulse {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            display: inline-block;
        }

        /* Filter Tab Bar Category Pills */
        .okj-client-categories {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 16px 20px;
            scrollbar-width: none;
        }
        .okj-client-categories::-webkit-scrollbar {
            display: none;
        }
        .okj-client-cat-pill {
            padding: 8px 16px;
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--gray);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .okj-client-cat-pill.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        /* Search input design */
        .okj-client-search-wrap {
            padding: 0 20px;
            margin-bottom: 16px;
        }
        .okj-client-search {
            position: relative;
            display: flex;
            align-items: center;
        }
        .okj-client-search span.dashicons {
            position: absolute;
            left: 12px;
            color: var(--gray);
            font-size: 18px;
        }
        .okj-client-search input {
            width: 100%;
            padding: 10px 12px 10px 38px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 13.5px;
            outline: none;
            transition: all 0.2s;
        }
        .okj-client-search input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        /* Catalogue Listing grid */
        .okj-client-catalog {
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 300px;
        }
        .okj-client-product-card {
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        .okj-client-product-card:hover {
            border-color: var(--primary-light);
        }
        .okj-client-p-info {
            flex: 1;
            padding-right: 12px;
        }
        .okj-client-p-name {
            font-weight: 700;
            font-size: 13.5px;
            color: var(--dark);
            margin-bottom: 4px;
        }
        .okj-client-p-meta {
            display: flex;
            gap: 6px;
            font-size: 10px;
            font-weight: 600;
            color: var(--gray);
        }
        .okj-client-p-meta span {
            background: var(--light);
            padding: 2px 6px;
            border-radius: 4px;
        }
        .okj-client-p-price {
            font-size: 14px;
            font-weight: 800;
            color: var(--primary);
            margin-top: 6px;
        }

        /* Floating Qty Control Buttons inside Listing */
        .okj-client-p-action {
            display: flex;
            align-items: center;
        }
        .okj-client-add-btn {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            font-weight: 700;
            font-size: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .okj-client-add-btn:hover {
            background: var(--primary-dark);
        }
        .okj-client-qty-wrap {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--primary);
            border-radius: 8px;
            overflow: hidden;
            height: 32px;
        }
        .okj-client-qty-btn {
            background: #ffffff;
            color: var(--primary);
            border: none;
            width: 28px;
            height: 100%;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
        }
        .okj-client-qty-val {
            width: 32px;
            text-align: center;
            font-weight: 700;
            font-size: 12.5px;
            border: none;
            outline: none;
        }

        /* Floating Cart Bottom Bar widget */
        .okj-client-cart-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 450px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            z-index: 200;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .okj-client-cart-bar:hover {
            transform: translateX(-50%) translateY(-2px);
        }
        .okj-client-cart-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .okj-client-cart-icon {
            position: relative;
            background: rgba(255,255,255,0.15);
            padding: 8px;
            border-radius: 10px;
        }
        .okj-client-cart-icon span.dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .okj-client-cart-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Cart & Checkout Slide Overlay Panel */
        .okj-client-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 500;
            display: none;
            justify-content: flex-end;
            flex-direction: column;
        }
        .okj-client-panel {
            background: #ffffff;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .okj-client-panel-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .okj-client-panel-header h2 {
            font-size: 1.15rem;
            font-weight: 800;
        }
        .okj-client-panel-close {
            background: var(--light);
            border: none;
            padding: 6px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--gray);
        }
        .okj-client-panel-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        /* Form Inputs checkout details */
        .okj-client-form-group {
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
        }
        .okj-client-label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .okj-client-input {
            padding: 10px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13.5px;
            outline: none;
            transition: all 0.2s;
            background: #ffffff;
        }
        .okj-client-input:focus {
            border-color: var(--primary);
        }

        /* Checkout summary */
        .okj-client-sum-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 8px;
        }
        .okj-client-sum-total {
            border-top: 1.5px dashed var(--border);
            padding-top: 10px;
            margin-top: 10px;
            font-size: 16px;
            font-weight: 800;
            color: var(--dark);
        }

        /* Order Status timelines tracker layout */
        .okj-status-wrap {
            padding: 30px 20px;
            text-align: center;
        }
        .okj-status-illustration {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .okj-status-illustration span.dashicons {
            font-size: 40px;
            width: 40px;
            height: 40px;
        }
        .okj-status-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .okj-status-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 24px;
        }

        /* Vertical tracking timelines */
        .okj-timeline {
            position: relative;
            max-width: 320px;
            margin: 0 auto 30px auto;
            text-align: left;
            padding-left: 30px;
        }
        .okj-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 6px;
            bottom: 6px;
            width: 3px;
            background: var(--border);
        }
        .okj-timeline-node {
            position: relative;
            margin-bottom: 24px;
        }
        .okj-timeline-node:last-child {
            margin-bottom: 0;
        }
        .okj-timeline-dot {
            position: absolute;
            left: -27px;
            top: 4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #ffffff;
            border: 3.5px solid var(--border);
            z-index: 10;
            transition: all 0.3s;
        }
        .okj-timeline-node.active .okj-timeline-dot {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }
        .okj-timeline-node.completed .okj-timeline-dot {
            border-color: var(--success);
            background: var(--success);
        }
        .okj-timeline-node.completed::after {
            content: '✓';
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            position: absolute;
            left: -22px;
            top: 3px;
            z-index: 20;
        }
        .okj-timeline-label {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--gray);
        }
        .okj-timeline-node.active .okj-timeline-label {
            color: var(--dark);
        }
        .okj-timeline-node.completed .okj-timeline-label {
            color: var(--success);
        }
        .okj-timeline-desc {
            font-size: 11px;
            color: var(--gray);
            margin-top: 2px;
        }

        /* Payment detail box visual instructions */
        .okj-payment-instruction-box {
            background: #f8fafc;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            margin-top: 20px;
            text-align: center;
        }
        .okj-payment-qr {
            width: 180px;
            height: 180px;
            margin: 12px auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #ffffff;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .okj-payment-qr img {
            max-width: 100%;
            max-height: 100%;
        }

        /* Elegant action buttons */
        .okj-btn-wide {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .okj-btn-primary {
            background: var(--primary);
            color: #ffffff !important;
        }
        .okj-btn-primary:hover {
            background: var(--primary-dark);
        }
        .okj-btn-success {
            background: var(--success);
            color: #ffffff !important;
        }
        .okj-btn-success:hover {
            background: #15803d;
        }
    </style>
</head>
<body>

<div class="okj-client-wrap">

    <?php if (empty($track_id)): ?>
        <!-- ================================================================= -->
        <!-- VIEW: PRODUCT CATALOGUE (DEFAULT SELF SERVICE VIEW)              -->
        <!-- ================================================================= -->
        
        <!-- Header -->
        <div class="okj-client-header">
            <h1><?php echo esc_html($company_name); ?></h1>
            <p><?php echo esc_html($company_address ?: 'Pemesanan Mandiri Digital POS'); ?></p>
            <div class="okj-client-table-badge">
                <span class="okj-client-pulse"></span>
                <span>Self-Service Scan QR</span>
            </div>
        </div>

        <!-- Categories Pill Filter -->
        <div class="okj-client-categories">
            <div class="okj-client-cat-pill active" data-cat="">Semua</div>
            <?php foreach ($categories as $cat): ?>
                <div class="okj-client-cat-pill" data-cat="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Real-time Product Search Bar -->
        <div class="okj-client-search-wrap">
            <div class="okj-client-search">
                <span class="dashicons dashicons-search"></span>
                <input type="text" id="okj-client-search-input" placeholder="Cari menu / produk..." />
            </div>
        </div>

        <!-- Dynamic Product Listing -->
        <div class="okj-client-catalog" id="okj-client-products">
            <div style="text-align: center; padding: 40px; color: var(--gray);">
                <div style="border: 2px solid var(--border); border-top: 2px solid var(--primary); border-radius: 50%; width: 24px; height: 24px; animation: publicSpin 0.8s linear infinite; margin: 0 auto 12px auto;"></div>
                <p>Memuat menu...</p>
            </div>
        </div>

        <!-- Floating Cart Widget Bar (shown when cart has items) -->
        <div class="okj-client-cart-bar" id="okj-floating-cart" style="display: none;">
            <div class="okj-client-cart-info">
                <div class="okj-client-cart-icon">
                    <span class="dashicons dashicons-cart"></span>
                    <span class="okj-client-cart-badge" id="okj-cart-count">0</span>
                </div>
                <div>
                    <p style="font-size: 11px; opacity: 0.8; font-weight: 500;">Keranjang Saya</p>
                    <p id="okj-cart-total-price" style="font-size: 14px; font-weight: 800;">Rp 0</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 4px; font-weight: 700; font-size: 13px;">
                Checkout <span class="dashicons dashicons-arrow-right-alt" style="font-size: 16px; margin-top: 2px;"></span>
            </div>
        </div>

        <!-- OVERLAY: Cart List & Checkout Panel -->
        <div class="okj-client-overlay" id="okj-cart-overlay">
            <div class="okj-client-panel">
                <div class="okj-client-panel-header">
                    <h2>Keranjang & Pembayaran</h2>
                    <button class="okj-client-panel-close" id="okj-close-cart-btn">
                        <span class="dashicons dashicons-no-alt" style="font-size: 18px; width: 18px; height: 18px;"></span>
                    </button>
                </div>

                <div class="okj-client-panel-body">
                    <!-- Form Customer Details -->
                    <div class="okj-client-form-group">
                        <label class="okj-client-label">Nama Pemesan <span class="okj-required" style="color:#ef4444;">*</span></label>
                        <input type="text" id="okj-cust-name" class="okj-client-input" placeholder="Masukkan nama Anda..." required />
                    </div>
                    <div class="okj-client-form-group">
                        <label class="okj-client-label">Nomor WhatsApp <span class="okj-required" style="color:#ef4444;">*</span></label>
                        <input type="text" id="okj-cust-wa" class="okj-client-input" placeholder="Contoh: 628123456789 (untuk notifikasi)" required />
                        <span style="font-size: 10px; color: var(--gray); margin-top: 4px;">Kami akan mengirimkan link update pelacakan pesanan Anda ke WhatsApp ini.</span>
                    </div>
                    <div class="okj-client-form-group">
                        <label class="okj-client-label">Catatan Pesanan / Meja</label>
                        <input type="text" id="okj-cust-notes" class="okj-client-input" placeholder="Contoh: Meja No. 5 / Sedikit pedas..." />
                    </div>

                    <!-- Payment Method options -->
                    <div class="okj-client-form-group">
                        <label class="okj-client-label">Metode Pembayaran</label>
                        <select id="okj-cust-pay-method" class="okj-client-input" style="cursor: pointer;">
                            <?php
                            $pos_enable_cash = isset($settings['pos_enable_cash']) ? (int)$settings['pos_enable_cash'] : 1;
                            $pos_enable_transfer = isset($settings['pos_enable_transfer']) ? (int)$settings['pos_enable_transfer'] : 1;
                            $pos_enable_qris = isset($settings['pos_enable_qris']) ? (int)$settings['pos_enable_qris'] : 1;

                            // Fallback to cash if all disabled
                            if (!$pos_enable_cash && !$pos_enable_transfer && !$pos_enable_qris) {
                                $pos_enable_cash = 1;
                            }
                            ?>
                            <?php if ($pos_enable_qris): ?>
                                <option value="qris">QRIS / E-Wallet (Bayar Instan)</option>
                            <?php endif; ?>
                            <?php if ($pos_enable_transfer): ?>
                                <option value="transfer">Transfer Bank Manual</option>
                            <?php endif; ?>
                            <?php if ($pos_enable_cash): ?>
                                <option value="cash">Bayar Kasir / Tunai</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div style="border-top: 1.5px solid var(--border); margin: 20px 0; padding-top: 16px;">
                        <h3 style="font-size: 12px; font-weight: 800; color: var(--gray); text-transform: uppercase; margin-bottom: 12px;">Rincian Pesanan</h3>
                        <div id="okj-panel-cart-items" style="max-height: 180px; overflow-y: auto;">
                            <!-- items list -->
                        </div>
                    </div>

                    <!-- Invoice calculation summaries -->
                    <div style="background: var(--light); padding: 12px; border-radius: 10px; margin-bottom: 20px;">
                        <div class="okj-client-sum-row">
                            <span>Subtotal</span>
                            <span id="okj-sum-subtotal">Rp 0</span>
                        </div>
                        <div class="okj-client-sum-row okj-client-sum-total">
                            <span>TOTAL BAYAR</span>
                            <span id="okj-sum-total">Rp 0</span>
                        </div>
                    </div>

                    <!-- Checkout button trigger -->
                    <button class="okj-btn-wide okj-btn-primary" id="okj-btn-submit-order" style="padding: 14px;">
                        <span class="dashicons dashicons-saved" style="margin-top: 2px;"></span> KIRIM & BUAT PESANAN
                    </button>
                </div>
            </div>
        </div>

    <?php else: 
        // Retrieve transaction details
        $t_transactions = OKJ_DB::get_table('pos_transactions');
        $tx = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t_transactions} WHERE id = %s", $track_id), ARRAY_A);
        
        if (!$tx):
    ?>
            <!-- Transaction Not Found Error View -->
            <div class="okj-status-wrap">
                <div class="okj-status-illustration" style="background:#fee2e2; color:#ef4444;">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <h2 class="okj-status-title">Pesanan Tidak Ditemukan</h2>
                <p class="okj-status-subtitle">ID pelacakan transaksi tidak valid atau pesanan telah dihapus.</p>
                <a href="<?php echo home_url('/?okj_order=1'); ?>" class="okj-btn-wide okj-btn-primary">Kembali Belanja</a>
            </div>
    <?php else: ?>
        <!-- ================================================================= -->
        <!-- VIEW: REAL-TIME CLIENT ORDER STATUS TRACKER                       -->
        <!-- ================================================================= -->
        <div class="okj-status-wrap">
            <div class="okj-status-illustration" id="okj-status-icon-box">
                <span class="dashicons dashicons-clock" id="okj-status-icon"></span>
            </div>
            
            <h2 class="okj-status-title" id="okj-status-tracker-title">Pesanan Menunggu</h2>
            <p class="okj-status-subtitle">Transaksi: <strong><?php echo esc_html($tx['transaction_no']); ?></strong></p>

            <!-- Animated Timelines Tracker -->
            <div class="okj-timeline">
                <!-- Received Node -->
                <div class="okj-timeline-node completed" id="node-received">
                    <div class="okj-timeline-dot"></div>
                    <div class="okj-timeline-label">Pesanan Diterima</div>
                    <div class="okj-timeline-desc">Pesanan Anda telah masuk di sistem operator kami.</div>
                </div>

                <!-- Processing Node -->
                <div class="okj-timeline-node" id="node-processing">
                    <div class="okj-timeline-dot"></div>
                    <div class="okj-timeline-label">Sedang Diproses</div>
                    <div class="okj-timeline-desc">Pesanan Anda sedang dipersiapkan oleh operator kasir.</div>
                </div>

                <!-- Completed Node -->
                <div class="okj-timeline-node" id="node-completed">
                    <div class="okj-timeline-dot"></div>
                    <div class="okj-timeline-label">Selesai & Lunas</div>
                    <div class="okj-timeline-desc">Pesanan selesai! Silakan mengambil produk Anda.</div>
                </div>
            </div>

            <!-- Payment instructions box for bank/qris -->
            <div class="okj-payment-instruction-box">
                <h3 style="font-size: 13.5px; font-weight:800; color:var(--dark);">Detail Pembayaran</h3>
                <p style="font-size:11.5px; color:var(--gray); margin-top:2px;">Metode Pembayaran: <strong><?php echo esc_html(strtoupper($tx['payment_method'])); ?></strong></p>
                <div style="font-size: 16px; font-weight:800; color:var(--primary); margin: 8px 0;">Rp <?php echo number_format($tx['total'], 0, ',', '.'); ?></div>
                
                <?php if ($tx['payment_method'] === 'qris'): ?>
                    <!-- Dynamically generate QR Code or show placeholder for QRIS -->
                    <div class="okj-payment-qr">
                        <!-- Free QR Code generator API to generate payment code or instructions -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode('PAY-' . $tx['transaction_no'] . '-TOTAL-' . $tx['total']); ?>" alt="QRIS OKJualin" />
                    </div>
                    <p style="font-size:10px; color:var(--gray);">Pindai QR di atas menggunakan aplikasi E-Wallet atau Bank Anda untuk membayar.</p>
                <?php elseif ($tx['payment_method'] === 'transfer'): ?>
                    <div style="background: #ffffff; border:1px solid var(--border); padding: 12px; border-radius: 8px; font-size: 12px; text-align: left; margin: 10px 0;">
                        <p style="color:var(--gray); margin-bottom:4px;">Silakan transfer ke rekening:</p>
                        <strong>Bank Mandiri: 123-45678-901</strong><br/>
                        <span>A.N. <?php echo esc_html($company_name); ?></span>
                    </div>
                <?php else: ?>
                    <p style="font-size:12px; color:var(--gray); padding: 10px 0;">Silakan lakukan pembayaran langsung ke Operator/Kasir di meja pelayanan.</p>
                <?php endif; ?>

                <?php if (!empty($wa_confirm_no)): 
                    $wa_text = urlencode("Halo, saya ingin mengonfirmasi pembayaran untuk pesanan mandiri No. Transaksi " . $tx['transaction_no'] . " sebesar Rp " . number_format($tx['total'], 0, ',', '.'));
                ?>
                    <a href="https://wa.me/<?php echo esc_attr($wa_confirm_no); ?>?text=<?php echo $wa_text; ?>" target="_blank" class="okj-btn-wide okj-btn-success" style="margin-top: 14px;">
                        <span class="dashicons dashicons-whatsapp"></span> Konfirmasi via WhatsApp
                    </a>
                <?php endif; ?>
            </div>

            <div style="margin-top: 24px; display: flex; gap: 8px;">
                <a href="<?php echo home_url('/?okj_order=1'); ?>" class="okj-btn-wide okj-btn-primary" style="background:#f1f5f9; color:var(--dark) !important; border:1.5px solid var(--border);">Pesan Lagi</a>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            const txId = "<?php echo esc_js($track_id); ?>";
            let pollTimer = null;

            // Function to check status via AJAX
            function checkStatus() {
                $.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: 'GET',
                    data: {
                        action: 'okj_public_check_order_status',
                        transaction_id: txId
                    },
                    success: function(response) {
                        if (response.success) {
                            const status = response.data.status;
                            updateTimelineUI(status);
                        }
                    }
                });
            }

            // Timeline states transition renderer
            function updateTimelineUI(status) {
                // Reset all states
                $('.okj-timeline-node').removeClass('active completed');

                if (status === 'pending') {
                    $('#node-received').addClass('active');
                    $('#okj-status-icon-box').css({'background':'#ffedd5', 'color':'#ea580c'});
                    $('#okj-status-icon').removeClass().addClass('dashicons dashicons-clock');
                    $('#okj-status-tracker-title').text('Pesanan Menunggu');
                } else if (status === 'processing') {
                    $('#node-received').addClass('completed');
                    $('#node-processing').addClass('active');
                    $('#okj-status-icon-box').css({'background':'#e0e7ff', 'color':'#4f46e5'});
                    $('#okj-status-icon').removeClass().addClass('dashicons dashicons-hourglass');
                    $('#okj-status-tracker-title').text('Sedang Diproses');
                } else if (status === 'paid' || status === 'completed') {
                    $('#node-received').addClass('completed');
                    $('#node-processing').addClass('completed');
                    $('#node-completed').addClass('completed');
                    $('#okj-status-icon-box').css({'background':'#dcfce7', 'color':'#16a34a'});
                    $('#okj-status-icon').removeClass().addClass('dashicons dashicons-yes-alt');
                    $('#okj-status-tracker-title').text('Pesanan Selesai!');
                    
                    // Stop polling once completed
                    clearInterval(pollTimer);
                }
            }

            // Init status update rendering
            updateTimelineUI("<?php echo esc_js($tx['payment_status']); ?>");

            // Setup polling timer every 5 seconds
            pollTimer = setInterval(checkStatus, 5000);
        });
        </script>
    <?php endif; ?>

    <?php endif; ?>

</div>

<!-- CSS spinners keyframes -->
<style>
@keyframes publicSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- ================================================================= -->
<!-- JAVASCRIPT LOGIC CLIENT SIDE                                      -->
<!-- ================================================================= -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Client catalogue logic
    let cart = [];
    let activeCategory = '';
    let searchQuery = '';

    // Fetch Products catalog
    function fetchProducts() {
        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: 'GET',
            data: {
                action: 'okj_public_get_products',
                search: searchQuery,
                category: activeCategory
            },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.data.length === 0) {
                        html = `
                            <div style="text-align:center; padding:40px; color:var(--gray);">
                                <span class="dashicons dashicons-search" style="font-size:32px; width:32px; height:32px; color:var(--border); margin-bottom:8px;"></span>
                                <p>Menu tidak tersedia.</p>
                            </div>
                        `;
                    } else {
                        response.data.forEach(function(p) {
                            let qtyInCart = getCartQty(p.id);
                            let actionHtml = '';

                            if (qtyInCart > 0) {
                                actionHtml = `
                                    <div class="okj-client-qty-wrap">
                                        <button class="okj-client-qty-btn client-qty-dec" data-id="${p.id}">-</button>
                                        <input type="text" class="okj-client-qty-val" value="${qtyInCart}" readonly />
                                        <button class="okj-client-qty-btn client-qty-inc" data-id="${p.id}">+</button>
                                    </div>
                                `;
                            } else {
                                actionHtml = `<button class="okj-client-add-btn client-add-btn" data-id="${p.id}" data-name="${p.name}" data-price="${p.sale_price}">+ Tambah</button>`;
                            }

                            html += `
                                <div class="okj-client-product-card">
                                    <div class="okj-client-p-info">
                                        <h3 class="okj-client-p-name">${p.name}</h3>
                                        <div class="okj-client-p-meta">
                                            <span>${p.category || 'Umum'}</span>
                                        </div>
                                        <p class="okj-client-p-price">Rp ${Number(p.sale_price).toLocaleString('id-ID')}</p>
                                    </div>
                                    <div class="okj-client-p-action">
                                        ${actionHtml}
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#okj-client-products').html(html);
                } else {
                    $('#okj-client-products').html('<p style="color:#ef4444; text-align:center; padding:40px;">Gagal memuat katalog.</p>');
                }
            },
            error: function() {
                $('#okj-client-products').html('<p style="color:#ef4444; text-align:center; padding:40px;">Terjadi kesalahan jaringan.</p>');
            }
        });
    }

    // Initialize list
    <?php if (empty($track_id)): ?>
    fetchProducts();
    <?php endif; ?>

    // Category pills click
    $('.okj-client-cat-pill').on('click', function() {
        $('.okj-client-cat-pill').removeClass('active');
        $(this).addClass('active');
        activeCategory = $(this).data('cat');
        fetchProducts();
    });

    // Search query debounced inputs
    let queryTimeout = null;
    $('#okj-client-search-input').on('input', function() {
        searchQuery = $(this).val().trim();
        clearTimeout(queryTimeout);
        queryTimeout = setTimeout(fetchProducts, 400);
    });

    // Get Cart Qty helper
    function getCartQty(id) {
        let item = cart.find(x => x.id === id);
        return item ? item.qty : 0;
    }

    // Add product to cart handler
    $(document).on('click', '.client-add-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const price = Number($(this).data('price'));

        cart.push({
            id: id,
            name: name,
            price: price,
            qty: 1
        });

        updateClientCartUI();
        fetchProducts(); // Refresh list to display quantity buttons
    });

    // Qty Increment handler
    $(document).on('click', '.client-qty-inc', function() {
        const id = $(this).data('id');
        let item = cart.find(x => x.id === id);
        if (item) {
            item.qty++;
            updateClientCartUI();
            fetchProducts();
        }
    });

    // Qty Decrement handler
    $(document).on('click', '.client-qty-dec', function() {
        const id = $(this).data('id');
        let idx = cart.findIndex(x => x.id === id);
        if (idx !== -1) {
            if (cart[idx].qty > 1) {
                cart[idx].qty--;
            } else {
                cart.splice(idx, 1);
            }
            updateClientCartUI();
            fetchProducts();
        }
    });

    // Open checkout slide panel
    $('#okj-floating-cart').on('click', function() {
        // Populate items in slide panel
        let html = '';
        cart.forEach(function(item) {
            let itemSub = item.price * item.qty;
            html += `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; font-size:13px;">
                    <div>
                        <strong>${item.name}</strong><br/>
                        <span style="color:var(--gray); font-size:11px;">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</span>
                    </div>
                    <strong>Rp ${itemSub.toLocaleString('id-ID')}</strong>
                </div>
            `;
        });
        $('#okj-panel-cart-items').html(html);

        $('#okj-cart-overlay').css('display', 'flex');
    });

    // Close checkout overlay
    $('#okj-close-cart-btn, #okj-cart-overlay').on('click', function(e) {
        if (e.target === this) {
            $('#okj-cart-overlay').hide();
        }
    });

    $('#okj-close-cart-btn').on('click', function() {
        $('#okj-cart-overlay').hide();
    });

    // Update floating cart widget and totals
    function updateClientCartUI() {
        let subtotal = 0;
        let count = 0;

        cart.forEach(function(item) {
            subtotal += item.price * item.qty;
            count += item.qty;
        });

        if (count > 0) {
            $('#okj-cart-count').text(count);
            $('#okj-cart-total-price').text('Rp ' + subtotal.toLocaleString('id-ID'));
            
            // Subtotal panel
            $('#okj-sum-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
            $('#okj-sum-total').text('Rp ' + subtotal.toLocaleString('id-ID'));
            
            $('#okj-floating-cart').show();
        } else {
            $('#okj-floating-cart').hide();
            $('#okj-cart-overlay').hide();
        }
    }

    // Place public self-service order POST trigger
    $('#okj-btn-submit-order').on('click', function() {
        const name = $('#okj-cust-name').val().trim();
        const whatsapp = $('#okj-cust-wa').val().trim();
        const notes = $('#okj-cust-notes').val();
        const payMethod = $('#okj-cust-pay-method').val();

        if (!name || !whatsapp) {
            alert('Nama Pemesan dan Nomor WhatsApp wajib diisi!');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('MENGIRIM PESANAN...');

        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php?action=okj_public_place_order'); ?>",
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                customer_name: name,
                customer_whatsapp: whatsapp,
                notes: notes,
                payment_method: payMethod,
                items: cart
            }),
            success: function(response) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="margin-top: 2px;"></span> KIRIM & BUAT PESANAN');
                if (response.success) {
                    // Redirect directly to self-service tracking status screen
                    window.location.href = window.location.pathname + '?okj_order=1&track_order=' + response.data.transaction_id;
                } else {
                    alert('Gagal membuat pesanan: ' + response.data.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="margin-top: 2px;"></span> KIRIM & BUAT PESANAN');
                alert('Terjadi kesalahan koneksi.');
            }
        });
    });
});
</script>
</body>
</html>
