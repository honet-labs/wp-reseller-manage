<?php if (!defined('ABSPATH')) { exit; }
$is_edit = !empty($row);
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php $page_info = 'Tips: gunakan <strong>Export CSV</strong> sebagai template sebelum melakukan <strong>Import CSV</strong>. Import tersedia di halaman Tambah/Edit.'; echo $this->page_header_html($is_edit ? 'Edit Harga Produk' : 'Tambah Harga Produk', '', '', $page_info); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wrpm_save_price'); ?>
        <input type="hidden" name="action" value="wrpm_save_price" />
        <input type="hidden" name="id" value="<?php echo esc_attr($row['id'] ?? ''); ?>" />

        <table class="form-table" role="presentation">
          <tr>
            <th><label>Nama Produk Jual</label></th>
            <td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr($row['name'] ?? ''); ?>" /></td>
          </tr>
          <tr>
            <th><label>Category Produk</label></th>
            <td><input type="text" name="category" class="regular-text" value="<?php echo esc_attr($row['category'] ?? ''); ?>" /></td>
          </tr>
          <tr>
            <th><label>Tags Produk</label></th>
            <td><input type="text" name="tags" class="regular-text" value="<?php echo esc_attr($row['tags'] ?? ''); ?>" placeholder="contoh: netflix, premium" />
              <p class="description">Pisahkan dengan koma.</p>
            </td>
          </tr>
          <tr>
            <th><label>Seller (dari menu Seller)</label></th>
            <td>
              <select name="seller_id" id="wrpm_price_seller_id">
                <option value="">-- pilih seller (opsional) --</option>
                <?php foreach ((array)($sellers ?? []) as $s):
                  $lbl = $s['name'] . ' (' . ($s['whatsapp'] ?: ($s['email'] ?: $s['phone'])) . ')';
                  $contact = $this->wrpm_build_contact_line($s);
                ?>
                  <option value="<?php echo esc_attr($s['id']); ?>" data-name="<?php echo esc_attr($s['name']); ?>" data-contact="<?php echo esc_attr($contact); ?>" <?php selected(($row['seller_id'] ?? ''), $s['id']); ?>><?php echo esc_html($lbl); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description">Jika dipilih, info seller akan tampil otomatis.</p>
            </td>
          </tr>
          <tr>
            <th><label>Nama Seller</label></th>
            <td><input type="text" id="wrpm_price_seller_name" class="regular-text" value="" readonly /></td>
          </tr>
          <tr>
            <th><label>Kontak Seller</label></th>
            <td><input type="text" id="wrpm_price_seller_contact" class="regular-text" value="" readonly /></td>
          </tr>
          <tr>
            <th><label>Harga Produk Reseller</label></th>
            <td><input type="number" name="reseller_price" value="<?php echo esc_attr((int)($row['reseller_price'] ?? 0)); ?>" /></td>
          </tr>
          <tr>
            <th><label>Harga Produk Jual</label></th>
            <td><input type="number" name="sale_price" value="<?php echo esc_attr((int)($row['sale_price'] ?? 0)); ?>" /></td>
          </tr>
          <tr>
            <th><label>Durasi Produk Jual (hari)</label></th>
            <td><input type="number" name="duration_days" value="<?php echo esc_attr((int)($row['duration_days'] ?? 0)); ?>" /></td>
          </tr>
          <tr>
            <th><label>Description</label></th>
            <td><textarea name="description" rows="5" class="large-text"><?php echo esc_textarea($row['description'] ?? ''); ?></textarea></td>
          </tr>
          <tr>
            <th><label>Catatan Tambahan</label></th>
            <td><textarea name="notes" rows="4" class="large-text"><?php echo esc_textarea($row['notes'] ?? ''); ?></textarea></td>
          </tr>
        </table>

        <p>
          <button class="button button-primary" type="submit">Simpan</button>
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-product-prices')); ?>">Kembali</a>
        </p>
      </form>

      <?php if ($is_edit): ?>
        <hr />
        <p class="description">Created: <?php echo esc_html($row['created_at'] ?? ''); ?> | Updated: <?php echo esc_html($row['updated_at'] ?? ''); ?> | Updated by: <?php echo esc_html($row['updated_by'] ?? ''); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  function syncSeller(){
    var sel = document.getElementById('wrpm_price_seller_id');
    var n = document.getElementById('wrpm_price_seller_name');
    var c = document.getElementById('wrpm_price_seller_contact');
    if(!sel) return;
    var opt = sel.options[sel.selectedIndex];
    var name = opt && opt.getAttribute('data-name');
    var contact = opt && opt.getAttribute('data-contact');
    if(n) n.value = name || '';
    if(c) c.value = contact || '';
  }
  document.addEventListener('change', function(e){
    if(e.target && e.target.id === 'wrpm_price_seller_id') syncSeller();
  });
  // init
  syncSeller();
})();
</script>

<div class="fl-card fl-mt">
  <div class="fl-card-head"><h2>Import CSV Harga Produk</h2></div>
  <div class="fl-card-body">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <?php wp_nonce_field('wrpm_import_prices_csv'); ?>
      <input type="hidden" name="action" value="wrpm_import_prices_csv" />
      <input type="file" name="csv_file" accept=".csv,text/csv" required />
      <button class="button">Import CSV</button>
    </form>
  </div>
</div>
