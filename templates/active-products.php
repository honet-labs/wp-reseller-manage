<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="okj-wrap">
    <?php if (isset($_GET['renewal_success'])): ?>
        <div class="notice notice-success is-dismissible okj-mb-2" style="margin: 0 0 20px 0; padding: 12px 16px; border-left-color: #10b981; background: #ecfdf5; color: #065f46; border-radius: 8px; border-left-width: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <p style="margin: 0; font-weight: 600;">Layanan berhasil diperpanjang (renewed)! Semua notifikasi reminder diset ulang ke pending.</p>
        </div>
    <?php endif; ?>
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="okj-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Pelacakan Produk Aktif' : 'Daftarkan Pelacakan Produk Aktif'; ?></h1>
                <p class="okj-subtitle">Petakan produk reseller yang terjual ke customer dan konfigurasikan status reminder.</p>
            </div>
            <div class="okj-actions">
                <a class="okj-btn okj-btn-secondary" href="<?php echo admin_url('admin.php?page=okj-active-products'); ?>">Kembali</a>
            </div>
        </div>

        <div class="okj-card okj-mt-2">
            <div class="okj-card-body">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('okj_save_active_product'); ?>
                    <input type="hidden" name="action" value="okj_save_active_product" />
                    <?php if ($row): ?>
                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>" />
                    <?php endif; ?>

                    <div class="okj-form-grid">
                        <div class="okj-form-group">
                            <label class="okj-label">Pilih Pembelian Produk Terkait <span class="okj-required">*</span></label>
                            <select name="reseller_product_id" class="okj-select okj-select2" style="width: 100%;" required>
                                <option value="">-- Pilih Pembelian Produk --</option>
                                <?php foreach ($resellers as $r): 
                                    $short_id = substr($r['id'], 0, 8);
                                    $seller_info = !empty($r['seller_name']) ? ' - ' . $r['seller_name'] : ' - Tanpa Seller';
                                    $is_currently_selected = $row && $row['reseller_product_id'] === $r['id'];
                                    $used_label = (!empty($r['is_used']) && $r['is_used'] > 0 && !$is_currently_selected) ? ' [Used]' : '';
                                ?>
                                    <option value="<?php echo esc_attr($r['id']); ?>" <?php echo $row && $row['reseller_product_id'] === $r['id'] ? 'selected' : ''; ?> data-purchase-date="<?php echo esc_attr($r['purchase_date']); ?>">
                                        <?php echo esc_html($short_id . ' - ' . $r['product_name'] . ' - ' . $r['duration_days'] . ' Hari' . $seller_info . $used_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="okj-form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label class="okj-label" style="margin-bottom: 0;">Pilih Customer <span class="okj-required">*</span></label>
                                <a href="#" class="okj-quick-add-customer-btn" style="text-decoration: none; font-size: 12px; color: #4f46e5; font-weight: 600; display: inline-flex; align-items: center;">
                                    <span class="dashicons dashicons-plus-alt2" style="font-size: 14px; width: 14px; height: 14px; margin-right: 2px; margin-top: 1px;"></span> Tambah Customer Baru
                                </a>
                            </div>
                            <select name="customer_id" class="okj-select okj-select2" style="width: 100%;" required>
                                <option value="">-- Pilih Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo esc_attr($c['id']); ?>" <?php echo $row && $row['customer_id'] === $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_html($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="okj-form-group">
                            <label class="okj-label">Tanggal Mulai Aktif <span class="okj-required">*</span></label>
                            <input type="date" name="start_date" class="okj-input" value="<?php echo $row ? esc_attr($row['start_date']) : wp_date('Y-m-d'); ?>" required />
                        </div>

                        <div class="okj-form-group">
                            <label class="okj-label">Harga Penjualan ke Customer (IDR)</label>
                            <input type="number" name="price" class="okj-input" value="<?php echo $row ? esc_attr($row['price']) : '0'; ?>" min="0" />
                        </div>

                        <div class="okj-form-group">
                            <label class="okj-label">Status Pembayaran Customer</label>
                            <select name="payment_status" class="okj-select">
                                <option value="pending" <?php echo $row && $row['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending / Belum Bayar</option>
                                <option value="paid" <?php echo $row && $row['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid / Lunas</option>
                            </select>
                        </div>
                    </div>

                    <div class="okj-form-group okj-mt-1">
                        <label class="okj-label">Bukti Pembayaran Customer (Gambar)</label>
                        <input type="file" name="payment_attachments" accept="image/*" />
                        <?php if ($row && $row['payment_attachments']): ?>
                            <div class="okj-mt-1">
                                <img src="<?php echo esc_url($row['payment_attachments']); ?>" style="max-width: 150px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="okj-form-group okj-mt-1">
                        <label class="okj-label">Catatan Layanan</label>
                        <textarea name="notes" class="okj-input" rows="3"><?php echo $row ? esc_textarea($row['notes']) : ''; ?></textarea>
                    </div>

                    <div class="okj-form-actions okj-mt-2">
                        <button type="submit" class="okj-btn okj-btn-primary">Simpan Data Pelacakan</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- List Page -->
        <div class="okj-header">
            <div>
                <h1>Daftar Pelacakan Layanan Produk Aktif</h1>
                <p class="okj-subtitle">Daftar produk terjual ke customer yang dipantau masa expired dan sistem remindernya.</p>
            </div>
            <div class="okj-actions">
                <a class="okj-btn okj-btn-primary" href="<?php echo admin_url('admin.php?page=okj-active-products&action=add'); ?>">
                    <span class="dashicons dashicons-plus"></span> Pelacakan Baru
                </a>
            </div>
        </div>

        <div class="okj-card okj-mt-2">
            <div class="okj-card-body">
                <?php
                $current_status_filter = !empty($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'active';
                ?>
                <div class="okj-tabs-wrapper" style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                    <div class="okj-status-tabs" style="display: flex; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
                        <a href="<?php echo admin_url('admin.php?page=okj-active-products&status_filter=active'); ?>" 
                           class="okj-tab-item <?php echo $current_status_filter === 'active' ? 'okj-tab-active' : ''; ?>"
                           style="text-decoration: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; transition: all 0.2s; color: <?php echo $current_status_filter === 'active' ? '#ffffff' : '#64748b'; ?>; background: <?php echo $current_status_filter === 'active' ? '#4f46e5' : 'transparent'; ?>; box-shadow: <?php echo $current_status_filter === 'active' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none'; ?>;">
                            <span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px; margin-top: 1px; margin-right: 4px;"></span>
                            Aktif
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=okj-active-products&status_filter=expired'); ?>" 
                           class="okj-tab-item <?php echo $current_status_filter === 'expired' ? 'okj-tab-active' : ''; ?>"
                           style="text-decoration: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; transition: all 0.2s; color: <?php echo $current_status_filter === 'expired' ? '#ffffff' : '#64748b'; ?>; background: <?php echo $current_status_filter === 'expired' ? '#4f46e5' : 'transparent'; ?>; box-shadow: <?php echo $current_status_filter === 'expired' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none'; ?>;">
                            <span class="dashicons dashicons-no-alt" style="font-size: 16px; width: 16px; height: 16px; margin-top: 1px; margin-right: 4px;"></span>
                            Expired
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=okj-active-products&status_filter=all'); ?>" 
                           class="okj-tab-item <?php echo $current_status_filter === 'all' ? 'okj-tab-active' : ''; ?>"
                           style="text-decoration: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; transition: all 0.2s; color: <?php echo $current_status_filter === 'all' ? '#ffffff' : '#64748b'; ?>; background: <?php echo $current_status_filter === 'all' ? '#4f46e5' : 'transparent'; ?>; box-shadow: <?php echo $current_status_filter === 'all' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none'; ?>;">
                            <span class="dashicons dashicons-category" style="font-size: 16px; width: 16px; height: 16px; margin-top: 1px; margin-right: 4px;"></span>
                            Semua Data
                        </a>
                    </div>
                    <?php if (!empty($rows)): ?>
                        <div class="okj-search-container" style="display: flex; gap: 8px; align-items: center;">
                            <input type="text" class="okj-input okj-table-search" placeholder="Cari data..." style="max-width: 250px; width: 100%; margin-bottom: 0;" />
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($rows)): ?>
                    <div class="okj-empty-state" style="padding: 48px 24px; text-align: center;">
                        <span class="dashicons dashicons-info" style="font-size: 36px; width: 36px; height: 36px; color: #94a3b8; margin-bottom: 12px; display: inline-block;"></span>
                        <p style="margin: 0; font-size: 15px; color: #64748b; font-weight: 500;">Tidak ada produk pelacakan dengan status filter ini.</p>
                    </div>
                <?php else: ?>
                    <table class="okj-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Produk Terjual</th>
                                <th>Customer</th>
                                <th>Catatan</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Expired</th>
                                <th>Status Keaktifan</th>
                                <th>Status Bayar</th>
                                <th>Bukti Bayar</th>
                                <th>Invoice</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code><?php echo esc_html(substr($r['id'], 0, 8)); ?></code></td>
                                    <td><strong><?php echo esc_html($r['product_label']); ?></strong></td>
                                    <td>
                                         <?php if (!empty($r['customer_name'])): ?>
                                             <a href="#" class="okj-view-customer-detail" 
                                                data-name="<?php echo esc_attr($r['customer_name']); ?>"
                                                data-email="<?php echo esc_attr($r['customer_email'] ?: '-'); ?>"
                                                data-phone="<?php echo esc_attr($r['customer_phone'] ?: '-'); ?>"
                                                data-telegram="<?php echo esc_attr($r['customer_telegram'] ?: '-'); ?>"
                                                data-whatsapp="<?php echo esc_attr($r['customer_whatsapp'] ?: '-'); ?>"
                                                style="text-decoration: none; color: #4f46e5; font-weight: 600; border-bottom: 1px dashed #4f46e5; padding-bottom: 2px;"
                                                title="Lihat Detail Customer">
                                                 <?php echo esc_html($r['customer_name']); ?>
                                             </a>
                                         <?php else: ?>
                                             <span class="okj-text-muted">-</span>
                                         <?php endif; ?>
                                         <div style="margin-top: 4px;"><small class="okj-text-muted"><?php echo esc_html($r['customer_contact']); ?></small></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['notes'])): ?>
                                            <a href="#" class="okj-view-active-notes" 
                                               data-name="<?php echo esc_attr($r['product_label']); ?>" 
                                               data-notes="<?php echo esc_attr(wp_strip_all_tags($r['notes'])); ?>" 
                                               style="text-decoration: none; color: #4338ca; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #e0e7ff; border-radius: 6px; border: 1px solid #c7d2fe; transition: all 0.2s;"
                                               title="Lihat Catatan">
                                                <span class="dashicons dashicons-visibility" style="font-size: 18px; width: 18px; height: 18px;"></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="okj-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($r['start_date']); ?></td>
                                    <td><span class="dashicons dashicons-calendar-alt okj-text-muted"></span> <?php echo esc_html($r['expires_at']); ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'active'): ?>
                                            <span class="okj-badge okj-badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="okj-badge okj-badge-danger">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['payment_status'] === 'paid'): ?>
                                            <span class="okj-badge okj-badge-success">Lunas</span>
                                        <?php else: ?>
                                            <span class="okj-badge okj-badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['payment_attachments'])): ?>
                                            <a href="#" class="okj-view-payment-proof" 
                                               data-url="<?php echo esc_url($r['payment_attachments']); ?>"
                                               style="text-decoration: none; color: #4f46e5; font-weight: 600; display: inline-flex; align-items: center; border-bottom: 1px dashed #4f46e5; padding-bottom: 2px;"
                                               title="Lihat Bukti Pembayaran">
                                                <span class="dashicons dashicons-image-filter" style="margin-right: 4px; font-size: 16px; width: 16px; height: 16px;"></span>
                                                Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="okj-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="okj-btn-link okj-btn-invoice" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=okj_invoice_pdf&id=' . $r['id']), 'okj_invoice_pdf_' . $r['id']); ?>" target="_blank">
                                            <span class="dashicons dashicons-pdf"></span> PDF Invoice
                                        </a>
                                    </td>
                                    <td>
                                        <div class="okj-row-actions" style="display: flex; flex-direction: column; gap: 4px;">
                                            <a class="okj-btn-link okj-renew-product-btn" href="#" data-id="<?php echo esc_attr($r['id']); ?>" data-name="<?php echo esc_attr($r['product_label']); ?>" data-expiry="<?php echo esc_attr($r['expires_at']); ?>" data-price="<?php echo esc_attr($r['price']); ?>" style="color: #4f46e5; font-weight: 600;">
                                                <span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; margin-right: 2px;"></span> Perpanjang
                                            </a>
                                            <a class="okj-btn-link okj-renewal-history-btn" href="#" data-id="<?php echo esc_attr($r['id']); ?>" style="color: #059669; font-weight: 600;">
                                                <span class="dashicons dashicons-backup" style="font-size: 14px; width: 14px; height: 14px; margin-right: 2px;"></span> Riwayat Renewal
                                            </a>
                                            <div style="display: flex; gap: 8px; margin-top: 4px;">
                                                <a class="okj-btn-link" href="<?php echo admin_url('admin.php?page=okj-active-products&action=edit&id=' . $r['id']); ?>">
                                                    <span class="dashicons dashicons-edit"></span> Edit
                                                </a>
                                                <a class="okj-btn-link okj-btn-link-danger" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=okj_delete_active_product&id=' . $r['id']), 'okj_delete_active_product_' . $r['id']); ?>" onclick="return confirm('Hapus data pelacakan ini? Semua log reminder pending untuk produk ini akan dihapus.');">
                                                    <span class="dashicons dashicons-trash"></span> Hapus
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if (isset($total_pages) && $total_pages > 1): 
                        $current_offset = ($paged - 1) * $per_page;
                    ?>
                        <div class="okj-pagination">
                            <div class="okj-pagination-info">
                                Menampilkan <?php echo ($current_offset + 1); ?> - <?php echo min($total_rows, $current_offset + $per_page); ?> dari <?php echo $total_rows; ?> data
                            </div>
                            <div class="okj-pagination-links">
                                <?php
                                echo paginate_links([
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'prev_text' => '&laquo; Prev',
                                    'next_text' => 'Next &raquo;',
                                    'total' => $total_pages,
                                    'current' => $paged,
                                    'type' => 'plain'
                                ]);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Popup Detail Customer -->
<div id="wrpmCustomerModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-admin-users" style="margin-right: 8px; color: #4f46e5; font-size: 20px; width: 20px; height: 20px;"></span>
                Detail Customer
            </h3>
            <span class="okj-customer-modal-close" style="color: #94a3b8; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</span>
        </div>
        <div class="okj-modal-body" style="padding: 24px; color: #334155; font-size: 0.95rem; line-height: 1.6;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b; width: 35%;">Nama Customer</td>
                    <td id="wrpmCustomerName" style="padding: 10px 0; color: #0f172a; font-weight: 600;">-</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">Email</td>
                    <td id="wrpmCustomerEmail" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">Telepon</td>
                    <td id="wrpmCustomerPhone" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">Telegram Chat ID</td>
                    <td id="wrpmCustomerTelegram" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">WhatsApp</td>
                    <td id="wrpmCustomerWhatsapp" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
            </table>
        </div>
        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button class="okj-btn okj-btn-secondary okj-customer-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Popup Bukti Pembayaran -->
<div id="wrpmAttachmentModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 600px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-image-filter" style="margin-right: 8px; color: #4f46e5; font-size: 20px; width: 20px; height: 20px;"></span>
                Bukti Pembayaran / Lampiran
            </h3>
            <span class="okj-attachment-modal-close" style="color: #94a3b8; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</span>
        </div>
        <div class="okj-modal-body" style="padding: 24px; text-align: center; background: #f8fafc;">
            <img id="wrpmAttachmentImg" src="" alt="Bukti Pembayaran" style="max-width: 100%; max-height: 450px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;" />
        </div>
        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <a id="wrpmAttachmentDownloadBtn" href="" download class="okj-btn okj-btn-primary" style="margin-right: 8px; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center;">
                <span class="dashicons dashicons-download" style="margin-right: 6px; font-size: 16px; width: 16px; height: 16px;"></span> Unduh Gambar
            </a>
            <button class="okj-btn okj-btn-secondary okj-attachment-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<style>
@keyframes wrpmFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.okj-customer-modal-close:hover, .okj-attachment-modal-close:hover {
    color: #475569 !important;
}
.okj-view-customer-detail:hover, .okj-view-payment-proof:hover {
    color: #4338ca !important;
    border-bottom-color: #4338ca !important;
}
</style>

<!-- Modal Quick Add Customer -->
<div id="wrpmQuickAddCustomerModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out; margin: auto;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-admin-users" style="margin-right: 8px; color: #4f46e5;"></span>
                Tambah Customer Baru (Cepat)
            </h3>
            <span class="okj-quick-customer-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        </div>
        <div class="okj-modal-body" style="padding: 20px 24px;">
            <div class="okj-form-group" style="margin-bottom: 12px;">
                <label class="okj-label">Nama Customer <span class="okj-required">*</span></label>
                <input type="text" id="wrpmQuickCustomerName" class="okj-input" placeholder="Masukkan nama customer..." required />
            </div>
            <div class="okj-form-group" style="margin-bottom: 12px;">
                <label class="okj-label">No. WhatsApp</label>
                <input type="text" id="wrpmQuickCustomerWhatsapp" class="okj-input" placeholder="628123456789..." />
            </div>
            <div class="okj-form-group" style="margin-bottom: 0;">
                <label class="okj-label">Email</label>
                <input type="email" id="wrpmQuickCustomerEmail" class="okj-input" placeholder="customer@email.com..." />
            </div>
        </div>
        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="okj-btn okj-btn-secondary okj-quick-customer-close-btn" style="cursor: pointer;">Batal</button>
            <button type="button" id="wrpmQuickCustomerSubmitBtn" class="okj-btn okj-btn-primary" style="cursor: pointer; display: inline-flex; align-items: center;">
                <span class="okj-spinner" style="display: none; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; width: 12px; height: 12px; margin-right: 6px; animation: wrpmSpin 1s linear infinite;"></span>
                Simpan Customer
            </button>
        </div>
    </div>
</div>

<style>
@keyframes wrpmSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- Modal Active Product Notes -->
<div id="wrpmActiveNotesModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out; margin: auto;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-testimonial" style="margin-right: 8px; color: #4f46e5;"></span>
                Catatan Layanan
            </h3>
            <span class="okj-active-notes-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        </div>
        <div class="okj-modal-body" style="padding: 20px 24px;">
            <h4 id="wrpmActiveNotesTitle" style="margin: 0 0 12px 0; font-size: 1rem; font-weight: 600; color: #334155;"></h4>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; font-size: 14px; color: #475569; line-height: 1.6; min-height: 100px; white-space: pre-wrap;" id="wrpmActiveNotesContent"></div>
        </div>
        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button class="okj-btn okj-btn-secondary okj-active-notes-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Perpanjang Layanan (Renewal) -->
<div id="okjRenewProductModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out; margin: auto;">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('okj_renew_active_product'); ?>
            <input type="hidden" name="action" value="okj_renew_active_product" />
            <input type="hidden" id="okj_renew_product_id" name="active_product_id" value="" />

            <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                    <span class="dashicons dashicons-update" style="margin-right: 8px; color: #4f46e5; font-size: 20px; width: 20px; height: 20px;"></span>
                    Perpanjang Layanan (Renewal)
                </h3>
                <span class="okj-renew-modal-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
            </div>
            
            <div class="okj-modal-body" style="padding: 20px 24px; color: #334155; font-size: 14px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                    <p style="margin: 0 0 6px 0; color: #64748b; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">PRODUK & MASA AKTIF</p>
                    <p style="margin: 0; font-weight: 600; color: #0f172a;" id="okj_renew_product_name">-</p>
                    <p style="margin: 4px 0 0 0; color: #475569;">Masa Aktif Saat Ini: <strong id="okj_renew_old_expiry">-</strong></p>
                </div>

                <div class="okj-form-group" style="margin-bottom: 12px;">
                    <label class="okj-label">Durasi Tambahan (Hari) <span class="okj-required">*</span></label>
                    <input type="number" name="duration_days" class="okj-input" value="30" min="1" required />
                </div>

                <div class="okj-form-group" style="margin-bottom: 12px;">
                    <label class="okj-label">Mulai Perpanjangan Dari <span class="okj-required">*</span></label>
                    <select name="start_from" class="okj-select">
                        <option value="old_expiry" id="okj_renew_option_old_expiry">Masa Aktif Habis Lama</option>
                        <option value="today">Hari Ini (Sejak Diproses)</option>
                    </select>
                </div>

                <div class="okj-form-group" style="margin-bottom: 12px;">
                    <label class="okj-label">Biaya Perpanjangan (IDR) <span class="okj-required">*</span></label>
                    <input type="number" id="okj_renew_price" name="price" class="okj-input" value="0" min="0" required />
                </div>

                <div class="okj-form-group" style="margin-bottom: 12px;">
                    <label class="okj-label">Status Pembayaran <span class="okj-required">*</span></label>
                    <select name="payment_status" class="okj-select">
                        <option value="paid">Paid / Lunas</option>
                        <option value="pending">Pending / Belum Bayar</option>
                    </select>
                </div>

                <div class="okj-form-group" style="margin-bottom: 12px;">
                    <label class="okj-label">Bukti Pembayaran (Gambar, Opsional)</label>
                    <input type="file" name="payment_attachments" accept="image/*" />
                </div>

                <div class="okj-form-group" style="margin-bottom: 0;">
                    <label class="okj-label">Catatan Perpanjangan</label>
                    <textarea name="notes" class="okj-input" rows="2" placeholder="Catatan tambahan perpanjangan..."></textarea>
                </div>
            </div>

            <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="okj-btn okj-btn-secondary okj-renew-modal-close-btn" style="cursor: pointer;">Batal</button>
                <button type="submit" class="okj-btn okj-btn-primary" style="cursor: pointer;">Proses Perpanjangan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Riwayat Renewal History -->
<div id="okjRenewalHistoryModal" class="okj-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="okj-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 700px; width: 95%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out; margin: auto;">
        <div class="okj-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-backup" style="margin-right: 8px; color: #059669; font-size: 20px; width: 20px; height: 20px;"></span>
                Riwayat Perpanjangan (Renewal History)
            </h3>
            <span class="okj-history-modal-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        </div>
        
        <div class="okj-modal-body" style="padding: 20px 24px; max-height: 450px; overflow-y: auto;" id="okj_history_content">
            <!-- Loaded dynamically via AJAX -->
        </div>

        <div class="okj-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button class="okj-btn okj-btn-secondary okj-history-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

