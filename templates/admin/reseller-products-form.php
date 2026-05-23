<?php if (!defined('ABSPATH')) { exit; }
$is_edit = !empty($row);
$attachments = $is_edit ? $this->wrpm_json_decode_assoc($row['payment_attachments'] ?? '') : [];
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php $page_info = 'Tips: gunakan <strong>Export CSV</strong> sebagai template sebelum melakukan <strong>Import CSV</strong>. Import tersedia di halaman Tambah/Edit.'; echo $this->page_header_html($is_edit ? 'Edit Produk Reseller' : 'Tambah Produk Reseller', '', '', $page_info); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('wrpm_save_reseller_product'); ?>
        <input type="hidden" name="action" value="wrpm_save_reseller_product" />
        <input type="hidden" name="id" value="<?php echo esc_attr($row['id'] ?? ''); ?>" />

        <table class="form-table" role="presentation">
          <tr>
            <th><label>Nama Produk</label></th>
            <td>
              <select name="price_id" id="wrpm_price_id" required>
                <option value="">-- pilih dari Harga Produk --</option>
                <?php foreach ((array)$prices as $p):
                  $seller_name = trim((string)($p['seller_name'] ?? ''));
                  if ($seller_name === '') $seller_name = 'N/A';
                  $label = $p['name'] . ' - ' . (int)$p['duration_days'] . ' hari - ' . $seller_name;
                ?>
                  <option value="<?php echo esc_attr($p['id']); ?>" data-duration="<?php echo esc_attr((int)$p['duration_days']); ?>" data-price="<?php echo esc_attr((int)$p['reseller_price']); ?>" <?php selected(($row['price_id'] ?? ''), $p['id']); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">Field produk akan otomatis mengikuti data dari Harga Produk.</p>
            </td>
          </tr>
          <tr>
            <th><label>Reseller (dari menu Seller)</label></th>
            <td>
              <select name="seller_id" id="wrpm_seller_id">
                <option value="">-- pilih reseller (opsional) --</option>
                <?php foreach ((array)($sellers ?? []) as $s):
                  $lbl = $s['name'] . ' (' . ($s['whatsapp'] ?: ($s['email'] ?: $s['phone'])) . ')';
                  $parts = [];
                  if (!empty($s['phone'])) $parts[] = 'Telp: ' . $s['phone'];
                  if (!empty($s['whatsapp'])) $parts[] = 'WA: ' . $s['whatsapp'];
                  if (!empty($s['telegram'])) $parts[] = 'TG: ' . $s['telegram'];
                  if (!empty($s['email'])) $parts[] = 'Email: ' . $s['email'];
                  $contact = implode(' | ', $parts);
                ?>
                  <option value="<?php echo esc_attr($s['id']); ?>" data-name="<?php echo esc_attr($s['name']); ?>" data-contact="<?php echo esc_attr($contact); ?>" <?php selected(($row['seller_id'] ?? ''), $s['id']); ?>><?php echo esc_html($lbl); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">Jika dipilih, Nama & Kontak reseller akan otomatis terisi.</p>
            </td>
          </tr>
          <tr>
            <th><label>Nama Reseller</label></th>
            <td><input type="text" name="reseller_name" id="wrpm_reseller_name" class="regular-text" value="<?php echo esc_attr($row['reseller_name'] ?? ''); ?>" /></td>
          </tr>
          <tr>
            <th><label>Kontak Reseller</label></th>
            <td><input type="text" name="reseller_contact" id="wrpm_reseller_contact" class="regular-text" value="<?php echo esc_attr($row['reseller_contact'] ?? ''); ?>" placeholder="telp/telegram/wa" /></td>
          </tr>
          <tr>
            <th><label>Tanggal Pembelian</label></th>
            <td><input type="date" name="purchase_date" value="<?php echo esc_attr($row['purchase_date'] ?? $this->wrpm_today_date()); ?>" /></td>
          </tr>
          <tr>
            <th><label>Durasi Produk (hari)</label></th>
            <td>
              <input type="number" id="wrpm_duration_display" value="<?php echo esc_attr((int)($row['duration_days'] ?? 0)); ?>" disabled />
              <p class="description">Durasi mengikuti data dari Harga Produk.</p>
            </td>
          </tr>
          <tr>
            <th><label>Harga Produk (Reseller)</label></th>
            <td><input type="number" name="price" value="<?php echo esc_attr((int)($row['price'] ?? 0)); ?>" /></td>
          </tr>
          <tr>
            <th><label>Status Pembayaran</label></th>
            <td>
              <select name="payment_status">
                <option value="pending" <?php selected(($row['payment_status'] ?? 'pending'), 'pending'); ?>>pending</option>
                <option value="paid" <?php selected(($row['payment_status'] ?? 'pending'), 'paid'); ?>>paid</option>
              </select>
            </td>
          </tr>
          <tr>
            <th><label>Bukti Pembayaran</label></th>
            <td>
              <?php if (!empty($attachments)): ?>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                  <?php foreach ($attachments as $aid):
                    $url = wp_get_attachment_url((int)$aid);
                    if (!$url) continue;
                    $thumb = wp_get_attachment_image((int)$aid, [80,80], true);
                  ?>
                    <div style="border:1px solid #ddd; padding:8px; border-radius:8px;">
                      <div><?php echo $thumb ? $thumb : '<code>'.esc_html($aid).'</code>'; ?></div>
                      <div style="margin-top:6px; display:flex; gap:6px;">
                        <a class="button button-small" href="<?php echo esc_url($url); ?>" target="_blank">View</a>
                        <label style="display:inline-flex; gap:6px; align-items:center;">
                          <input type="checkbox" name="existing_attachments[]" value="<?php echo esc_attr((int)$aid); ?>" checked /> keep
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <p class="description">Hilangkan centang <em>keep</em> untuk menghapus attachment dari record (file media tidak dihapus).</p>
              <?php endif; ?>

              <div class="fl-filepicker" style="margin-top:10px;">
                <button type="button" class="button" data-fl-file-trigger="wrpm_payment_attachments">Pilih File</button>
                <span class="fl-file-label">Tidak ada file yang dipilih</span>
                <input id="wrpm_payment_attachments" class="fl-hidden-file" type="file" name="payment_attachments[]" multiple accept="image/*" />
              </div>
            </td>
          </tr>
          <tr>
            <th><label>Catatan Tambahan</label></th>
            <td><textarea name="notes" class="large-text" rows="4"><?php echo esc_textarea($row['notes'] ?? ''); ?></textarea></td>
          </tr>
        </table>

        <p>
          <button class="button button-primary" type="submit">Simpan</button>
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-reseller-products')); ?>">Kembali</a>
        </p>
      </form>

      <?php if ($is_edit): ?>
        <hr />
        <p class="description">Expired Produk (otomatis): <?php echo esc_html($row['expires_at'] ?? ''); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="fl-card fl-mt">
  <div class="fl-card-head"><h2>Import CSV Produk Reseller</h2></div>
  <div class="fl-card-body">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <?php wp_nonce_field('wrpm_import_reseller_products_csv'); ?>
      <input type="hidden" name="action" value="wrpm_import_reseller_products_csv" />
      <input type="file" name="csv_file" accept=".csv,text/csv" required />
      <button class="button">Import CSV</button>
    </form>
  </div>
</div>

<script>
(function(){
  function syncDuration(){
    var sel = document.getElementById('wrpm_price_id');
    var out = document.getElementById('wrpm_duration_display');
    if(!sel || !out) return;
    var opt = sel.options[sel.selectedIndex];
    var d = opt && opt.getAttribute('data-duration');
    out.value = d ? String(parseInt(d,10) || 0) : '0';
  }
  function syncSeller(){
    var sel = document.getElementById('wrpm_seller_id');
    if(!sel) return;
    var opt = sel.options[sel.selectedIndex];
    var name = opt && opt.getAttribute('data-name');
    var contact = opt && opt.getAttribute('data-contact');
    var n = document.getElementById('wrpm_reseller_name');
    var c = document.getElementById('wrpm_reseller_contact');
    if(n && name) n.value = name;
    if(c && contact) c.value = contact;
  }
  document.addEventListener('change', function(e){
    if(e.target && e.target.id === 'wrpm_price_id') syncDuration();
    if(e.target && e.target.id === 'wrpm_seller_id') syncSeller();
  });
  // init
  syncDuration();
})();
</script>
