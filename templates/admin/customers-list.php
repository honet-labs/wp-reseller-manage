<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html('Customer'); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="page" value="wrpm-customers" />
        <div>
          <label>Cari</label><br/>
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" class="regular-text" placeholder="nama/email/kontak" />
        </div>
        <div><button class="button button-primary">Filter</button></div>
      </form>

      <div class="fl-btnrow fl-mt">
        <a class="button button-primary" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-customer-add')); ?>">Tambah Customer</a>
        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_export_customers_csv'), 'wrpm_export_customers_csv')); ?>">Export CSV</a>
      </div>

      <div class="fl-mt">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Telp</th>
              <th>Telegram</th>
              <th>Whatsapp</th>
              <th>Updated</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="8">Belum ada data.</td></tr>
            <?php else: foreach ((array)$rows as $r): ?>
              <tr>
                <td><code><?php echo esc_html($r['id']); ?></code></td>
                <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                <td><?php echo esc_html($r['email']); ?></td>
                <td><?php echo esc_html($r['phone']); ?></td>
                <td><?php echo esc_html($r['telegram']); ?></td>
                <td><?php echo esc_html($r['whatsapp']); ?></td>
                <td><?php echo esc_html($r['updated_at']); ?></td>
                <td>
                  <a class="button button-small" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-customer-add', ['id' => $r['id']])); ?>">Edit</a>
                  <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_customer&id=' . rawurlencode($r['id'])), 'wrpm_delete_customer_' . $r['id'])); ?>" onclick="return confirm('Hapus data ini?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
