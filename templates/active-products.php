<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrpm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="wrpm-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Pelacakan Produk Aktif' : 'Daftarkan Pelacakan Produk Aktif'; ?></h1>
                <p class="wrpm-subtitle">Petakan produk reseller yang terjual ke customer dan konfigurasikan status reminder.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-secondary" href="<?php echo admin_url('admin.php?page=wrpm-active-products'); ?>">Kembali</a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('wrpm_save_active_product'); ?>
                    <input type="hidden" name="action" value="wrpm_save_active_product" />
                    <?php if ($row): ?>
                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>" />
                    <?php endif; ?>

                    <div class="wrpm-form-grid">
                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Pilih Produk Reseller Terkait <span class="wrpm-required">*</span></label>
                            <select name="reseller_product_id" class="wrpm-select wrpm-select2" style="width: 100%;" required>
                                <option value="">-- Pilih Produk Reseller --</option>
                                <?php foreach ($resellers as $r): ?>
                                    <option value="<?php echo esc_attr($r['id']); ?>" <?php echo $row && $row['reseller_product_id'] === $r['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_html($r['product_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Pilih Customer <span class="wrpm-required">*</span></label>
                            <select name="customer_id" class="wrpm-select wrpm-select2" style="width: 100%;" required>
                                <option value="">-- Pilih Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo esc_attr($c['id']); ?>" <?php echo $row && $row['customer_id'] === $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_html($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Tanggal Mulai Aktif <span class="wrpm-required">*</span></label>
                            <input type="date" name="start_date" class="wrpm-input" value="<?php echo $row ? esc_attr($row['start_date']) : wp_date('Y-m-d'); ?>" required />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Harga Penjualan ke Customer (IDR)</label>
                            <input type="number" name="price" class="wrpm-input" value="<?php echo $row ? esc_attr($row['price']) : '0'; ?>" min="0" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Status Pembayaran Customer</label>
                            <select name="payment_status" class="wrpm-select">
                                <option value="pending" <?php echo $row && $row['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending / Belum Bayar</option>
                                <option value="paid" <?php echo $row && $row['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid / Lunas</option>
                            </select>
                        </div>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Bukti Pembayaran Customer (Gambar)</label>
                        <input type="file" name="payment_attachments" accept="image/*" />
                        <?php if ($row && $row['payment_attachments']): ?>
                            <div class="wrpm-mt-1">
                                <img src="<?php echo esc_url($row['payment_attachments']); ?>" style="max-width: 150px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Catatan Layanan</label>
                        <textarea name="notes" class="wrpm-input" rows="3"><?php echo $row ? esc_textarea($row['notes']) : ''; ?></textarea>
                    </div>

                    <div class="wrpm-form-actions wrpm-mt-2">
                        <button type="submit" class="wrpm-btn wrpm-btn-primary">Simpan Data Pelacakan</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- List Page -->
        <div class="wrpm-header">
            <div>
                <h1>Daftar Pelacakan Layanan Produk Aktif</h1>
                <p class="wrpm-subtitle">Daftar produk terjual ke customer yang dipantau masa expired dan sistem remindernya.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-primary" href="<?php echo admin_url('admin.php?page=wrpm-active-products&action=add'); ?>">
                    <span class="dashicons dashicons-plus"></span> Pelacakan Baru
                </a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <?php if (empty($rows)): ?>
                    <div class="wrpm-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <p>Belum ada produk aktif yang sedang dilacak.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                        <input type="text" class="wrpm-input wrpm-table-search" placeholder="Cari data..." style="max-width: 300px; width: 100%;" />
                    </div>
                    <table class="wrpm-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Produk Terjual</th>
                                <th>Customer</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Expired</th>
                                <th>Status Keaktifan</th>
                                <th>Status Bayar</th>
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
                                             <a href="#" class="wrpm-view-customer-detail" 
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
                                             <span class="wrpm-text-muted">-</span>
                                         <?php endif; ?>
                                         <div style="margin-top: 4px;"><small class="wrpm-text-muted"><?php echo esc_html($r['customer_contact']); ?></small></div>
                                    </td>
                                    <td><?php echo esc_html($r['start_date']); ?></td>
                                    <td><span class="dashicons dashicons-calendar-alt wrpm-text-muted"></span> <?php echo esc_html($r['expires_at']); ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'active'): ?>
                                            <span class="wrpm-badge wrpm-badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="wrpm-badge wrpm-badge-danger">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['payment_status'] === 'paid'): ?>
                                            <span class="wrpm-badge wrpm-badge-success">Lunas</span>
                                        <?php else: ?>
                                            <span class="wrpm-badge wrpm-badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="wrpm-btn-link wrpm-btn-invoice" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wrpm_invoice_pdf&id=' . $r['id']), 'wrpm_invoice_pdf_' . $r['id']); ?>" target="_blank">
                                            <span class="dashicons dashicons-pdf"></span> PDF Invoice
                                        </a>
                                    </td>
                                    <td>
                                        <div class="wrpm-row-actions">
                                            <a class="wrpm-btn-link" href="<?php echo admin_url('admin.php?page=wrpm-active-products&action=edit&id=' . $r['id']); ?>">
                                                <span class="dashicons dashicons-edit"></span> Edit
                                            </a>
                                            <a class="wrpm-btn-link wrpm-btn-link-danger" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_active_product&id=' . $r['id']), 'wrpm_delete_active_product_' . $r['id']); ?>" onclick="return confirm('Hapus data pelacakan ini? Semua log reminder pending untuk produk ini akan dihapus.');">
                                                <span class="dashicons dashicons-trash"></span> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Popup Detail Customer -->
<div id="wrpmCustomerModal" class="wrpm-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="wrpm-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out;">
        <div class="wrpm-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-admin-users" style="margin-right: 8px; color: #4f46e5; font-size: 20px; width: 20px; height: 20px;"></span>
                Detail Customer
            </h3>
            <span class="wrpm-customer-modal-close" style="color: #94a3b8; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</span>
        </div>
        <div class="wrpm-modal-body" style="padding: 24px; color: #334155; font-size: 0.95rem; line-height: 1.6;">
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
        <div class="wrpm-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button class="wrpm-btn wrpm-btn-secondary wrpm-customer-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<style>
@keyframes wrpmFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.wrpm-customer-modal-close:hover {
    color: #475569 !important;
}
.wrpm-view-customer-detail:hover {
    color: #4338ca !important;
    border-bottom-color: #4338ca !important;
}
</style>

