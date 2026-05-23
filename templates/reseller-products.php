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
                                            data-duration="<?php echo esc_attr($p['duration_days']); ?>"
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
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label class="wrpm-label" style="margin-bottom: 0;">Seller</label>
                                <a href="#" class="wrpm-quick-add-seller-btn" style="text-decoration: none; font-size: 12px; color: #4f46e5; font-weight: 600; display: inline-flex; align-items: center;">
                                    <span class="dashicons dashicons-plus-alt2" style="font-size: 14px; width: 14px; height: 14px; margin-right: 2px; margin-top: 1px;"></span> Tambah Seller Baru
                                </a>
                            </div>
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
                            <label class="wrpm-label">Durasi Produk (Hari)</label>
                            <input type="number" name="duration_days" class="wrpm-input" value="<?php echo $row ? esc_attr($row['duration_days']) : ''; ?>" min="0" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Tanggal Kadaluwarsa</label>
                            <input type="date" name="expires_at" class="wrpm-input" value="<?php echo $row ? esc_attr($row['expires_at']) : ''; ?>" readonly style="background: #f1f5f9; cursor: not-allowed; pointer-events: none;" tabindex="-1" />
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
                                <th>Seller</th>
                                <th>Tanggal Beli</th>
                                <th>Masa Expired</th>
                                <th>Harga Beli</th>
                                <th>Status Bayar</th>
                                <th>Bukti Bayar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code><?php echo esc_html(substr($r['id'], 0, 8)); ?></code></td>
                                    <td><strong><?php echo esc_html($r['product_name']); ?></strong></td>
                                    <td><strong><?php echo esc_html($r['seller_name'] ?: '-'); ?></strong></td>
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
                                        <?php if (!empty($r['payment_attachments'])): ?>
                                            <a href="#" class="wrpm-view-payment-proof" 
                                               data-url="<?php echo esc_url($r['payment_attachments']); ?>"
                                               style="text-decoration: none; color: #4f46e5; font-weight: 600; display: inline-flex; align-items: center; border-bottom: 1px dashed #4f46e5; padding-bottom: 2px;"
                                               title="Lihat Bukti Pembayaran">
                                                <span class="dashicons dashicons-image-filter" style="margin-right: 4px; font-size: 16px; width: 16px; height: 16px;"></span>
                                                Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="wrpm-text-muted">-</span>
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

                    <!-- Pagination -->
                    <?php if (isset($total_pages) && $total_pages > 1): 
                        $current_offset = ($paged - 1) * $per_page;
                    ?>
                        <div class="wrpm-pagination">
                            <div class="wrpm-pagination-info">
                                Menampilkan <?php echo ($current_offset + 1); ?> - <?php echo min($total_rows, $current_offset + $per_page); ?> dari <?php echo $total_rows; ?> data
                            </div>
                            <div class="wrpm-pagination-links">
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

<!-- Modal Popup Bukti Pembayaran -->
<div id="wrpmAttachmentModal" class="wrpm-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="wrpm-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 600px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out;">
        <div class="wrpm-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-image-filter" style="margin-right: 8px; color: #4f46e5; font-size: 20px; width: 20px; height: 20px;"></span>
                Bukti Pembayaran / Lampiran
            </h3>
            <span class="wrpm-attachment-modal-close" style="color: #94a3b8; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</span>
        </div>
        <div class="wrpm-modal-body" style="padding: 24px; text-align: center; background: #f8fafc;">
            <img id="wrpmAttachmentImg" src="" alt="Bukti Pembayaran" style="max-width: 100%; max-height: 450px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;" />
        </div>
        <div class="wrpm-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <a id="wrpmAttachmentDownloadBtn" href="" download class="wrpm-btn wrpm-btn-primary" style="margin-right: 8px; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center;">
                <span class="dashicons dashicons-download" style="margin-right: 6px; font-size: 16px; width: 16px; height: 16px;"></span> Unduh Gambar
            </a>
            <button class="wrpm-btn wrpm-btn-secondary wrpm-attachment-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<style>
@keyframes wrpmFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.wrpm-attachment-modal-close:hover {
    color: #475569 !important;
}
.wrpm-view-payment-proof:hover {
    color: #4338ca !important;
    border-bottom-color: #4338ca !important;
}
</style>

<!-- Modal Quick Add Seller -->
<div id="wrpmQuickAddSellerModal" class="wrpm-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="wrpm-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out; margin: auto;">
        <div class="wrpm-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-admin-users" style="margin-right: 8px; color: #4f46e5;"></span>
                Tambah Seller Baru (Cepat)
            </h3>
            <span class="wrpm-quick-seller-close" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        </div>
        <div class="wrpm-modal-body" style="padding: 20px 24px;">
            <div class="wrpm-form-group" style="margin-bottom: 12px;">
                <label class="wrpm-label">Nama Seller <span class="wrpm-required">*</span></label>
                <input type="text" id="wrpmQuickSellerName" class="wrpm-input" placeholder="Masukkan nama seller..." required />
            </div>
            <div class="wrpm-form-group" style="margin-bottom: 12px;">
                <label class="wrpm-label">No. WhatsApp</label>
                <input type="text" id="wrpmQuickSellerWhatsapp" class="wrpm-input" placeholder="628123456789..." />
            </div>
            <div class="wrpm-form-group" style="margin-bottom: 0;">
                <label class="wrpm-label">Email</label>
                <input type="email" id="wrpmQuickSellerEmail" class="wrpm-input" placeholder="seller@email.com..." />
            </div>
        </div>
        <div class="wrpm-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="wrpm-btn wrpm-btn-secondary wrpm-quick-seller-close-btn" style="cursor: pointer;">Batal</button>
            <button type="button" id="wrpmQuickSellerSubmitBtn" class="wrpm-btn wrpm-btn-primary" style="cursor: pointer; display: inline-flex; align-items: center;">
                <span class="wrpm-spinner" style="display: none; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; width: 12px; height: 12px; margin-right: 6px; animation: wrpmSpin 1s linear infinite;"></span>
                Simpan Seller
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
