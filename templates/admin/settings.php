<?php if (!defined('ABSPATH')) { exit; }
$offsets = isset($s['reminder_offsets']) ? implode(',', (array)$s['reminder_offsets']) : '7,3,1';
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html('Settings'); ?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('wrpm_save_settings'); ?>
    <input type="hidden" name="action" value="wrpm_save_settings" />

    <div class="fl-card fl-mt">
      <div class="fl-card-head"><h2 style="margin:0">Template & Jadwal Reminder</h2></div>
      <div class="fl-card-body">
        <table class="form-table" role="presentation">
          <tr>
            <th><label>Waktu Cron Harian</label></th>
            <td>
              <input type="text" name="cron_time" class="regular-text" value="<?php echo esc_attr($s['cron_time'] ?? '08:00'); ?>" />
              <p class="description">Format <code>HH:MM</code> (waktu situs). Cron mengirim reminder otomatis pada jam ini.</p>
            </td>
          </tr>
          <tr>
            <th><label>Pengiriman Reminder (hari sebelum expired)</label></th>
            <td>
              <input type="text" name="reminder_offsets" class="regular-text" value="<?php echo esc_attr($offsets); ?>" />
              <p class="description">Contoh: <code>7,3,1</code></p>
            </td>
          </tr>
          <tr>
            <th><label>Email Subject</label></th>
            <td><input type="text" name="email_subject" class="large-text" value="<?php echo esc_attr($s['email_subject'] ?? ''); ?>" /></td>
          </tr>
          <tr>
            <th><label>Email Template</label></th>
            <td>
              <textarea name="email_template" class="large-text" rows="8"><?php echo esc_textarea($s['email_template'] ?? ''); ?></textarea>
              <p class="description">Variables: <code>{customer_name}</code>, <code>{product_label}</code>, <code>{start_date}</code>, <code>{duration_days}</code>, <code>{expires_at}</code>, <code>{price}</code>, <code>{remaining_days}</code></p>
            </td>
          </tr>
          <tr>
            <th><label>Telegram Template</label></th>
            <td>
              <textarea name="telegram_template" class="large-text" rows="5"><?php echo esc_textarea($s['telegram_template'] ?? ''); ?></textarea>
            </td>
          </tr>
          <tr>
            <th><label>WhatsApp Template (manual)</label></th>
            <td>
              <textarea name="whatsapp_template" class="large-text" rows="4"><?php echo esc_textarea($s['whatsapp_template'] ?? ''); ?></textarea>
              <p class="description">Dipakai untuk tombol WA manual di halaman Reminder. Variables sama seperti email.</p>
            </td>
          </tr>
          <tr>
            <th><label>WhatsApp Template (H-7 Milestone)</label></th>
            <td>
              <textarea name="whatsapp_template_h7" class="large-text" rows="3"><?php echo esc_textarea($s['whatsapp_template_h7'] ?? ''); ?></textarea>
            </td>
          </tr>
          <tr>
            <th><label>WhatsApp Template (H-3 Milestone)</label></th>
            <td>
              <textarea name="whatsapp_template_h3" class="large-text" rows="3"><?php echo esc_textarea($s['whatsapp_template_h3'] ?? ''); ?></textarea>
            </td>
          </tr>
          <tr>
            <th><label>WhatsApp Template (H-1 Milestone)</label></th>
            <td>
              <textarea name="whatsapp_template_h1" class="large-text" rows="3"><?php echo esc_textarea($s['whatsapp_template_h1'] ?? ''); ?></textarea>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <div class="fl-grid fl-grid-2 fl-mt">
      <div class="fl-card">
        <div class="fl-card-head"><h2 style="margin:0">SMTP (Email Sender)</h2></div>
        <div class="fl-card-body">
          <table class="form-table" role="presentation">
            <tr><th><label>Enable SMTP</label></th><td><label><input type="checkbox" name="smtp_enabled" value="1" <?php checked(!empty($s['smtp_enabled'])); ?> /> aktif</label></td></tr>
            <tr><th><label>Host</label></th><td><input type="text" name="smtp_host" class="regular-text" value="<?php echo esc_attr($s['smtp_host'] ?? ''); ?>" /></td></tr>
            <tr><th><label>Port</label></th><td><input type="number" name="smtp_port" value="<?php echo esc_attr((int)($s['smtp_port'] ?? 587)); ?>" /></td></tr>
            <tr><th><label>Secure</label></th><td>
              <select name="smtp_secure">
                <option value="tls" <?php selected(($s['smtp_secure'] ?? 'tls'),'tls'); ?>>tls</option>
                <option value="ssl" <?php selected(($s['smtp_secure'] ?? ''),'ssl'); ?>>ssl</option>
                <option value="" <?php selected(($s['smtp_secure'] ?? ''),''); ?>>(none)</option>
              </select>
            </td></tr>
            <tr><th><label>User</label></th><td><input type="text" name="smtp_user" class="regular-text" value="<?php echo esc_attr($s['smtp_user'] ?? ''); ?>" /></td></tr>
            <tr><th><label>Pass</label></th><td><input type="password" name="smtp_pass" class="regular-text" value="<?php echo esc_attr($s['smtp_pass'] ?? ''); ?>" autocomplete="new-password" /></td></tr>
            <tr><th><label>From Email</label></th><td><input type="email" name="smtp_from_email" class="regular-text" value="<?php echo esc_attr($s['smtp_from_email'] ?? ''); ?>" /></td></tr>
            <tr><th><label>From Name</label></th><td><input type="text" name="smtp_from_name" class="regular-text" value="<?php echo esc_attr($s['smtp_from_name'] ?? ''); ?>" /></td></tr>
          </table>
        </div>
      </div>

      <div class="fl-card">
        <div class="fl-card-head"><h2 style="margin:0">Telegram Bot</h2></div>
        <div class="fl-card-body">
          <table class="form-table" role="presentation">
            <tr><th><label>Enable Telegram</label></th><td><label><input type="checkbox" name="telegram_enabled" value="1" <?php checked(!empty($s['telegram_enabled'])); ?> /> aktif</label></td></tr>
            <tr><th><label>Bot Token</label></th><td><input type="text" name="telegram_bot_token" class="regular-text" value="<?php echo esc_attr($s['telegram_bot_token'] ?? ''); ?>" /></td></tr>
            <tr><th><label>Default Chat ID</label></th><td><input type="text" name="telegram_default_chat_id" class="regular-text" value="<?php echo esc_attr($s['telegram_default_chat_id'] ?? ''); ?>" /></td></tr>
          </table>
        </div>
      </div>

      <div class="fl-card">
        <div class="fl-card-head"><h2 style="margin:0">WhatsApp WAHA Gateway</h2></div>
        <div class="fl-card-body">
          <table class="form-table" role="presentation">
            <tr><th><label>Enable WhatsApp</label></th><td><label><input type="checkbox" name="waha_enabled" value="1" <?php checked(!empty($s['waha_enabled'])); ?> /> aktif</label></td></tr>
            <tr><th><label>API URL</label></th><td><input type="url" name="waha_api_url" class="regular-text" placeholder="http://localhost:3000" value="<?php echo esc_attr($s['waha_api_url'] ?? ''); ?>" /></td></tr>
            <tr><th><label>API Token (Bearer)</label></th><td><input type="password" name="waha_api_token" class="regular-text" value="<?php echo esc_attr($s['waha_api_token'] ?? ''); ?>" autocomplete="new-password" /></td></tr>
            <tr><th><label>Session Name</label></th><td><input type="text" name="waha_session_name" class="regular-text" value="<?php echo esc_attr($s['waha_session_name'] ?? 'default'); ?>" /></td></tr>
          </table>
        </div>
      </div>
    </div>

    <div class="fl-card fl-mt">
      <div class="fl-card-head"><h2 style="margin:0">PDF Invoice & Branding</h2></div>
      <div class="fl-card-body">
        <table class="form-table" role="presentation">
          <tr>
            <th><label>Judul Invoice</label></th>
            <td><input type="text" name="pdf_invoice_title" class="regular-text" value="<?php echo esc_attr($s['pdf_invoice_title'] ?? 'Invoice'); ?>" /></td>
          </tr>
          <tr>
            <th><label>Warna Tema Utama (Primary Color)</label></th>
            <td>
              <input type="color" name="pdf_primary_color" value="<?php echo esc_attr($s['pdf_primary_color'] ?? '#1e293b'); ?>" style="vertical-align:middle; width:45px; height:32px; padding:0; border:1px solid #ccc; border-radius:4px; cursor:pointer;" />
              <span style="margin-left:8px; vertical-align:middle; font-size:12px; color:#666;">Pilih warna branding untuk header & garis aksen invoice</span>
            </td>
          </tr>
          <tr>
            <th><label>Nama Perusahaan</label></th>
            <td><input type="text" name="pdf_company_name" class="regular-text" value="<?php echo esc_attr($s['pdf_company_name'] ?? ''); ?>" /></td>
          </tr>
          <tr>
            <th><label>Alamat Perusahaan</label></th>
            <td><textarea name="pdf_company_address" class="large-text" rows="3"><?php echo esc_textarea($s['pdf_company_address'] ?? ''); ?></textarea></td>
          </tr>
          <tr>
            <th><label>No Telp/WhatsApp Perusahaan</label></th>
            <td><input type="text" name="pdf_company_phone" class="regular-text" value="<?php echo esc_attr($s['pdf_company_phone'] ?? ''); ?>" /></td>
          </tr>
          <tr>
            <th><label>Instruksi / Detail Pembayaran (Invoice Footer)</label></th>
            <td>
              <textarea name="pdf_payment_details" class="large-text" rows="4" placeholder="Contoh: Transfer Bank BCA 12345678 a/n Reseller Jaya"><?php echo esc_textarea($s['pdf_payment_details'] ?? ''); ?></textarea>
              <p class="description">Petunjuk pembayaran yang akan tampil di bagian bawah setiap PDF Invoice.</p>
            </td>
          </tr>
        </table>
      </div>
    </div>

    
    <div class="fl-grid fl-grid-2 fl-mt">
      <div class="fl-card" style="margin-top:0;">
        <div class="fl-card-head"><h2 style="margin:0">Integrasi WooCommerce</h2></div>
        <div class="fl-card-body">
          <table class="form-table" role="presentation">
            <tr>
              <th><label>Enable Sync</label></th>
              <td><label><input type="checkbox" name="wc_sync_enabled" value="1" <?php checked(!empty($s['wc_sync_enabled'])); ?> /> aktif</label></td>
            </tr>
          </table>
          <div style="margin-top:10px;">
            <a class="button" href="<?php echo esc_url(admin_url('admin-post.php?action=wrpm_wc_sync&_wpnonce=' . wp_create_nonce('wrpm_wc_sync'))); ?>">Sync sekarang (Harga Produk → WooCommerce)</a>
            <p class="description">Membuat / update produk WooCommerce dengan SKU = ID harga produk jual.</p>
          </div>
        </div>
      </div>

      <div class="fl-card" style="margin-top:0;">
        <div class="fl-card-head"><h2 style="margin:0">GitHub Auto-Updater</h2></div>
        <div class="fl-card-body">
          <table class="form-table" role="presentation">
            <tr>
              <th><label>GitHub Repository</label></th>
              <td>
                <input type="text" name="github_repo" class="regular-text" placeholder="username/repository" value="<?php echo esc_attr($s['github_repo'] ?? ''); ?>" />
                <p class="description">Format: <code>username/nama-repo</code></p>
              </td>
            </tr>
            <tr>
              <th><label>GitHub Token (Private Repo)</label></th>
              <td>
                <input type="password" name="github_token" class="regular-text" value="<?php echo esc_attr($s['github_token'] ?? ''); ?>" autocomplete="new-password" />
                <p class="description">Kosongkan jika repository Anda bersifat publik.</p>
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <p class="fl-mt"><button class="button button-primary" type="submit">Simpan Semua Settings</button></p>
  </form>

  <div class="fl-grid fl-grid-2 fl-mt">
    <div class="fl-card" style="margin-top:0;">
      <div class="fl-card-head"><h2 style="margin:0">Maintenance</h2></div>
      <div class="fl-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('wrpm_run_cron_now'); ?>
          <input type="hidden" name="action" value="wrpm_run_cron_now" />
          <button class="button">Jalankan Cron Sekarang</button>
          <p class="description">Menjalankan proses harian: update status expired, update remaining days, dan kirim reminder yang due.</p>
        </form>
      </div>
    </div>

    <div class="fl-card" style="margin-top:0;">
      <div class="fl-card-head"><h2 style="margin:0">Backup & Restore 1-Klik</h2></div>
      <div class="fl-card-body">
        <div style="display:flex; flex-direction:column; gap:15px;">
          <div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <?php wp_nonce_field('wrpm_backup_data'); ?>
              <input type="hidden" name="action" value="wrpm_backup_data" />
              <button class="button button-primary" type="submit">Download Backup (JSON)</button>
              <p class="description" style="margin-top:4px;">Unduh semua data tabel dan pengaturan plugin dalam satu file JSON.</p>
            </form>
          </div>
          <hr style="margin:0; border:none; border-top:1px solid #eee;" />
          <div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
              <?php wp_nonce_field('wrpm_restore_data'); ?>
              <input type="hidden" name="action" value="wrpm_restore_data" />
              <div style="display:flex; gap:8px; align-items:center;">
                <input type="file" name="restore_file" accept=".json" required />
                <button class="button" type="submit" onclick="return confirm('PERHATIAN: Restore akan menghapus data saat ini dan menggantinya dengan data dari file backup. Lanjutkan?');">Restore Backup</button>
              </div>
              <p class="description" style="margin-top:4px;">Pilih file JSON backup hasil unduhan sebelumnya untuk memulihkan data.</p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="fl-grid fl-grid-3 fl-mt">
    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Test Email</h2></div>
      <div class="fl-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('wrpm_test_email'); ?>
          <input type="hidden" name="action" value="wrpm_test_email" />
          <label>Test email ke:</label>
          <input type="email" name="test_email_to" class="regular-text" placeholder="nama@domain.com" required />
          <button class="button">Kirim Test</button>
        </form>
      </div>
    </div>

    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Test Telegram</h2></div>
      <div class="fl-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('wrpm_test_telegram'); ?>
          <input type="hidden" name="action" value="wrpm_test_telegram" />
          <label>Chat ID (opsional):</label>
          <input type="text" name="test_telegram_chat_id" class="regular-text" placeholder="12345678 atau @channel" />
          <button class="button">Kirim Test</button>
        </form>
      </div>
    </div>

    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Test WhatsApp (WAHA)</h2></div>
      <div class="fl-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('wrpm_test_waha'); ?>
          <input type="hidden" name="action" value="wrpm_test_waha" />
          <label>No WhatsApp (dengan kode negara):</label>
          <input type="text" name="test_waha_to" class="regular-text" placeholder="628123456789" required />
          <button class="button">Kirim Test</button>
        </form>
      </div>
    </div>
  </div>

  <div class="fl-card fl-mt">
    <div class="fl-card-head"><h2 style="margin:0">Shortcodes</h2></div>
    <div class="fl-card-body">
      <ul style="margin-top:0">
        <li><code>[wrpm_price_table]</code> — menampilkan tabel harga produk jual</li>
      </ul>
    </div>
  </div>
</div>
