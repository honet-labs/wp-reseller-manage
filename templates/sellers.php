<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrpm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="wrpm-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Data Seller' : 'Tambah Seller Baru'; ?></h1>
                <p class="wrpm-subtitle">Daftarkan akun profil seller pembantu penjualan produk.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-secondary" href="<?php echo admin_url('admin.php?page=wrpm-sellers'); ?>">Kembali</a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('wrpm_save_seller'); ?>
                    <input type="hidden" name="action" value="wrpm_save_seller" />
                    <?php if ($row): ?>
                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>" />
                    <?php endif; ?>

                    <div class="wrpm-form-grid">
                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Nama Seller <span class="wrpm-required">*</span></label>
                            <input type="text" name="name" class="wrpm-input" value="<?php echo $row ? esc_attr($row['name']) : ''; ?>" required />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Email</label>
                            <input type="email" name="email" class="wrpm-input" value="<?php echo $row ? esc_attr($row['email']) : ''; ?>" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">No. Telepon</label>
                            <input type="text" name="phone" class="wrpm-input" value="<?php echo $row ? esc_attr($row['phone']) : ''; ?>" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Telegram Chat ID</label>
                            <input type="text" name="telegram" class="wrpm-input" value="<?php echo $row ? esc_attr($row['telegram']) : ''; ?>" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">WhatsApp (Format Internasional)</label>
                            <input type="text" name="whatsapp" class="wrpm-input" value="<?php echo $row ? esc_attr($row['whatsapp']) : ''; ?>" placeholder="Contoh: 628123456789" />
                        </div>
                    </div>

                    <div class="wrpm-form-actions wrpm-mt-2">
                        <button type="submit" class="wrpm-btn wrpm-btn-primary">Simpan Profil Seller</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- List Page -->
        <div class="wrpm-header">
            <div>
                <h1>Daftar Data Seller</h1>
                <p class="wrpm-subtitle">Manajemen daftar seller internal pendukung penjualan produk reseller.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-primary" href="<?php echo admin_url('admin.php?page=wrpm-sellers&action=add'); ?>">
                    <span class="dashicons dashicons-plus"></span> Tambah Seller
                </a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <?php if (empty($rows)): ?>
                    <div class="wrpm-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <p>Belum ada data seller pendukung yang didaftarkan.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                        <input type="text" class="wrpm-input wrpm-table-search" placeholder="Cari data..." style="max-width: 300px; width: 100%;" />
                    </div>
                    <table class="wrpm-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Seller</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Telegram</th>
                                <th>WhatsApp</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code><?php echo esc_html(substr($r['id'], 0, 8)); ?></code></td>
                                    <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                                    <td><?php echo esc_html($r['email'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($r['phone'] ?: '-'); ?></td>
                                    <td><code><?php echo esc_html($r['telegram'] ?: '-'); ?></code></td>
                                    <td><?php echo esc_html($r['whatsapp'] ?: '-'); ?></td>
                                    <td>
                                        <div class="wrpm-row-actions">
                                            <a class="wrpm-btn-link" href="<?php echo admin_url('admin.php?page=wrpm-sellers&action=edit&id=' . $r['id']); ?>">
                                                <span class="dashicons dashicons-edit"></span> Edit
                                            </a>
                                            <a class="wrpm-btn-link wrpm-btn-link-danger" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_seller&id=' . $r['id']), 'wrpm_delete_seller_' . $r['id']); ?>" onclick="return confirm('Hapus seller ini?');">
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
