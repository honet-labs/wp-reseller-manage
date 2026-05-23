<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrpm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="wrpm-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Daftar Harga Produk' : 'Tambah Daftar Harga Produk'; ?></h1>
                <p class="wrpm-subtitle">Tetapkan konfigurasi harga master untuk produk yang dijual.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-secondary" href="<?php echo admin_url('admin.php?page=wrpm-product-prices'); ?>">Kembali</a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('wrpm_save_price'); ?>
                    <input type="hidden" name="action" value="wrpm_save_price" />
                    <?php if ($row): ?>
                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>" />
                    <?php endif; ?>

                    <div class="wrpm-form-grid">
                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Nama Produk <span class="wrpm-required">*</span></label>
                            <input type="text" name="name" class="wrpm-input" value="<?php echo $row ? esc_attr($row['name']) : ''; ?>" required />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Kategori (Pilih atau Ketik Baru)</label>
                            <select name="category" class="wrpm-select wrpm-select2-category" style="width: 100%;">
                                <option value="">-- Pilih Kategori --</option>
                                <?php
                                $selected_category = $row ? trim($row['category']) : '';
                                if ($selected_category !== '') {
                                    echo '<option value="' . esc_attr($selected_category) . '" selected>' . esc_html($selected_category) . '</option>';
                                }
                                if (!empty($existing_categories)) {
                                    foreach ($existing_categories as $cat) {
                                        if ($cat !== $selected_category) {
                                            echo '<option value="' . esc_attr($cat) . '">' . esc_html($cat) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Tags (Pilih atau Ketik Baru)</label>
                            <select name="tags[]" class="wrpm-select wrpm-select2-tags" multiple="multiple" style="width: 100%;">
                                <?php
                                $selected_tags = [];
                                if ($row && !empty($row['tags'])) {
                                    $selected_tags = array_map('trim', explode(',', $row['tags']));
                                }
                                // Render selected tags first so they are selected
                                foreach ($selected_tags as $t) {
                                    echo '<option value="' . esc_attr($t) . '" selected>' . esc_html($t) . '</option>';
                                }
                                // Render other existing unique tags
                                if (!empty($existing_tags)) {
                                    foreach ($existing_tags as $t) {
                                        if (!in_array($t, $selected_tags)) {
                                            echo '<option value="' . esc_attr($t) . '">' . esc_html($t) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label class="wrpm-label" style="margin-bottom: 0;">Seller/Supplier/Provider</label>
                                <a href="#" class="wrpm-quick-add-seller-btn" style="text-decoration: none; font-size: 12px; color: #4f46e5; font-weight: 600; display: inline-flex; align-items: center;">
                                    <span class="dashicons dashicons-plus-alt2" style="font-size: 14px; width: 14px; height: 14px; margin-right: 2px; margin-top: 1px;"></span> Tambah Seller/Supplier/Provider Baru
                                </a>
                            </div>
                            <select name="seller_id" class="wrpm-select wrpm-select2" style="width: 100%;">
                                <option value="">-- Pilih Seller/Supplier/Provider --</option>
                                <?php foreach ($sellers as $s): ?>
                                    <option value="<?php echo esc_attr($s['id']); ?>" <?php echo $row && $row['seller_id'] === $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo esc_html($s['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Harga Reseller (Modal)</label>
                            <input type="number" name="reseller_price" class="wrpm-input" value="<?php echo $row ? esc_attr($row['reseller_price']) : '0'; ?>" min="0" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Harga Jual</label>
                            <input type="number" name="sale_price" class="wrpm-input" value="<?php echo $row ? esc_attr($row['sale_price']) : '0'; ?>" min="0" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Durasi Masa Aktif (Hari)</label>
                            <input type="number" name="duration_days" class="wrpm-input" value="<?php echo $row ? esc_attr($row['duration_days']) : '30'; ?>" min="1" required />
                        </div>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Deskripsi Singkat</label>
                        <textarea name="description" class="wrpm-input" rows="3"><?php echo $row ? esc_textarea($row['description']) : ''; ?></textarea>
                    </div>

                    <div class="wrpm-form-group wrpm-mt-1">
                        <label class="wrpm-label">Catatan Internal</label>
                        <textarea name="notes" class="wrpm-input" rows="2"><?php echo $row ? esc_textarea($row['notes']) : ''; ?></textarea>
                    </div>

                    <div class="wrpm-form-actions wrpm-mt-2">
                        <button type="submit" class="wrpm-btn wrpm-btn-primary">Simpan Konfigurasi</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- List Page -->
        <div class="wrpm-header">
            <div>
                <h1>Daftar Harga Produk</h1>
                <p class="wrpm-subtitle">Daftar harga retail, reseller, dan masa durasi aktif produk.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-primary" href="<?php echo admin_url('admin.php?page=wrpm-product-prices&action=add'); ?>">
                    <span class="dashicons dashicons-plus"></span> Tambah Baru
                </a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <?php if (empty($rows)): ?>
                    <div class="wrpm-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <p>Belum ada konfigurasi produk. Mulai dengan membuat harga master baru.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                        <input type="text" class="wrpm-input wrpm-table-search" placeholder="Cari data..." style="max-width: 300px; width: 100%;" />
                    </div>
                    <table class="wrpm-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Tags</th>
                                <th>Keterangan</th>
                                <th>Seller/Supplier/Provider</th>
                                <th>Durasi</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code><?php echo esc_html(substr($r['id'], 0, 8)); ?></code></td>
                                    <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                                    <td><span class="wrpm-badge wrpm-badge-secondary"><?php echo esc_html($r['category'] ?: 'Umum'); ?></span></td>
                                    <td>
                                        <?php
                                        if (!empty($r['tags'])) {
                                            $tags_array = array_map('trim', explode(',', $r['tags']));
                                            foreach ($tags_array as $t) {
                                                echo '<span class="wrpm-badge" style="margin-right: 4px; background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;">' . esc_html($t) . '</span>';
                                            }
                                        } else {
                                            echo '<span class="wrpm-text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="#" class="wrpm-view-detail" 
                                           data-name="<?php echo esc_attr($r['name']); ?>" 
                                           data-description="<?php echo esc_attr(wp_strip_all_tags($r['description'])); ?>" 
                                           data-notes="<?php echo esc_attr(wp_strip_all_tags($r['notes'])); ?>" 
                                           style="text-decoration: none; color: #4338ca; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #e0e7ff; border-radius: 6px; border: 1px solid #c7d2fe; transition: all 0.2s;"
                                           title="Lihat Detail">
                                            <span class="dashicons dashicons-visibility" style="font-size: 18px; width: 18px; height: 18px;"></span>
                                        </a>
                                    </td>
                                    <td>
                                         <?php if (!empty($r['seller_name'])): ?>
                                             <a href="#" class="wrpm-view-seller-detail" 
                                                data-name="<?php echo esc_attr($r['seller_name']); ?>"
                                                data-email="<?php echo esc_attr($r['seller_email'] ?: '-'); ?>"
                                                data-phone="<?php echo esc_attr($r['seller_phone'] ?: '-'); ?>"
                                                data-telegram="<?php echo esc_attr($r['seller_telegram'] ?: '-'); ?>"
                                                data-whatsapp="<?php echo esc_attr($r['seller_whatsapp'] ?: '-'); ?>"
                                                style="text-decoration: none; color: #4f46e5; font-weight: 600; border-bottom: 1px dashed #4f46e5; padding-bottom: 2px;"
                                                title="Lihat Detail Seller">
                                                 <?php echo esc_html($r['seller_name']); ?>
                                             </a>
                                         <?php else: ?>
                                             <span class="wrpm-text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                    <td><?php echo esc_html($r['duration_days']); ?> Hari</td>
                                    <td>Rp <?php echo number_format_i18n($r['reseller_price'], 0); ?></td>
                                    <td>Rp <?php echo number_format_i18n($r['sale_price'], 0); ?></td>
                                    <td>
                                        <div class="wrpm-row-actions">
                                            <a class="wrpm-btn-link" href="<?php echo admin_url('admin.php?page=wrpm-product-prices&action=edit&id=' . $r['id']); ?>">
                                                <span class="dashicons dashicons-edit"></span> Edit
                                            </a>
                                            <a class="wrpm-btn-link wrpm-btn-link-danger" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_price&id=' . $r['id']), 'wrpm_delete_price_' . $r['id']); ?>" onclick="return confirm('Hapus master harga ini?');">
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

<!-- Modal Popup Detail Produk -->
<div id="wrpmDetailModal" class="wrpm-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="wrpm-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 550px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out;">
        <div class="wrpm-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="wrpmModalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Detail Produk</h3>
            <span class="wrpm-modal-close" style="color: #94a3b8; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</span>
        </div>
        <div class="wrpm-modal-body" style="padding: 24px; color: #334155; font-size: 0.95rem; line-height: 1.6;">
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 8px 0; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Deskripsi Produk</h4>
                <div id="wrpmModalDescription" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 12px; border-radius: 8px; min-height: 40px; white-space: pre-wrap;">-</div>
            </div>
            <div>
                <h4 style="margin: 0 0 8px 0; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Catatan Internal</h4>
                <div id="wrpmModalNotes" style="background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 8px; min-height: 40px; color: #92400e; white-space: pre-wrap;">-</div>
            </div>
        </div>
        <div class="wrpm-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button class="wrpm-btn wrpm-btn-secondary wrpm-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Popup Detail Seller -->
<div id="wrpmSellerModal" class="wrpm-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="wrpm-modal-content" style="background-color: #ffffff; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; animation: wrpmFadeIn 0.25s ease-out;">
        <div class="wrpm-modal-header" style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a; display: flex; align-items: center;">
                <span class="dashicons dashicons-businessman" style="margin-right: 8px; color: #4f46e5; font-size: 20px; width: 20px; height: 20px;"></span>
                Detail Seller
            </h3>
            <span class="wrpm-seller-modal-close" style="color: #94a3b8; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; transition: color 0.2s;">&times;</span>
        </div>
        <div class="wrpm-modal-body" style="padding: 24px; color: #334155; font-size: 0.95rem; line-height: 1.6;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b; width: 35%;">Nama Seller</td>
                    <td id="wrpmSellerName" style="padding: 10px 0; color: #0f172a; font-weight: 600;">-</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">Email</td>
                    <td id="wrpmSellerEmail" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">Telepon</td>
                    <td id="wrpmSellerPhone" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">Telegram</td>
                    <td id="wrpmSellerTelegram" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; font-weight: 600; color: #64748b;">WhatsApp</td>
                    <td id="wrpmSellerWhatsapp" style="padding: 10px 0; color: #0f172a;">-</td>
                </tr>
            </table>
        </div>
        <div class="wrpm-modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
            <button class="wrpm-btn wrpm-btn-secondary wrpm-seller-modal-close-btn" style="cursor: pointer; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-weight: 500;">Tutup</button>
        </div>
    </div>
</div>

<style>
@keyframes wrpmFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.wrpm-modal-close:hover, .wrpm-seller-modal-close:hover {
    color: #475569 !important;
}
.wrpm-view-detail:hover {
    background: #c7d2fe !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
.wrpm-view-seller-detail:hover {
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

