<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrpm-wrap">
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add / Edit Page -->
        <div class="wrpm-header">
            <div>
                <h1><?php echo $action === 'edit' ? 'Edit Data Customer' : 'Tambah Customer Baru'; ?></h1>
                <p class="wrpm-subtitle">Daftarkan profil kontak customer untuk pemetaan notifikasi reminder.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-secondary" href="<?php echo admin_url('admin.php?page=wrpm-customers'); ?>">Kembali</a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('wrpm_save_customer'); ?>
                    <input type="hidden" name="action" value="wrpm_save_customer" />
                    <?php if ($row): ?>
                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>" />
                    <?php endif; ?>

                    <div class="wrpm-form-grid">
                        <div class="wrpm-form-group">
                            <label class="wrpm-label">Nama Lengkap <span class="wrpm-required">*</span></label>
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
                            <label class="wrpm-label">ID Telegram / Chat ID</label>
                            <input type="text" name="telegram" class="wrpm-input" value="<?php echo $row ? esc_attr($row['telegram']) : ''; ?>" placeholder="Contoh: 123456789 atau nama username" />
                        </div>

                        <div class="wrpm-form-group">
                            <label class="wrpm-label">WhatsApp (Format Internasional)</label>
                            <input type="text" name="whatsapp" class="wrpm-input" value="<?php echo $row ? esc_attr($row['whatsapp']) : ''; ?>" placeholder="Contoh: 628123456789" />
                        </div>
                    </div>

                    <div class="wrpm-form-actions wrpm-mt-2">
                        <button type="submit" class="wrpm-btn wrpm-btn-primary">Simpan Profil Customer</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- List Page -->
        <div class="wrpm-header">
            <div>
                <h1>Daftar Data Customer</h1>
                <p class="wrpm-subtitle">Manajemen data customer terdaftar dan detail kontak notifikasi mereka.</p>
            </div>
            <div class="wrpm-actions">
                <a class="wrpm-btn wrpm-btn-primary" href="<?php echo admin_url('admin.php?page=wrpm-customers&action=add'); ?>">
                    <span class="dashicons dashicons-plus"></span> Tambah Customer
                </a>
            </div>
        </div>

        <div class="wrpm-card wrpm-mt-2">
            <div class="wrpm-card-body">
                <?php if (empty($rows)): ?>
                    <div class="wrpm-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <p>Belum ada data customer terdaftar.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                        <input type="text" class="wrpm-input wrpm-table-search" placeholder="Cari data..." style="max-width: 300px; width: 100%;" />
                    </div>
                    <table class="wrpm-table">
                        <thead>
                            <tr>
                                <th>Nama Customer</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Telegram Chat ID</th>
                                <th>WhatsApp</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                                    <td><?php echo esc_html($r['email'] ?: '-'); ?></td>
                                    <td><?php echo esc_html($r['phone'] ?: '-'); ?></td>
                                    <td><code><?php echo esc_html($r['telegram'] ?: '-'); ?></code></td>
                                    <td>
                                        <?php if ($r['whatsapp']): ?>
                                            <a href="https://wa.me/<?php echo esc_attr($r['whatsapp']); ?>" target="_blank" class="wrpm-whatsapp-link">
                                                <span class="dashicons dashicons-whatsapp"></span> <?php echo esc_html($r['whatsapp']); ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="wrpm-row-actions">
                                            <a class="wrpm-btn-link" href="<?php echo admin_url('admin.php?page=wrpm-customers&action=edit&id=' . $r['id']); ?>">
                                                <span class="dashicons dashicons-edit"></span> Edit
                                            </a>
                                            <a class="wrpm-btn-link wrpm-btn-link-danger" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_customer&id=' . $r['id']), 'wrpm_delete_customer_' . $r['id']); ?>" onclick="return confirm('Hapus customer ini? Semua data produk aktif terkait mungkin akan terdampak.');">
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
