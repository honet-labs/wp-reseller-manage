<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html('Harga Produk', '[wrpm_price_table]'); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="get" class="fl-grid" style="grid-template-columns: 1fr 200px 120px; gap: 12px; align-items: end;">
        <input type="hidden" name="page" value="wrpm-product-prices" />
        <div>
          <label>Cari</label>
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" class="regular-text" placeholder="nama / tags / seller / harga / description" />
        </div>
        <div>
          <label>Kategori</label>
          <select name="cat">
            <option value="">Semua</option>
            <?php foreach ((array)$cats as $c): ?>
              <option value="<?php echo esc_attr($c); ?>" <?php selected($cat, $c); ?>><?php echo esc_html($c); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <button class="button button-primary">Filter</button>
        </div>
      </form>

      <div class="fl-btnrow fl-mt">
        <a class="button button-primary" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-product-price-add')); ?>">Tambah Harga Produk</a>
        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_export_prices_csv'), 'wrpm_export_prices_csv')); ?>">Export CSV</a>
      </div>

      <div class="fl-mt">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>Durasi (hari)</th>
              <th>Kategori</th>
              <th>Tags</th>
              <th>Seller</th>
              <th>Kontak</th>
              <th>Harga Reseller</th>
              <th>Harga Jual</th>
              <th>Description</th>
              <th>Catatan</th>
              <th>Updated</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="13">Belum ada data.</td></tr>
            <?php else: foreach ((array)$rows as $r): ?>
              <tr>
                <td><code><?php echo esc_html($r['id']); ?></code></td>
                <td><strong><?php echo esc_html($r['name']); ?></strong></td>
                <td><?php echo esc_html((int)$r['duration_days']); ?></td>
                <td><?php echo esc_html($r['category']); ?></td>
                <td><?php echo esc_html($r['tags']); ?></td>
                <td>
                  <?php if (!empty($r['seller_id'])): ?>
                    <?php echo esc_html($r['seller_name'] ?: $r['seller_id']); ?>
                  <?php else: ?><span class="fl-muted">-</span><?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($r['seller_id'])):
                    $phone = trim((string)($r['seller_phone'] ?? ''));
                    $wa = trim((string)($r['seller_whatsapp'] ?? ''));
                    $tg = trim((string)($r['seller_telegram'] ?? ''));
                    $email = trim((string)($r['seller_email'] ?? ''));

                    $lines = [];
                    if ($phone !== '') {
                      $tel_href = 'tel:' . preg_replace('/[^0-9\+]/', '', $phone);
                      $lines[] = 'Telp: <a href="' . esc_url($tel_href) . '">' . esc_html($phone) . '</a>';
                    }
                    if ($wa !== '') {
                      $wa_digits = preg_replace('/\D+/', '', $wa);
                      if (strpos($wa_digits, '0') === 0) $wa_digits = '62' . substr($wa_digits, 1);
                      $wa_href = 'https://wa.me/' . rawurlencode($wa_digits);
                      $lines[] = 'WA: <a href="' . esc_url($wa_href) . '" target="_blank" rel="noopener">' . esc_html($wa) . '</a>';
                    }
                    if ($email !== '') {
                      $lines[] = 'Email: <a href="' . esc_url('mailto:' . $email) . '">' . esc_html($email) . '</a>';
                    }
                    if ($tg !== '') {
                      $tg_user = ltrim($tg, '@');
                      $tg_href = 'https://t.me/' . rawurlencode($tg_user);
                      $lines[] = 'TG: <a href="' . esc_url($tg_href) . '" target="_blank" rel="noopener">' . esc_html($tg) . '</a>';
                    }

                    $html = $lines ? implode('<br>', $lines) : '';
                  ?>
                    <?php echo $html ? wp_kses($html, ['a' => ['href' => [], 'target' => [], 'rel' => []], 'br' => []]) : '<span class="fl-muted">-</span>'; ?>
                  <?php else: ?><span class="fl-muted">-</span><?php endif; ?>
                </td>
                <td><?php echo esc_html($this->wrpm_money_idr((float)$r['reseller_price'])); ?></td>
                <td><?php echo esc_html($this->wrpm_money_idr((float)$r['sale_price'])); ?></td>
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
                <td><?php echo esc_html($r['updated_at']); ?></td>
                <td>
                  <a class="button button-small" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-product-price-add', ['id' => $r['id']])); ?>">Edit</a>
                  <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_delete_price&id=' . rawurlencode($r['id'])), 'wrpm_delete_price_' . $r['id'])); ?>" onclick="return confirm('Hapus data ini?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
