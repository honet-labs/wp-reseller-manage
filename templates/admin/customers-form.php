<?php if (!defined('ABSPATH')) { exit; }
$is_edit = !empty($row);
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php $page_info = 'Tips: gunakan <strong>Export CSV</strong> sebagai template sebelum melakukan <strong>Import CSV</strong>. Import tersedia di halaman Tambah/Edit.'; echo $this->page_header_html($is_edit ? 'Edit Customer' : 'Tambah Customer', '', '', $page_info); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wrpm_save_customer'); ?>
        <input type="hidden" name="action" value="wrpm_save_customer" />
        <input type="hidden" name="id" value="<?php echo esc_attr($row['id'] ?? ''); ?>" />

        <table class="form-table" role="presentation">
          <tr><th><label>Nama</label></th><td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr($row['name'] ?? ''); ?>" /></td></tr>
          <tr><th><label>Email</label></th><td><input type="email" name="email" class="regular-text" value="<?php echo esc_attr($row['email'] ?? ''); ?>" /></td></tr>
          <tr><th><label>Telepon</label></th><td><input type="text" name="phone" class="regular-text" value="<?php echo esc_attr($row['phone'] ?? ''); ?>" /></td></tr>
          <tr><th><label>Telegram</label></th><td><input type="text" name="telegram" class="regular-text" value="<?php echo esc_attr($row['telegram'] ?? ''); ?>" placeholder="chat id / @username" /></td></tr>
          <tr><th><label>Whatsapp</label></th><td><input type="text" name="whatsapp" class="regular-text" value="<?php echo esc_attr($row['whatsapp'] ?? ''); ?>" /></td></tr>
        </table>

        <p>
          <button class="button button-primary" type="submit">Simpan</button>
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-customers')); ?>">Kembali</a>
        </p>
      </form>
    </div>
  </div>
</div>

<div class="fl-card fl-mt">
  <div class="fl-card-head"><h2>Import CSV Customer</h2></div>
  <div class="fl-card-body">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <?php wp_nonce_field('wrpm_import_customers_csv'); ?>
      <input type="hidden" name="action" value="wrpm_import_customers_csv" />
      <input type="file" name="csv_file" accept=".csv,text/csv" required />
      <button class="button">Import CSV</button>
    </form>
  </div>
</div>
