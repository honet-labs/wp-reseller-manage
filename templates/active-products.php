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
                                    <td><strong><?php echo esc_html($r['product_label']); ?></strong></td>
                                    <td>
                                        <div><strong><?php echo esc_html($r['customer_name']); ?></strong></div>
                                        <small class="wrpm-text-muted"><?php echo esc_html($r['customer_contact']); ?></small>
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
