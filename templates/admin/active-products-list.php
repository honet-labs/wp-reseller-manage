<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html('Produk Aktif'); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="page" value="wrpm-active-products" />
        <div>
          <label>Cari</label><br/>
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" class="regular-text" placeholder="produk/customer" />
        </div>
        <div>
          <label>Status</label><br/>
          <select name="st">
            <option value="">Semua</option>
            <option value="active" <?php selected($st,'active'); ?>>active</option>
            <option value="expired" <?php selected($st,'expired'); ?>>expired</option>
          </select>
        </div>
        <div style="display:flex; gap:8px; align-items:end;">
          <button class="button button-primary">Filter</button>
          <a class="button button-primary" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-active-product-add')); ?>">Tambah Produk Aktif</a>
          <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_export_active_products_csv'), 'wrpm_export_active_products_csv')); ?>">Export CSV</a>
        </div>
      </form>

      <div class="fl-mt">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Produk</th>
              <th>Customer</th>
              <th>Mulai</th>
              <th>Durasi</th>
              <th>Expired</th>
              <th>Sisa Durasi</th>
              <th>Status</th>
              <th>Pembayaran</th>
              <th>Bukti</th>
              <th>Catatan</th>
              <th>Harga</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="13">Belum ada data.</td></tr>
            <?php else: foreach ((array)$rows as $r): ?>
              <tr>
                <td><code><?php echo esc_html($r['id']); ?></code></td>
                <td><strong><?php echo esc_html($r['product_label']); ?></strong></td>
                <td><?php echo esc_html($r['customer_name']); ?><br/><span class="fl-muted"><?php echo esc_html($r['customer_contact']); ?></span></td>
                <td><?php echo esc_html($r['start_date']); ?></td>
                <td><?php echo esc_html((int)$r['duration_days']); ?> hari</td>
                <td><?php echo esc_html($r['expires_at']); ?></td>
                <td>
                  <?php
                    $rem = $this->wrpm_date_diff_days($this->wrpm_today_date(), (string)($r['expires_at'] ?? ''));
                    if ($rem < 0) $rem = 0;
                  ?>
                  <?php echo esc_html((int)$rem); ?> hari
                </td>
                <td>
                  <?php if ($r['status'] === 'active'): ?>
                    <span class="fl-pill fl-pill-green">active</span>
                  <?php else: ?>
                    <span class="fl-pill fl-pill-red">expired</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $ps = (string)($r['payment_status'] ?? 'pending'); ?>
                  <?php if ($ps === 'paid'): ?>
                    <span class="fl-pill fl-pill-green">paid</span>
                  <?php else: ?>
                    <span class="fl-pill fl-pill-red">pending</span>
                  <?php endif; ?>
                </td>
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
                  <?php $notes = (string)($r['notes'] ?? ''); ?>
                  <?php if (trim($notes) !== ''): ?>
                    <button type="button" class="button button-small wrpm-view-text" data-title="Catatan" data-text="<?php echo esc_attr($notes); ?>">View</button>
                  <?php else: ?><span class="fl-muted">-</span><?php endif; ?>
                </td>
                <td><?php echo esc_html($this->wrpm_money_idr((float)$r['price'])); ?></td>
                <td>
                  <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_invoice_pdf&id=' . rawurlencode($r['id'])), 'wrpm_invoice_pdf_' . $r['id'])); ?>">Invoice PDF</a>
                  <a class="button button-small" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-active-product-add', ['id' => $r['id']])); ?>">Edit</a>
                  <?php if ($r['status'] === 'expired'): ?>
                    <button type="button" class="button button-small wrpm-extend-btn" data-id="<?php echo esc_attr($r['id']); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wrpm_extend_active_product_' . $r['id'])); ?>">Tambah Durasi</button>
                  <?php endif; ?>
                  <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_active_product&id=' . rawurlencode($r['id'])), 'wrpm_delete_active_product_' . $r['id'])); ?>" onclick="return confirm('Hapus data ini?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <form id="wrpm-extend-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:none">
        <input type="hidden" name="action" value="wrpm_extend_active_product" />
        <input type="hidden" name="_wpnonce" value="" />
        <input type="hidden" name="id" value="" />
        <input type="hidden" name="days" value="" />
      </form>

    </div>
  </div>
</div>
