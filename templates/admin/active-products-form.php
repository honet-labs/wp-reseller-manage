<?php if (!defined('ABSPATH')) { exit; }
$is_edit = !empty($row);
$attachments = $is_edit ? $this->wrpm_json_decode_assoc($row['payment_attachments'] ?? '') : [];
$has_customers = !empty($customers);
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php $page_info = 'Tips: gunakan <strong>Export CSV</strong> sebagai template sebelum melakukan <strong>Import CSV</strong>. Import tersedia di halaman Tambah/Edit.'; echo $this->page_header_html($is_edit ? 'Edit Produk Aktif' : 'Tambah Produk Aktif', '', '', $page_info); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('wrpm_save_active_product'); ?>
        <input type="hidden" name="action" value="wrpm_save_active_product" />
        <input type="hidden" name="id" value="<?php echo esc_attr($row['id'] ?? ''); ?>" />

        <table class="form-table" role="presentation">
          <tr>
            <th><label>Produk Reseller</label></th>
            <td>
              <select name="reseller_product_id" id="wrpm_reseller_product_id" required>
                <option value="">-- pilih produk reseller --</option>
                <?php foreach ((array)$resellers as $p):
                  $lbl = $p['product_name'] . ' (' . (int)$p['duration_days'] . ' hari) - ' . ($p['reseller_name'] ? $p['reseller_name'] : '');
                ?>
                  <option value="<?php echo esc_attr($p['id']); ?>" data-duration="<?php echo esc_attr((int)$p['duration_days']); ?>" data-price="<?php echo esc_attr((int)($p['price'] ?? 0)); ?>" <?php selected(($row['reseller_product_id'] ?? ''), $p['id']); ?>><?php echo esc_html($lbl); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">Nama produk aktif akan otomatis menjadi: <code>nama produk - durasi</code>.</p>
            </td>
          </tr>
          <tr>
            <th><label>Customer</label></th>
            <td>
              <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <select name="customer_id" id="wrpm_customer_id" data-has-customers="<?php echo $has_customers ? '1' : '0'; ?>" <?php echo $has_customers ? 'required' : ''; ?>>
                  <option value="">-- pilih customer --</option>
                  <?php foreach ((array)$customers as $c):
                    $cl = $c['name'] . ' (' . ($c['email'] ?: ($c['whatsapp'] ?: $c['phone'])) . ')';
                  ?>
                    <option value="<?php echo esc_attr($c['id']); ?>" <?php selected(($row['customer_id'] ?? ''), $c['id']); ?>><?php echo esc_html($cl); ?></option>
                  <?php endforeach; ?>
                </select>

                <label style="display:inline-flex; gap:8px; align-items:center;">
                  <input type="checkbox" id="wrpm_add_new_customer" name="add_new_customer" value="1" <?php checked(!$has_customers); ?> />
                  Tambah customer baru di sini
                </label>
              </div>
              <?php if (!$has_customers): ?>
                <p class="description" style="color:#b32d2e;">Belum ada customer. Silakan isi data customer baru di bawah.</p>
              <?php else: ?>
                <p class="description">Jika customer belum ada, centang <em>Tambah customer baru</em> untuk input langsung tanpa pindah menu.</p>
              <?php endif; ?>

              <div id="wrpm_new_customer_fields" style="margin-top:12px; padding:12px; border:1px solid #e5e5e5; border-radius:10px; background:#fff; display:none;">
                <h3 style="margin:0 0 10px;">Data Customer Baru</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; max-width:900px;">
                  <div>
                    <label for="wrpm_new_customer_name"><strong>Nama Customer</strong></label><br />
                    <input type="text" id="wrpm_new_customer_name" name="new_customer_name" class="regular-text" value="" />
                  </div>
                  <div>
                    <label><strong>Email</strong></label><br />
                    <input type="email" name="new_customer_email" class="regular-text" value="" />
                  </div>
                  <div>
                    <label><strong>Telepon</strong></label><br />
                    <input type="text" name="new_customer_phone" class="regular-text" value="" />
                  </div>
                  <div>
                    <label><strong>WhatsApp</strong></label><br />
                    <input type="text" name="new_customer_whatsapp" class="regular-text" value="" />
                  </div>
                  <div>
                    <label><strong>Telegram</strong></label><br />
                    <input type="text" name="new_customer_telegram" class="regular-text" value="" />
                  </div>
                </div>
                <p class="description">Customer akan otomatis dibuat saat kamu klik <strong>Simpan</strong>, lalu langsung dipakai untuk Produk Aktif ini.</p>
              </div>
            </td>
          </tr>
          <tr>
            <th><label>Tanggal Produk Aktif</label></th>
            <td><input type="date" name="start_date" value="<?php echo esc_attr($row['start_date'] ?? $this->wrpm_today_date()); ?>" /></td>
          </tr>
          <tr>
            <th><label>Durasi Produk Aktif (hari)</label></th>
            <td><input type="number" id="wrpm_active_duration" name="duration_days" value="<?php echo esc_attr((int)($row['duration_days'] ?? 0)); ?>" />
              <p class="description">Jika kosong/0, akan mengikuti durasi dari Produk Reseller.</p>
            </td>
          </tr>
          <tr>
            <th><label>Harga Produk Aktif</label></th>
            <td><input type="number" id="wrpm_active_price" name="price" value="<?php echo esc_attr((int)($row['price'] ?? 0)); ?>" /></td>
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
              <?php endif; ?>

              <div class="fl-filepicker" style="margin-top:10px;">
                <button type="button" class="button" data-fl-file-trigger="wrpm_active_payment_attachments">Pilih File</button>
                <span class="fl-file-label">Tidak ada file yang dipilih</span>
                <input id="wrpm_active_payment_attachments" class="fl-hidden-file" type="file" name="payment_attachments[]" multiple accept="image/*" />
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
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-active-products')); ?>">Kembali</a>
        </p>
      </form>

      <?php if ($is_edit): ?>
        <hr />
        <p class="description">Status: <strong><?php echo esc_html($row['status'] ?? ''); ?></strong> | Expired (otomatis): <strong><?php echo esc_html($row['expires_at'] ?? ''); ?></strong></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="fl-card fl-mt">
  <div class="fl-card-head"><h2>Import CSV Produk Aktif</h2></div>
  <div class="fl-card-body">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <?php wp_nonce_field('wrpm_import_active_products_csv'); ?>
      <input type="hidden" name="action" value="wrpm_import_active_products_csv" />
      <input type="file" name="csv_file" accept=".csv,text/csv" required />
      <button class="button">Import CSV</button>
    </form>
  </div>
</div>

<script>
(function(){
  function toggleNewCustomer(){
    var cb = document.getElementById('wrpm_add_new_customer');
    var box = document.getElementById('wrpm_new_customer_fields');
    var sel = document.getElementById('wrpm_customer_id');
    var nm = document.getElementById('wrpm_new_customer_name');
    if(!cb || !box || !sel) return;

    var hasCustomers = (sel.getAttribute('data-has-customers') === '1');
    if(cb.checked){
      box.style.display = 'block';
      sel.disabled = true;
      sel.required = false;
      if(nm) nm.required = true;
    }else{
      box.style.display = 'none';
      sel.disabled = false;
      sel.required = hasCustomers;
      if(nm) nm.required = false;
    }
  }

  function syncFromReseller(){
    var sel = document.getElementById('wrpm_reseller_product_id');
    if(!sel) return;
    var opt = sel.options[sel.selectedIndex];
    if(!opt) return;
    var d = opt.getAttribute('data-duration');
    var p = opt.getAttribute('data-price');
    var di = document.getElementById('wrpm_active_duration');
    var pi = document.getElementById('wrpm_active_price');
    if(di && d) di.value = String(parseInt(d,10) || 0);
    if(pi && p) pi.value = String(parseInt(p,10) || 0);
  }
  document.addEventListener('change', function(e){
    if(e.target && e.target.id === 'wrpm_reseller_product_id') syncFromReseller();
    if(e.target && e.target.id === 'wrpm_add_new_customer') toggleNewCustomer();
  });

  // init
  try{ toggleNewCustomer(); }catch(e){}

  // init (only if empty)
  try{
    var di = document.getElementById('wrpm_active_duration');
    var pi = document.getElementById('wrpm_active_price');
    if(di && (!di.value || di.value === '0')) syncFromReseller();
    if(pi && (!pi.value || pi.value === '0')) syncFromReseller();
  }catch(e){}
</script>

<script>
jQuery(document).ready(function($){
  $('#wrpm_reseller_product_id, #wrpm_customer_id').select2({
    width: '100%'
  });

  $('#wrpm_reseller_product_id').on('select2:select', function(){
     var sel = document.getElementById('wrpm_reseller_product_id');
     if(sel) {
        var opt = sel.options[sel.selectedIndex];
        if(opt) {
           var d = opt.getAttribute('data-duration');
           var p = opt.getAttribute('data-price');
           var di = document.getElementById('wrpm_active_duration');
           var pi = document.getElementById('wrpm_active_price');
           if(di && d) di.value = String(parseInt(d,10) || 0);
           if(pi && p) pi.value = String(parseInt(p,10) || 0);
        }
     }
  });

  $('#wrpm_add_new_customer').on('change', function(){
     if(this.checked) {
        $('#wrpm_customer_id').val('').trigger('change').prop('disabled', true);
     } else {
        $('#wrpm_customer_id').prop('disabled', false).trigger('change');
     }
  });
});
</script>
