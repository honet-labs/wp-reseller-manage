<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php $page_info = 'Reminder otomatis dikirim oleh cron harian (sesuai pengaturan di Settings) untuk reminder <code>pending</code> yang <code>reminder_date</code> &lt;= hari ini. Gunakan filter <strong>Jatuh tempo</strong> agar list hanya menampilkan reminder yang memang perlu dikirim saat ini.'; echo $this->page_header_html('Reminder Produk Aktif', '', '', $page_info); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="page" value="wrpm-reminders" />
        <div>
          <label>Cari</label><br/>
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" class="regular-text" placeholder="produk/customer" />
        </div>
        <div>
          <label>Offset (H-)</label><br/>
          <select name="off">
            <option value="">Semua</option>
            <option value="7" <?php selected((string)$off,'7'); ?>>7 hari</option>
            <option value="3" <?php selected((string)$off,'3'); ?>>3 hari</option>
            <option value="1" <?php selected((string)$off,'1'); ?>>1 hari</option>
          </select>
        </div>
        <div style="min-width:260px;">
          <label>Tampilkan</label><br/>
          <input type="hidden" name="due" value="0" />
          <label style="display:inline-flex; gap:6px; align-items:center; margin-right:12px;">
            <input type="checkbox" name="due" value="1" <?php checked((int)$due, 1); ?> />
            Jatuh tempo (<= hari ini)
          </label>

          <input type="hidden" name="sent" value="0" />
          <label style="display:inline-flex; gap:6px; align-items:center;">
            <input type="checkbox" name="sent" value="1" <?php checked((int)$sent, 1); ?> />
            Termasuk terkirim
          </label>
        </div>
        <div><button class="button button-primary">Filter</button></div>
      </form>

      <div class="fl-mt">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>Reminder Date</th>
              <th>Produk</th>
              <th>Customer</th>
              <th>Expired</th>
              <th>Sisa</th>
              <th>Sent Via</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="7">Belum ada data.</td></tr>
            <?php else: foreach ((array)$rows as $r): ?>
              <tr>
                <td><?php echo esc_html($r['reminder_date']); ?></td>
                <td><?php echo esc_html($r['product_label'] ?? ''); ?></td>
                <td><?php echo esc_html($r['customer_name'] ?? ''); ?><br/><span class="fl-muted"><?php echo esc_html($r['customer_contact'] ?? ''); ?></span></td>
                <td><?php echo esc_html($r['expires_at'] ?? ''); ?></td>
                <td><strong><?php echo esc_html((int)($r['remaining_days'] ?? 0)); ?></strong> hari</td>
                <td>
                  <?php if (($r['status'] ?? '') === 'sent'): ?>
                    <span class="fl-pill fl-pill-green">terkirim</span>
                    <span class="fl-muted"><?php echo esc_html($r['sent_via'] ?? ''); ?></span><br/>
                    <span class="fl-muted"><?php echo esc_html($r['sent_at'] ?? ''); ?></span>
                  <?php else: ?>
                    <span class="fl-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $s = $this->wrpm_get_settings();
                    $vars = [
                      'customer_name' => (string)($r['customer_name'] ?? ''),
                      'product_label' => (string)($r['product_label'] ?? ''),
                      'start_date' => (string)($r['start_date'] ?? ''),
                      'duration_days' => (string)($r['duration_days'] ?? ''),
                      'expires_at' => (string)($r['expires_at'] ?? ''),
                      'price' => $this->wrpm_money_idr((float)($r['price'] ?? 0)),
                      'remaining_days' => (string)((int)($r['remaining_days'] ?? 0)),
                    ];
                    $wa_text = $this->wrpm_render_template((string)($s['whatsapp_template'] ?? ''), $vars);
                    $wa_num = preg_replace('/[^0-9]/', '', (string)($r['customer_whatsapp'] ?? ''));
                    $wa_url = $wa_num ? ('https://wa.me/' . $wa_num . '?text=' . rawurlencode($wa_text)) : '';
                  ?>
                  <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wrpm_send_reminder_manual&id=' . rawurlencode($r['id'])), 'wrpm_send_reminder_' . $r['id'])); ?>">Kirim Email/TG</a>
                  <?php if ($wa_url): ?>
                    <a class="button button-small" href="<?php echo esc_url($wa_url); ?>" target="_blank">WA</a>
                    <button type="button" class="button button-small wrpm-copy-wa" data-text="<?php echo esc_attr($wa_text); ?>">Copy WA</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
