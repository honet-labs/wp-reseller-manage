<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html('Produk Reseller'); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="page" value="wrpm-reseller-products" />
        <div>
          <label>Cari</label><br/>
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" class="regular-text" placeholder="produk / reseller" />
        </div>
        <div>
          <label>Status Pembayaran</label><br/>
          <select name="pay">
            <option value="">Semua</option>
            <option value="paid" <?php selected($pay,'paid'); ?>>paid</option>
            <option value="pending" <?php selected($pay,'pending'); ?>>pending</option>
          </select>
        </div>
        <div>
          <button class="button button-primary">Filter</button>
        </div>
      </form>

      <div class="fl-btnrow fl-mt">
        <a class="button button-primary" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-reseller-product-add')); ?>">Tambah Produk Reseller</a>
        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_export_reseller_products_csv'), 'wrpm_export_reseller_products_csv')); ?>">Export CSV</a>
      </div>

      <div class="fl-mt">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Produk</th>
              <th>Reseller</th>
              <th>Durasi</th>
              <th>Expired</th>
              <th>Sisa Durasi</th>
              <th>Pembayaran</th>
              <th>Bukti</th>
              <th>Description</th>
              <th>Catatan</th>
              <th>Harga</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="12">Belum ada data.</td></tr>
            <?php else: foreach ((array)$rows as $r): ?>
              <tr>
                <td><code><?php echo esc_html($r['id']); ?></code></td>
                <td>
                  <strong><?php echo esc_html($r['product_name']); ?></strong><br/>
                  <span class="fl-muted"><?php echo esc_html($r['category']); ?></span>
                </td>
                <td><?php echo esc_html($r['reseller_name']); ?><br/><span class="fl-muted"><?php echo esc_html($r['reseller_contact']); ?></span></td>
                <td><?php echo esc_html((int)$r['duration_days']); ?> hari</td>
                <td><?php echo esc_html($r['expires_at']); ?></td>
                <td>
                  <?php
                    $rem = $this->wrpm_date_diff_days($this->wrpm_today_date(), (string)($r['expires_at'] ?? ''));
                    if ($rem < 0) $rem = 0;
                  ?>
                  <?php echo esc_html((int)$rem); ?> hari
                </td>
                <td><?php echo esc_html($r['payment_status']); ?></td>
                <td>
                  <?php
                    $aids = $this->wrpm_json_decode_assoc($r['payment_attachments'] ?? '');
                    $urls = [];
                    foreach ((array)$aids as $aid) {
                      $u = wp_get_attachment_url((int)$aid);
                      if ($u) $urls[] = $u;
                    }
                    if (!empty($urls)):
                  ?>
                    <button type="button" class="button button-small wrpm-view-images" data-title="Bukti Pembayaran" data-urls="<?php echo esc_attr(wp_json_encode($urls)); ?>">View (<?php echo esc_html(count($urls)); ?>)</button>
                  <?php else: ?>
                    <span class="fl-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $desc = (string)($r['description'] ?? ''); ?>
                  <?php if (trim($desc) !== ''): ?>
                    <button type="button" class="button button-small wrpm-view-text" data-title="Description" data-text="<?php echo esc_attr($desc); ?>">View</button>
                  <?php else: ?><span class="fl-muted">-</span><?php endif; ?>
                </td>
                <td>
                  <?php $notes = (string)($r['notes'] ?? ''); ?>
                  <?php if (trim($notes) !== ''): ?>
                    <button type="button" class="button button-small wrpm-view-text" data-title="Catatan" data-text="<?php echo esc_attr($notes); ?>">View</button>
                  <?php else: ?><span class="fl-muted">-</span><?php endif; ?>
                </td>
                <td><?php echo esc_html($this->wrpm_money_idr((float)$r['price'])); ?></td>
                <td>
                  <a class="button button-small" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-reseller-product-add', ['id' => $r['id']])); ?>">Edit</a>
                  <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_reseller_product&id=' . rawurlencode($r['id'])), 'wrpm_delete_reseller_product_' . $r['id'])); ?>" onclick="return confirm('Hapus data ini?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
