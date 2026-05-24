<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;

$t_transactions = OKJ_DB::get_table('pos_transactions');
if ($wpdb->get_var("SHOW TABLES LIKE '{$t_transactions}'") !== $t_transactions) {
    OKJ_DB::install();
}

// Fetch all transactions for Tab 2
$transactions = $wpdb->get_results("SELECT * FROM {$t_transactions} ORDER BY created_at DESC LIMIT 50", ARRAY_A);

// Settings for company details
$settings = get_option('okj_settings_v1', []);
$company_name = !empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name');
$qr_url = home_url('/?okj_order=1');
?>
<div class="okj-wrap okj-pos-container">

    <!-- Tabbed Navigation Header -->
    <div class="okj-pos-header" style="flex-direction: column; align-items: stretch; gap: 16px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div class="okj-pos-title-area">
                <h1>Point of Sale (POS)</h1>
                <p class="okj-subtitle">Kasir digital multi-item terintegrasi dengan pemesanan mandiri scan QR Code.</p>
            </div>
            <div class="okj-pos-meta-area">
                <div class="okj-pos-time" id="okj-pos-live-time">00:00:00</div>
                <div class="okj-pos-badge" id="okj-pos-waha-status">
                    <span class="okj-pulse-dot"></span> WAHA Gateway: Active
                </div>
            </div>
        </div>

        <!-- Elegant Navigation Tabs -->
        <div class="okj-pos-tabs" style="display: flex; border-bottom: 2px solid #e2e8f0; margin-top: 10px; gap: 8px;">
            <a href="#tab-cashier" class="okj-pos-tab-link active" style="padding: 12px 20px; text-decoration: none; font-weight: 700; font-size: 14px; color: #4f46e5; border-bottom: 3px solid #4f46e5; transition: all 0.2s;">
                <span class="dashicons dashicons-cart" style="font-size: 18px; margin-right: 4px;"></span> Mesin Kasir
            </a>
            <a href="#tab-history" class="okj-pos-tab-link" style="padding: 12px 20px; text-decoration: none; font-weight: 700; font-size: 14px; color: #64748b; border-bottom: 3px solid transparent; transition: all 0.2s;">
                <span class="dashicons dashicons-media-text" style="font-size: 18px; margin-right: 4px;"></span> Order & Transaksi
            </a>
            <a href="#tab-qrcode" class="okj-pos-tab-link" style="padding: 12px 20px; text-decoration: none; font-weight: 700; font-size: 14px; color: #64748b; border-bottom: 3px solid transparent; transition: all 0.2s;">
                <span class="dashicons dashicons-qrcode" style="font-size: 18px; margin-right: 4px;"></span> QR Pemesanan Mandiri
            </a>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- TAB 1: CASHIER INTERFACE                                                -->
    <!-- ======================================================================= -->
    <div id="tab-cashier" class="okj-pos-tab-content active-content">
        <div class="okj-pos-layout">
            <!-- LEFT SIDE: Catalog and Search (63% Width) -->
            <div class="okj-pos-catalog-panel">
                <div class="okj-card">
                    <div class="okj-card-body" style="padding: 16px;">
                        <div class="okj-pos-filter-bar">
                            <div class="okj-pos-search-box">
                                <span class="dashicons dashicons-search"></span>
                                <input type="text" id="okj-pos-search-input" placeholder="Cari nama produk..." />
                            </div>
                            <div class="okj-pos-category-select">
                                <select id="okj-pos-category-filter" class="okj-select">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="okj-pos-products-list-scroll">
                            <div class="okj-pos-products-grid" id="okj-pos-products-list">
                                <div class="okj-pos-loader">
                                    <div class="okj-spinner-large"></div>
                                    <p>Memuat katalog produk...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE: Cart and Checkout Details (35% Width) -->
            <div class="okj-pos-checkout-panel">
                <div class="okj-card" style="height: 100%; display: flex; flex-direction: column;">
                    <div class="okj-card-header" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); padding: 16px 20px;">
                        <h2 style="color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                            <span>Keranjang Belanja</span>
                            <span class="okj-pos-cart-count" id="okj-cart-count-badge">0 Item</span>
                        </h2>
                    </div>

                    <div class="okj-card-body okj-pos-cart-body" style="flex: 1; display: flex; flex-direction: column; padding: 16px;">
                        <div class="okj-pos-inputs-group">
                            <div class="okj-form-group" style="margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="okj-label" style="margin-bottom: 0; font-size: 13px;">Customer/Pelanggan <span class="okj-required">*</span></label>
                                    <a href="#" id="okj-pos-add-customer-btn" style="text-decoration: none; font-size: 11px; color: #6366f1; font-weight: 600;">
                                        + Customer Baru
                                    </a>
                                </div>
                                <select id="okj-pos-customer-select" class="okj-select" style="width: 100%;" required>
                                    <option value="">-- Pilih Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo esc_attr($c['id']); ?>" data-phone="<?php echo esc_attr($c['whatsapp'] ?: $c['phone']); ?>">
                                            <?php echo esc_html($c['name']); ?> <?php echo $c['whatsapp'] ? esc_html('(' . $c['whatsapp'] . ')') : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="okj-form-group" style="margin-bottom: 16px;">
                                <label class="okj-label" style="font-size: 13px;">Operator Kasir / Seller</label>
                                <select id="okj-pos-seller-select" class="okj-select" style="width: 100%;">
                                    <option value="">-- Pilih Seller (Opsional) --</option>
                                    <?php foreach ($sellers as $s): ?>
                                        <option value="<?php echo esc_attr($s['id']); ?>"><?php echo esc_html($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="okj-pos-cart-items-wrapper" id="okj-cart-items-list">
                            <div class="okj-pos-cart-empty">
                                <span class="dashicons dashicons-cart" style="font-size: 32px; width: 32px; height: 32px; color: #cbd5e1; margin-bottom: 8px;"></span>
                                <p>Keranjang kosong. Pilih produk di panel kiri.</p>
                            </div>
                        </div>

                        <div class="okj-pos-summary-area">
                            <div class="okj-pos-summary-row">
                                <span>Subtotal</span>
                                <strong id="okj-summary-subtotal">Rp 0</strong>
                            </div>
                            <div class="okj-pos-summary-row" style="align-items: center; margin-top: 8px; margin-bottom: 8px;">
                                <span>Potongan Diskon</span>
                                <input type="number" id="okj-summary-discount" class="okj-input okj-pos-discount-input" min="0" value="0" placeholder="Rp" style="max-width: 120px; padding: 4px 8px; text-align: right;" />
                            </div>
                            <div class="okj-pos-summary-row okj-pos-summary-total">
                                <span>TOTAL BAYAR</span>
                                <span id="okj-summary-total">Rp 0</span>
                            </div>
                        </div>

                        <div class="okj-pos-payment-selector">
                            <label class="okj-label" style="font-size: 13px; margin-bottom: 8px; display: block;">Metode Pembayaran</label>
                            <div class="okj-pos-payment-options">
                                <label class="okj-pos-pay-opt active" data-method="cash">
                                    <input type="radio" name="payment_method" value="cash" checked style="display:none;" />
                                    <span class="dashicons dashicons-money"></span> Cash/Tunai
                                </label>
                                <label class="okj-pos-pay-opt" data-method="transfer">
                                    <input type="radio" name="payment_method" value="transfer" style="display:none;" />
                                    <span class="dashicons dashicons-bank"></span> Transfer
                                </label>
                                <label class="okj-pos-pay-opt" data-method="qris">
                                    <input type="radio" name="payment_method" value="qris" style="display:none;" />
                                    <span class="dashicons dashicons-smartphone"></span> QRIS/E-Wallet
                                </label>
                            </div>
                        </div>

                        <div class="okj-form-group" style="margin-top: 12px; margin-bottom: 16px;">
                            <textarea id="okj-pos-notes" class="okj-input" placeholder="Catatan transaksi internal (opsional)..." rows="2" style="font-size: 12px; padding: 6px 10px;"></textarea>
                        </div>

                        <button class="okj-btn okj-btn-primary okj-pos-checkout-btn" id="okj-pos-checkout-submit">
                            <span class="dashicons dashicons-saved"></span> PROSES & BAYAR SEKARANG
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- TAB 2: TRANSACTIONS & LIVE ORDERS LIST                                  -->
    <!-- ======================================================================= -->
    <div id="tab-history" class="okj-pos-tab-content" style="display: none;">
        <div class="okj-card">
            <div class="okj-card-header">
                <h2>Riwayat Transaksi & Order Mandiri</h2>
            </div>
            <div class="okj-card-body">
                <table class="okj-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Nama Customer</th>
                            <th>Total Belanja</th>
                            <th>Metode Bayar</th>
                            <th>Status Proses</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #64748b; padding: 30px 0;">Belum ada riwayat transaksi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): 
                                $status = $tx['payment_status'];
                                $badge_style = 'background: #ffedd5; color: #ea580c;'; // pending
                                if ($status === 'processing') $badge_style = 'background: #e0e7ff; color: #4f46e5;';
                                if ($status === 'paid' || $status === 'completed') $badge_style = 'background: #dcfce7; color: #15803d;';
                            ?>
                                <tr id="tx-row-<?php echo esc_attr($tx['id']); ?>">
                                    <td><strong><?php echo esc_html($tx['transaction_no']); ?></strong></td>
                                    <td><?php echo esc_html($tx['created_at']); ?></td>
                                    <td><?php echo esc_html($tx['customer_name']); ?></td>
                                    <td><strong>Rp <?php echo number_format($tx['total'], 0, ',', '.'); ?></strong></td>
                                    <td><span style="font-size: 11px; padding: 2px 6px; background: #f1f5f9; border-radius: 4px; font-weight: 700;"><?php echo esc_html(strtoupper($tx['payment_method'])); ?></span></td>
                                    <td>
                                        <!-- Real-time Status Dropdown Manager -->
                                        <select class="okj-status-changer" data-id="<?php echo esc_attr($tx['id']); ?>" style="font-size: 11.5px; font-weight: 700; padding: 4px 8px; border-radius: 6px; <?php echo $badge_style; ?> cursor: pointer; outline: none; border: none;">
                                            <option value="pending" <?php selected($status, 'pending'); ?>>Menunggu Konfirmasi</option>
                                            <option value="processing" <?php selected($status, 'processing'); ?>>Sedang Diproses</option>
                                            <option value="completed" <?php selected($status, 'completed'); ?>>Selesai & Lunas</option>
                                            <option value="paid" <?php selected($status, 'paid'); ?>>Selesai & Lunas</option>
                                        </select>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="okj-btn okj-btn-secondary okj-btn-struk" 
                                            data-id="<?php echo esc_attr($tx['id']); ?>" 
                                            data-no="<?php echo esc_attr($tx['transaction_no']); ?>" 
                                            data-date="<?php echo esc_attr($tx['created_at']); ?>" 
                                            data-cust="<?php echo esc_attr($tx['customer_name']); ?>" 
                                            data-subtotal="<?php echo esc_attr($tx['subtotal']); ?>" 
                                            data-discount="<?php echo esc_attr($tx['discount']); ?>" 
                                            data-total="<?php echo esc_attr($tx['total']); ?>" 
                                            data-method="<?php echo esc_attr($tx['payment_method']); ?>" 
                                            style="padding: 4px 8px; font-size: 11px;">
                                            <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px;"></span> Nota
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- TAB 3: QR CODE GENERATOR CARD                                           -->
    <!-- ======================================================================= -->
    <div id="tab-qrcode" class="okj-pos-tab-content" style="display: none;">
        <div class="okj-card" style="max-width: 600px; margin: 0 auto;">
            <div class="okj-card-header" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 20px;">
                <h2 style="color: #ffffff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-qrcode" style="font-size: 24px; width: 24px; height: 24px;"></span>
                    QR Code Self-Service Pemesanan
                </h2>
            </div>
            <div class="okj-card-body" style="padding: 30px; text-align: center;">
                <p style="color: #64748b; font-size: 13.5px; margin-bottom: 24px; max-width: 500px; margin-left: auto; margin-right: auto;">
                    Tampilkan QR Code ini di meja kafe/restoran, lobi pelayanan, atau banner stand toko Anda agar pelanggan dapat memindai, memesan menu, dan melacak proses masak/layanan secara mandiri dari HP mereka!
                </p>

                <!-- Premium Printable QR Stand -->
                <div id="okj-qr-print-box" style="background: #ffffff; border: 2px solid #e2e8f0; border-radius: 16px; padding: 24px; max-width: 320px; margin: 0 auto; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: #1e1b4b; text-transform: uppercase;"><?php echo esc_html($company_name); ?></h3>
                    <p style="margin: 0 0 16px 0; font-size: 11px; color: #64748b;">PEMESANAN DIGITAL MANDIRI</p>
                    
                    <!-- QR Code rendering via QR Server API -->
                    <div style="background: #ffffff; border: 1.5px solid #e2e8f0; padding: 12px; border-radius: 10px; width: 200px; height: 200px; margin: 0 auto 16px auto; display: flex; align-items: center; justify-content: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode($qr_url); ?>" alt="Scan to Order QR Code" style="max-width: 100%; height: auto;" />
                    </div>

                    <div style="background: #f1f5f9; padding: 8px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; color: #4f46e5; margin-bottom: 8px;">
                        SCAN ME / PINDAI SAYA
                    </div>
                    <p style="font-size: 10px; color: #64748b; line-height: 1.4; margin: 0;">Pesan langsung tanpa antre melalui smartphone Anda!</p>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: center; gap: 12px;">
                    <button type="button" id="okj-print-qr-btn" class="okj-btn okj-btn-secondary">
                        <span class="dashicons dashicons-printer" style="margin-right: 4px;"></span> Cetak Kartu QR
                    </button>
                    <a href="<?php echo esc_url($qr_url); ?>" target="_blank" class="okj-btn okj-btn-primary" style="text-decoration: none;">
                        <span class="dashicons dashicons-external" style="margin-right: 4px;"></span> Buka Link POS Mandiri
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- MODAL: QUICK ADD CUSTOMER                  -->
<!-- ========================================== -->
<div id="okjPosCustomerModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; margin: auto;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-admin-users" style="margin-right: 8px; color: #6366f1;"></span>
                Tambah Customer Baru
            </h3>
            <span class="okj-pos-modal-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        </div>
        <div class="okj-modal-body" style="padding: 20px 24px;">
            <div class="okj-form-group" style="margin-bottom: 12px;">
                <label class="okj-label">Nama Lengkap <span class="okj-required">*</span></label>
                <input type="text" id="okj-pos-new-cust-name" class="okj-input" placeholder="Nama lengkap..." required />
            </div>
            <div class="okj-form-group" style="margin-bottom: 12px;">
                <label class="okj-label">Nomor WhatsApp</label>
                <input type="text" id="okj-pos-new-cust-wa" class="okj-input" placeholder="628123456789..." />
            </div>
            <div class="okj-form-group" style="margin-bottom: 0;">
                <label class="okj-label">Email</label>
                <input type="email" id="okj-pos-new-cust-email" class="okj-input" placeholder="customer@email.com..." />
            </div>
        </div>
        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="okj-btn okj-btn-secondary okj-pos-modal-close-btn">Batal</button>
            <button type="button" id="okj-pos-new-cust-submit" class="okj-btn okj-btn-primary">
                <span class="okj-spinner" style="display: none;"></span> Simpan Customer
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RECEIPT & SUCCESS DIALOG            -->
<!-- ========================================== -->
<div id="okjPosReceiptModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 480px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; margin: auto;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #16a34a; display: flex; align-items: center;">
                <span class="dashicons dashicons-yes-alt" style="margin-right: 8px; color: #16a34a;"></span>
                Rincian Transaksi
            </h3>
            <span class="okj-pos-receipt-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        </div>
        <div class="okj-modal-body" style="padding: 20px 24px;">
            <div class="okj-pos-thermal-struk" id="okj-pos-struk-print-area">
                <div class="okj-struk-header">
                    <h4 style="margin:0; font-weight: 700; font-size: 16px;"><?php echo esc_html(!empty($settings['pdf_company_name']) ? $settings['pdf_company_name'] : get_bloginfo('name')); ?></h4>
                    <p style="margin:4px 0 0 0; font-size:11px; color:#64748b;"><?php echo esc_html(!empty($settings['pdf_company_address']) ? $settings['pdf_company_address'] : 'Kasir Digital'); ?></p>
                </div>
                <div class="okj-struk-divider">-----------------------------------------</div>
                <table class="okj-struk-meta">
                    <tr><td>No. Transaksi</td><td id="struk-no">: -</td></tr>
                    <tr><td>Tanggal</td><td id="struk-date">: -</td></tr>
                    <tr><td>Pelanggan</td><td id="struk-cust">: -</td></tr>
                </table>
                <div class="okj-struk-divider">-----------------------------------------</div>
                <div id="struk-items-container"></div>
                <div class="okj-struk-divider">-----------------------------------------</div>
                <table class="okj-struk-totals">
                    <tr><td>Subtotal</td><td id="struk-subtotal" style="text-align: right;">Rp 0</td></tr>
                    <tr id="struk-discount-row"><td>Diskon</td><td id="struk-discount" style="text-align: right;">-Rp 0</td></tr>
                    <tr style="font-weight: 700;"><td>TOTAL BAYAR</td><td id="struk-total" style="text-align: right;">Rp 0</td></tr>
                </table>
                <div class="okj-struk-divider">-----------------------------------------</div>
                <table class="okj-struk-meta">
                    <tr><td>Metode Bayar</td><td id="struk-pay-method">: CASH</td></tr>
                    <tr><td>Status</td><td style="color:#16a34a; font-weight:700;">: LUNAS</td></tr>
                </table>
                <div class="okj-struk-divider">-----------------------------------------</div>
                <div class="okj-struk-footer">
                    <p style="margin: 0; font-size: 11px;">Terima kasih atas kunjungan Anda!</p>
                    <p style="margin: 4px 0 0 0; font-size: 10px; color: #94a3b8;">Powered by OKJualin</p>
                </div>
            </div>

            <div class="okj-pos-wa-box" style="margin-top: 16px; padding: 12px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 8px;">
                <label class="okj-label" style="font-size:12px; color:#15803d; margin-bottom: 6px; display:block;">Kirim Struk Nota via WhatsApp</label>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="okj-pos-wa-input" class="okj-input" placeholder="628123456789" style="flex:1; border-color:#bbf7d0;" />
                    <button type="button" id="okj-pos-wa-send-btn" class="okj-btn okj-btn-primary" style="background:#16a34a; padding: 8px 12px; font-size:12px;">
                        <span class="dashicons dashicons-whatsapp" style="margin-right:2px; font-size:14px; width:14px; height:14px;"></span> Kirim
                    </button>
                </div>
            </div>
        </div>
        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" id="okj-pos-print-struk-btn" class="okj-btn okj-btn-secondary" style="border-color:#cbd5e1;">
                <span class="dashicons dashicons-printer" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span> Cetak
            </button>
            <button type="button" class="okj-btn okj-btn-primary okj-pos-receipt-close-btn">Tutup</button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const safeAjaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl.replace(/^http:/i, window.location.protocol) : '/wp-admin/admin-ajax.php';
    let cart = [];

    // Tab Navigation controller
    $('.okj-pos-tab-link').on('click', function(e) {
        e.preventDefault();
        $('.okj-pos-tab-link').removeClass('active').css({'color':'#64748b', 'border-color':'transparent'});
        $(this).addClass('active').css({'color':'#4f46e5', 'border-color':'#4f46e5'});
        
        const target = $(this).attr('href');
        $('.okj-pos-tab-content').hide();
        $(target).show();
    });

    // Live clock in header
    setInterval(function() {
        let now = new Date();
        let h = String(now.getHours()).padStart(2, '0');
        let m = String(now.getMinutes()).padStart(2, '0');
        let s = String(now.getSeconds()).padStart(2, '0');
        $('#okj-pos-live-time').text(h + ':' + m + ':' + s);
    }, 1000);

    // Initialize select2
    if ($.fn.select2) {
        $('#okj-pos-customer-select').select2({
            placeholder: '-- Pilih Customer --',
            allowClear: true
        });
        $('#okj-pos-seller-select').select2({
            placeholder: '-- Pilih Seller (Opsional) --',
            allowClear: true
        });
    }

    // Interactive AJAX Status Changer
    $('.okj-status-changer').on('change', function() {
        const txId = $(this).data('id');
        const status = $(this).val();
        const select = $(this);

        // Instantly update badge color based on selected option
        let color = '#ea580c';
        let bg = '#ffedd5';
        if (status === 'processing') { color = '#4f46e5'; bg = '#e0e7ff'; }
        if (status === 'paid' || status === 'completed') { color = '#15803d'; bg = '#dcfce7'; }
        select.css({'color': color, 'background': bg});

        $.ajax({
            url: safeAjaxUrl,
            type: 'POST',
            data: {
                action: 'okj_pos_update_status',
                transaction_id: txId,
                status: status
            },
            success: function(response) {
                if (!response.success) {
                    alert('Gagal memperbarui status: ' + response.data.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan jaringan saat mengubah status.');
            }
        });
    });

    // Modal control for adding Customer
    $('#okj-pos-add-customer-btn').on('click', function(e) {
        e.preventDefault();
        $('#okjPosCustomerModal').css('display', 'flex');
    });

    $('.okj-pos-modal-close, .okj-pos-modal-close-btn').on('click', function() {
        $('#okjPosCustomerModal').css('display', 'none');
    });

    // Close Receipt modal
    $('.okj-pos-receipt-close, .okj-pos-receipt-close-btn').on('click', function() {
        $('#okjPosReceiptModal').css('display', 'none');
        cart = [];
        updateCartUI();
        $('#okj-pos-customer-select').val('').trigger('change');
        $('#okj-pos-seller-select').val('').trigger('change');
        $('#okj-summary-discount').val(0);
        $('#okj-pos-notes').val('');
    });

    // Add customer AJAX post
    $('#okj-pos-new-cust-submit').on('click', function() {
        const name = $('#okj-pos-new-cust-name').val().trim();
        const whatsapp = $('#okj-pos-new-cust-wa').val().trim();
        const email = $('#okj-pos-new-cust-email').val().trim();

        if (!name) {
            alert('Nama Lengkap wajib diisi!');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).addClass('okj-btn-loading');

        $.ajax({
            url: safeAjaxUrl,
            type: 'POST',
            data: {
                action: 'okj_quick_add_customer',
                name: name,
                whatsapp: whatsapp,
                email: email
            },
            success: function(response) {
                btn.prop('disabled', false).removeClass('okj-btn-loading');
                if (response.success) {
                    const newOption = new Option(response.data.name + ' (' + whatsapp + ')', response.data.id, true, true);
                    $(newOption).attr('data-phone', whatsapp);
                    $('#okj-pos-customer-select').append(newOption).trigger('change');
                    
                    $('#okj-pos-new-cust-name').val('');
                    $('#okj-pos-new-cust-wa').val('');
                    $('#okj-pos-new-cust-email').val('');
                    $('#okjPosCustomerModal').css('display', 'none');
                } else {
                    alert('Gagal menambah customer: ' + response.data.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).removeClass('okj-btn-loading');
                alert('Terjadi kesalahan jaringan.');
            }
        });
    });

    // Fetch master products
    function fetchProducts() {
        const search = $('#okj-pos-search-input').val().trim();
        const category = $('#okj-pos-category-filter').val();

        $('#okj-pos-products-list').html(`
            <div class="okj-pos-loader">
                <div class="okj-spinner-large"></div>
                <p>Memuat katalog produk...</p>
            </div>
        `);

        $.ajax({
            url: safeAjaxUrl,
            type: 'GET',
            dataType: 'text',
            data: {
                action: 'okj_pos_get_products',
                search: search,
                category: category
            },
            success: function(rawResponse) {
                try {
                    let cleanJson = rawResponse.trim();
                    let jsonStart = cleanJson.indexOf('{"success":');
                    if (jsonStart !== -1) {
                        cleanJson = cleanJson.substring(jsonStart);
                    }
                    let response = JSON.parse(cleanJson);

                    if (response.success) {
                        let html = '';
                        if (response.data.length === 0) {
                            html = `
                                <div class="okj-pos-loader">
                                    <span class="dashicons dashicons-search" style="font-size:32px; width:32px; height:32px; color:#cbd5e1; margin-bottom:8px;"></span>
                                    <p>Produk tidak ditemukan.</p>
                                </div>
                            `;
                        } else {
                            response.data.forEach(function(p) {
                                let formattedPrice = 'Rp ' + Number(p.sale_price).toLocaleString('id-ID');
                                html += `
                                    <div class="okj-pos-product-card" data-id="${p.id}" data-name="${p.name}" data-price="${p.sale_price}">
                                        <div>
                                            <h3 class="okj-pos-p-title">${p.name}</h3>
                                            <div class="okj-pos-p-meta">
                                                <span class="okj-pos-p-cat">${p.category || 'Umum'}</span>
                                                ${p.duration_days > 0 ? `<span class="okj-pos-p-dur">${p.duration_days} Hari</span>` : ''}
                                            </div>
                                        </div>
                                        <div>
                                            <p class="okj-pos-p-price">${formattedPrice}</p>
                                            <button type="button" class="okj-pos-p-add-btn">+ Tambah</button>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        $('#okj-pos-products-list').html(html);
                    } else {
                        $('#okj-pos-products-list').html('<p style="color:#ef4444; text-align:center;">Gagal memuat katalog.</p>');
                    }
                } catch(e) {
                    let errorMsg = 'Terjadi kesalahan parsing JSON.';
                    let snippet = rawResponse.trim().substring(0, 150).replace(/<[^>]*>/g, '');
                    errorMsg += '<br><span style="font-size:11px; display:inline-block; margin-top:8px; color:#ef4444; word-break:break-all; background:#fee2e2; padding:4px 8px; border-radius:4px;">Detail: ' + snippet + '</span>';
                    $('#okj-pos-products-list').html('<p style="color:#ef4444; text-align:center; padding: 20px;">' + errorMsg + '</p>');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Terjadi kesalahan koneksi.';
                if (xhr.status) {
                    errorMsg += ' (HTTP ' + xhr.status + ': ' + error + ')';
                }
                if (xhr.responseText) {
                    // Show a short snippet of response body to catch DB errors, 403, or PHP fatals
                    let snippet = xhr.responseText.trim().substring(0, 200).replace(/<[^>]*>/g, '');
                    errorMsg += '<br><span style="font-size:11px; display:inline-block; margin-top:8px; color:#ef4444; word-break:break-all; background:#fee2e2; padding:4px 8px; border-radius:4px;">Detail: ' + snippet + '</span>';
                }
                $('#okj-pos-products-list').html('<p style="color:#ef4444; text-align:center; padding: 20px;">' + errorMsg + '</p>');
            }
        });
    }

    fetchProducts();
    let searchTimeout = null;
    $('#okj-pos-search-input').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchProducts, 400);
    });
    $('#okj-pos-category-filter').on('change', fetchProducts);

    // Add to Cart handler
    $(document).on('click', '.okj-pos-product-card, .okj-pos-p-add-btn', function(e) {
        e.stopPropagation();
        const card = $(this).closest('.okj-pos-product-card');
        const id = card.data('id');
        const name = card.data('name');
        const price = Number(card.data('price'));

        let found = false;
        for (let i = 0; i < cart.length; i++) {
            if (cart[i].id === id) {
                cart[i].qty++;
                found = true;
                break;
            }
        }

        if (!found) {
            cart.push({
                id: id,
                name: name,
                price: price,
                qty: 1
            });
        }

        updateCartUI();
    });

    // Update Cart UI helper
    function updateCartUI() {
        let subtotal = 0;
        let count = 0;

        if (cart.length === 0) {
            $('#okj-cart-items-list').html(`
                <div class="okj-pos-cart-empty">
                    <span class="dashicons dashicons-cart" style="font-size: 32px; width: 32px; height: 32px; color: #cbd5e1; margin-bottom: 8px;"></span>
                    <p>Keranjang kosong. Pilih produk di panel kiri.</p>
                </div>
            `);
            $('#okj-cart-count-badge').text('0 Item');
            $('#okj-summary-subtotal').text('Rp 0');
            $('#okj-summary-total').text('Rp 0');
            return;
        }

        let html = '<div class="okj-pos-cart-list">';
        cart.forEach(function(item, idx) {
            const itemSub = item.price * item.qty;
            subtotal += itemSub;
            count += item.qty;

            html += `
                <div class="okj-pos-cart-item" data-idx="${idx}">
                    <div>
                        <p class="okj-pos-cart-item-name">${item.name}</p>
                        <p class="okj-pos-cart-item-price">Rp ${item.price.toLocaleString('id-ID')} / unit</p>
                    </div>
                    <div class="okj-pos-qty-controls">
                        <button type="button" class="okj-pos-qty-btn qty-dec">-</button>
                        <input type="text" class="okj-pos-qty-val" value="${item.qty}" readonly />
                        <button type="button" class="okj-pos-qty-btn qty-inc">+</button>
                    </div>
                    <button type="button" class="okj-pos-remove-item">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            `;
        });
        html += '</div>';

        $('#okj-cart-items-list').html(html);
        $('#okj-cart-count-badge').text(count + ' Item');

        const discount = Number($('#okj-summary-discount').val()) || 0;
        const total = Math.max(0, subtotal - discount);

        $('#okj-summary-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
        $('#okj-summary-total').text('Rp ' + total.toLocaleString('id-ID'));
    }

    $(document).on('click', '.qty-inc', function() {
        const idx = $(this).closest('.okj-pos-cart-item').data('idx');
        cart[idx].qty++;
        updateCartUI();
    });

    $(document).on('click', '.qty-dec', function() {
        const idx = $(this).closest('.okj-pos-cart-item').data('idx');
        if (cart[idx].qty > 1) {
            cart[idx].qty--;
        } else {
            cart.splice(idx, 1);
        }
        updateCartUI();
    });

    $(document).on('click', '.okj-pos-remove-item', function() {
        const idx = $(this).closest('.okj-pos-cart-item').data('idx');
        cart.splice(idx, 1);
        updateCartUI();
    });

    $('#okj-summary-discount').on('input', function() {
        updateCartUI();
    });

    $('.okj-pos-pay-opt').on('click', function() {
        $('.okj-pos-pay-opt').removeClass('active');
        $(this).addClass('active');
        $(this).find('input').prop('checked', true);
    });

    // POS cashier checkout post
    $('#okj-pos-checkout-submit').on('click', function() {
        const customerId = $('#okj-pos-customer-select').val();
        if (!customerId) {
            alert('Customer/Pelanggan wajib dipilih untuk memproses penjualan!');
            return;
        }

        if (cart.length === 0) {
            alert('Keranjang belanja masih kosong!');
            return;
        }

        const sellerId = $('#okj-pos-seller-select').val();
        const discount = Number($('#okj-summary-discount').val()) || 0;
        const notes = $('#okj-pos-notes').val();
        const paymentMethod = $('input[name="payment_method"]:checked').val();

        const btn = $(this);
        btn.prop('disabled', true).text('MEMPROSES...');

        $.ajax({
            url: safeAjaxUrl + '?action=okj_pos_checkout',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'text', // Read as raw text to safely parse BOM or whitespace-prepended responses
            data: JSON.stringify({
                customer_id: customerId,
                seller_id: sellerId,
                discount: discount,
                notes: notes,
                payment_method: paymentMethod,
                items: cart
            }),
            success: function(rawResponse) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> PROSES & BAYAR SEKARANG');
                try {
                    let cleanJson = rawResponse.trim();
                    let jsonStart = cleanJson.indexOf('{"success":');
                    if (jsonStart !== -1) {
                        cleanJson = cleanJson.substring(jsonStart);
                    }
                    let response = JSON.parse(cleanJson);
                    
                    if (response.success) {
                        showReceiptModal(response.data);
                    } else {
                        alert('Gagal checkout: ' + (response.data && response.data.message ? response.data.message : 'Terjadi kesalahan sistem.'));
                    }
                } catch(e) {
                    alert('Terjadi kesalahan parsing respon server: ' + e.message);
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> PROSES & BAYAR SEKARANG');
                let errorMsg = 'Terjadi kesalahan jaringan saat checkout.';
                if (xhr.responseText) {
                    let snippet = xhr.responseText.trim().substring(0, 150).replace(/<[^>]*>/g, '');
                    errorMsg += '\n\nDetail: ' + snippet;
                }
                alert(errorMsg);
            }
        });
    });

    // Action button to load and show Struk in logs
    $('.okj-btn-struk').on('click', function() {
        const id = $(this).data('id');
        
        // Fetch detailed items for this log via simple inline DOM/attribute gather, or fetch via AJAX.
        // Let's dynamically read data from log row attributes and show the modal
        const data = {
            transaction_id: id,
            transaction_no: $(this).data('no'),
            created_at: $(this).data('date'),
            customer_name: $(this).data('cust'),
            subtotal: Number($(this).data('subtotal')),
            discount: Number($(this).data('discount')),
            total: Number($(this).data('total')),
            payment_method: $(this).data('method'),
            items: [] // Will fetch via ajax or show simple total
        };

        // Load items from database to render accurately
        $.ajax({
            url: safeAjaxUrl,
            type: 'GET',
            data: {
                action: 'okj_pos_checkout', // We can repurpose checkout or fetch
                transaction_id: id,
                retrieve_only: 1
            },
            success: function(response) {
                if (response.success) {
                    data.items = response.data.items;
                    showReceiptModal(data);
                } else {
                    // Fallback to minimal rendering
                    data.items = [{ product_name: 'Daftar Item Belanjaan', price: data.total, qty: 1 }];
                    showReceiptModal(data);
                }
            },
            error: function() {
                data.items = [{ product_name: 'Daftar Item Belanjaan', price: data.total, qty: 1 }];
                showReceiptModal(data);
            }
        });
    });

    function showReceiptModal(data) {
        $('#struk-no').text(': ' + data.transaction_no);
        $('#struk-date').text(': ' + data.created_at);
        $('#struk-cust').text(': ' + data.customer_name);
        $('#struk-subtotal').text('Rp ' + data.subtotal.toLocaleString('id-ID'));

        if (data.discount > 0) {
            $('#struk-discount-row').show();
            $('#struk-discount').text('-Rp ' + data.discount.toLocaleString('id-ID'));
        } else {
            $('#struk-discount-row').hide();
        }

        $('#struk-total').text('Rp ' + data.total.toLocaleString('id-ID'));
        $('#struk-pay-method').text(': ' + data.payment_method.toUpperCase());

        let itemsHtml = '<table class="okj-struk-items-table" style="width:100%; border-collapse:collapse;">';
        data.items.forEach(function(item) {
            let itemSub = item.price * item.qty;
            itemsHtml += `
                <tr>
                    <td style="padding: 2px 0;">
                        ${item.product_name}<br/>
                        <span style="color:#64748b; font-size:10px;">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</span>
                    </td>
                    <td style="text-align:right; vertical-align: bottom; padding: 2px 0;">
                        Rp ${itemSub.toLocaleString('id-ID')}
                    </td>
                </tr>
            `;
        });
        itemsHtml += '</table>';
        $('#struk-items-container').html(itemsHtml);

        const waNo = $('#okj-pos-customer-select').find(':selected').data('phone') || '';
        $('#okj-pos-wa-input').val(waNo);
        $('#okj-pos-wa-send-btn').data('tx-id', data.transaction_id);

        $('#okjPosReceiptModal').css('display', 'flex');
    }

    // Cetak Struk
    $('#okj-pos-print-struk-btn').on('click', function() {
        const printContent = document.getElementById('okj-pos-struk-print-area').innerHTML;
        const originalContent = document.body.innerHTML;

        document.body.innerHTML = `
            <div style="display:flex; justify-content:center; align-items:center; padding: 20px; background:#ffffff;">
                <div style="width: 320px;">
                    ${printContent}
                </div>
            </div>
        `;
        window.print();
        location.reload();
    });

    // Cetak QR Stand
    $('#okj-print-qr-btn').on('click', function() {
        const printContent = document.getElementById('okj-qr-print-box').innerHTML;
        
        document.body.innerHTML = `
            <div style="display:flex; justify-content:center; align-items:center; padding: 40px; background:#ffffff; min-height: 100vh;">
                <div style="text-align: center; width: 320px;">
                    ${printContent}
                </div>
            </div>
        `;
        window.print();
        location.reload();
    });

    // Send WhatsApp Invoice Struk
    $('#okj-pos-wa-send-btn').on('click', function() {
        const txId = $(this).data('tx-id');
        const phone = $('#okj-pos-wa-input').val().trim();

        if (!phone) {
            alert('Nomor HP WhatsApp tujuan wajib diisi!');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('MENGIRIM...');

        $.ajax({
            url: safeAjaxUrl,
            type: 'POST',
            dataType: 'text', // Read as raw text to safely parse BOM or whitespace-prepended responses
            data: {
                action: 'okj_pos_send_wa_struk',
                transaction_id: txId,
                whatsapp_no: phone
            },
            success: function(rawResponse) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-whatsapp" style="margin-right:2px; font-size:14px; width:14px; height:14px;"></span> Kirim');
                try {
                    let cleanJson = rawResponse.trim();
                    let jsonStart = cleanJson.indexOf('{"success":');
                    if (jsonStart !== -1) {
                        cleanJson = cleanJson.substring(jsonStart);
                    }
                    let response = JSON.parse(cleanJson);
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Gagal kirim WhatsApp: ' + (response.data && response.data.message ? response.data.message : 'Terjadi kesalahan sistem.'));
                    }
                } catch(e) {
                    alert('Terjadi kesalahan parsing respon WhatsApp: ' + e.message);
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-whatsapp" style="margin-right:2px; font-size:14px; width:14px; height:14px;"></span> Kirim');
                let errorMsg = 'Terjadi kesalahan jaringan.';
                if (xhr.responseText) {
                    let snippet = xhr.responseText.trim().substring(0, 150).replace(/<[^>]*>/g, '');
                    errorMsg += '\n\nDetail: ' + snippet;
                }
                alert(errorMsg);
            }
        });
    });
});
</script>
