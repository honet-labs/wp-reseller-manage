<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrpm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="wrpm-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Harga Produk' : 'Tambah Harga Produk'; ?></h1>
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
                            <label class="wrpm-label">Kategori</label>
                            <input type="text" name="category" class="wrpm-input" value="<?php echo $row ? esc_attr($row['category']) : ''; ?>" />
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
                            <label class="wrpm-label">Seller Pendukung</label>
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
                <h1>Daftar Master Harga Produk</h1>
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
                    <table class="wrpm-table">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Seller</th>
                                <th>Durasi</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                                    <td><span class="wrpm-badge wrpm-badge-secondary"><?php echo esc_html($r['category'] ?: 'Umum'); ?></span></td>
                                    <td><?php echo esc_html($r['seller_name'] ?: '-'); ?></td>
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
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
