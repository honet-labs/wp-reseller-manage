<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrpm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="wrpm-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Produk Reseller' : 'Tambah Produk Reseller'; ?></h1>
                <p class="wrpm-subtitle">Daftarkan transaksi produk reseller yang dibeli dari supplier luar.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-secondary" href="<?php echo admin_url('admin.php?page=wrpm-reseller-products'); ?>">Kembali</a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('wrpm_save_reseller_product'); ?>
                    <input type="hidden" name="action" value="wrpm_save_reseller_product" />
                    <?php if ($row): ?>
                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>" />
                    <?php endif; ?>

                    <div class="wrpm-form-grid">
                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Pilih Produk Referensi <span class="wrpm-required">*</span></label>
                            <select name="price_id" class="wrpm-select wrpm-select2" style="width: 100%;" required>
                                <option value="">-- Pilih Master Harga --</option>
                                <?php foreach ($prices as $p): 
                                    $short_id = substr($p['id'], 0, 8);
                                    $seller_info = !empty($p['seller_name']) ? ' - ' . $p['seller_name'] : ' - Tanpa Seller';
                                ?>
                                    <option value="<?php echo esc_attr($p['id']); ?>" 
                                            data-seller-id="<?php echo esc_attr($p['seller_id']); ?>" 
                                            <?php echo $row && $row['price_id'] === $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_html($short_id . ' - ' . $p['name'] . ' - ' . $p['duration_days'] . ' Hari' . $seller_info); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Kategori</label>
                            <input type="text" name="category" class="wrpm-input" value="<?php echo $row ? esc_attr($row['category']) : ''; ?>" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Seller</label>
                            <select name="seller_id" class="wrpm-select wrpm-select2" style="width: 100%;">
                                <option value="">-- Pilih Seller --</option>
                                <?php foreach ($sellers as $s): ?>
                                    <option value="<?php echo esc_attr($s['id']); ?>" <?php echo $row && $row['seller_id'] === $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_html($s['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Tanggal Pembelian</label>
                            <input type="date" name="purchase_date" class="wrpm-input" value="<?php echo $row ? esc_attr($row['purchase_date']) : wp_date('Y-m-d'); ?>" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Tanggal Kadaluwarsa</label>
                            <input type="date" name="expires_at" class="wrpm-input" value="<?php echo $row ? esc_attr($row['expires_at']) : ''; ?>" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Harga Pembelian (Modal)</label>
                            <input type="number" name="price" class="wrpm-input" value="<?php echo $row ? esc_attr($row['price']) : '0'; ?>" min="0" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Status Pembayaran</label>
                            <select name="payment_status" class="wrpm-select">
                                <option value="pending" <?php echo $row && $row['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending / Belum Bayar</option>
                                <option value="paid" <?php echo $row && $row['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid / Lunas</option>
                            </select>
                        </div>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Bukti Pembayaran / Lampiran (Gambar)</label>
                        <input type="file" name="payment_attachments" accept="image/*" />
                        <?php if ($row && $row['payment_attachments']): ?>
                            <div class="wrpm-mt-1">
                                <img src="<?php echo esc_url($row['payment_attachments']); ?>" style="max-width: 150px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Deskripsi</label>
                        <textarea name="description" class="wrpm-input" rows="2"><?php echo $row ? esc_textarea($row['description']) : ''; ?></textarea>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Catatan Tambahan</label>
                        <textarea name="notes" class="wrpm-input" rows="2"><?php echo $row ? esc_textarea($row['notes']) : ''; ?></textarea>
                    </div>

                    <div class="wrpm-form-actions wrpm-mt-2">
                        <button type="submit" class="wrpm-btn wrpm-btn-primary">Simpan Produk Reseller</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- List Page -->
        <div class="wrpm-header">
            <div>
                <h1>Daftar Pembelian Produk Reseller</h1>
                <p class="wrpm-subtitle">Daftar transaksi pembelian produk ke reseller supplier eksternal.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-primary" href="<?php echo admin_url('admin.php?page=wrpm-reseller-products&action=add'); ?>">
                    <span class="dashicons dashicons-plus"></span> Tambah Pembelian
                </a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <?php if (empty($rows)): ?>
                    <div class="wrpm-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <p>Belum ada data pembelian produk reseller.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                        <input type="text" class="wrpm-input wrpm-table-search" placeholder="Cari data..." style="max-width: 300px; width: 100%;" />
                    </div>
                    <table class="wrpm-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Produk</th>
                                <th>Supplier / Reseller</th>
                                <th>Tanggal Beli</th>
                                <th>Masa Expired</th>
                                <th>Harga Beli</th>
                                <th>Status Bayar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code><?php echo esc_html(substr($r['id'], 0, 8)); ?></code></td>
                                    <td><strong><?php echo esc_html($r['product_name']); ?></strong></td>
                                    <td>
                                        <div><strong><?php echo esc_html($r['reseller_name'] ?: '-'); ?></strong></div>
                                        <small class="wrpm-text-muted"><?php echo esc_html($r['reseller_contact']); ?></small>
                                    </td>
                                    <td><?php echo esc_html($r['purchase_date']); ?></td>
                                    <td><?php echo esc_html($r['expires_at'] ?: '-'); ?></td>
                                    <td>Rp <?php echo number_format_i18n($r['price'], 0); ?></td>
                                    <td>
                                        <?php if ($r['payment_status'] === 'paid'): ?>
                                            <span class="wrpm-badge wrpm-badge-success">Paid</span>
                                        <?php else: ?>
                                            <span class="wrpm-badge wrpm-badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="wrpm-row-actions">
                                            <a class="wrpm-btn-link" href="<?php echo admin_url('admin.php?page=wrpm-reseller-products&action=edit&id=' . $r['id']); ?>">
                                                <span class="dashicons dashicons-edit"></span> Edit
                                            </a>
                                            <a class="wrpm-btn-link wrpm-btn-link-danger" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_reseller_product&id=' . $r['id']), 'wrpm_delete_reseller_product_' . $r['id']); ?>" onclick="return confirm('Hapus data pembelian ini?');">
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
