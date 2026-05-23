<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html(self::PLUGIN_SHORT_NAME); ?>

  <div class="fl-grid fl-grid-2 fl-mt">
    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Ringkasan</h2></div>
      <div class="fl-card-body">
        <div class="fl-kpis">
          <div class="fl-kpi">
            <div class="fl-kpi-label">Total Produk Reseller</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$total_reseller)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Total Produk Aktif</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$total_active)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Total Produk Expired</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$total_expired)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Total Pendapatan</div>
            <div class="fl-kpi-value"><?php echo esc_html($this->wrpm_money_idr((float)$total_income)); ?></div>
          </div>
        </div>

        <div class="fl-mt fl-btnrow">
          <a class="button button-primary" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-active-product-add')); ?>">Tambah Produk Aktif</a>
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-product-price-add')); ?>">Tambah Harga Produk</a>
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-reseller-product-add')); ?>">Tambah Produk Reseller</a>
          <a class="button" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-customer-add')); ?>">Tambah Customer</a>
        </div>
      </div>
    </div>

    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Notifikasi</h2></div>
      <div class="fl-card-body">
        <div class="fl-kpis" style="margin-bottom:12px">
          <div class="fl-kpi">
            <div class="fl-kpi-label">Expiring ≤ 7 hari</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$exp7)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Expiring ≤ 3 hari</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$exp3)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Expiring ≤ 1 hari</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$exp1)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Pembayaran Reseller pending</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$pending_reseller)); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Reminder due</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)$due_reminders)); ?></div>
          </div>
        </div>

        <p class="fl-muted" style="margin-top:0">Produk aktif yang akan expired dalam 7 hari ke depan (badge 7/3/1 hari) (mulai <?php echo esc_html($today); ?>).</p>
        <?php if (empty($soon)): ?>
          <div class="notice notice-info" style="margin:0"><p>Tidak ada produk yang akan expired dalam 7 hari.</p></div>
        <?php else: ?>
          <table class="widefat striped">
            <thead>
              <tr>
                <th>Produk</th>
                <th>Customer</th>
                <th>Expired</th>
                <th>Sisa Hari</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ((array)$soon as $r):
                $days = $this->wrpm_date_diff_days($today, (string)$r['expires_at']);
              ?>
                <tr>
                  <td><?php echo esc_html($r['product_label']); ?></td>
                  <td><?php echo esc_html($r['customer_name']); ?></td>
                  <td><?php echo esc_html($r['expires_at']); ?></td>
                  <td>
                    <strong><?php echo esc_html($days); ?></strong>
                    <?php if ($days <= 1): ?><span class="fl-pill fl-pill-red" style="margin-left:6px">1d</span>
                    <?php elseif ($days <= 3): ?><span class="fl-pill fl-pill-yellow" style="margin-left:6px">3d</span>
                    <?php else: ?><span class="fl-pill fl-pill-green" style="margin-left:6px">7d</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a class="button button-small" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-active-product-add', ['id' => $r['id']])); ?>">Edit</a>
                    <a class="button button-small" href="<?php echo esc_url($this->wrpm_admin_url('wrpm-reminders', ['q' => $r['customer_name']])); ?>">Reminders</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="fl-grid fl-grid-2 fl-mt">
    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Tren Pendapatan Bulanan</h2></div>
      <div class="fl-card-body">
        <canvas id="wrpmRevenueChart" style="max-height: 250px;"></canvas>
      </div>
    </div>
    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Distribusi Status Produk</h2></div>
      <div class="fl-card-body" style="display:flex; justify-content:center; align-items:center; height:250px;">
        <canvas id="wrpmStatusChart" style="max-height: 220px; max-width: 220px;"></canvas>
      </div>
    </div>
  </div>
</div>

<?php
$revenue_labels = [];
$revenue_values = [];
foreach ((array)$revenue_monthly as $rm) {
    $revenue_labels[] = $rm['label'];
    $revenue_values[] = $rm['revenue'];
}
?>
<script>
jQuery(document).ready(function($){
  var revCanvas = document.getElementById('wrpmRevenueChart');
  if (revCanvas) {
    var revCtx = revCanvas.getContext('2d');
    new Chart(revCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($revenue_labels); ?>,
        datasets: [{
          label: 'Pendapatan (IDR)',
          data: <?php echo json_encode($revenue_values); ?>,
          backgroundColor: 'rgba(30, 41, 59, 0.85)',
          borderColor: 'rgba(30, 41, 59, 1)',
          borderWidth: 1.5,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return 'Rp ' + value.toLocaleString('id-ID');
              }
            }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
  }

  var statusCanvas = document.getElementById('wrpmStatusChart');
  if (statusCanvas) {
    var statusCtx = statusCanvas.getContext('2d');
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['Aktif', 'Expired'],
        datasets: [{
          data: [<?php echo (int)$total_active; ?>, <?php echo (int)$total_expired; ?>],
          backgroundColor: ['rgba(34, 197, 94, 0.85)', 'rgba(239, 68, 68, 0.85)'],
          borderColor: ['#22c55e', '#ef4444'],
          borderWidth: 1.5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }
});
</script>
