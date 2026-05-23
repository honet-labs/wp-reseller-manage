<?php if (!defined('ABSPATH')) { exit; }
$months_json = wp_json_encode(array_values((array)$months));
$totals_json = wp_json_encode(array_values((array)$totals));
?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php $page_info = 'Pendapatan dihitung dari <strong>Produk Aktif</strong> dengan <strong>status pembayaran = paid</strong>. Grafik memakai <strong>Apache ECharts</strong> via CDN. Jika butuh offline, bisa diganti menjadi bundle lokal.'; echo $this->page_header_html('Laporan', '', '', $page_info); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="action" value="wrpm_report_pdf" />
        <?php wp_nonce_field('wrpm_report_pdf'); ?>
        <div>
          <label>Monthly report (PDF)</label><br/>
          <select name="ym">
            <?php foreach ((array)$month_options as $ym): ?>
              <option value="<?php echo esc_attr($ym); ?>"><?php echo esc_html(wp_date('m/Y', strtotime($ym . '-01'))); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <button class="button button-primary">Download PDF</button>
        </div>
      </form>
    </div>
  </div>

  <div class="fl-grid fl-grid-2 fl-mt">
    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Ringkasan Produk Aktif</h2></div>
      <div class="fl-card-body">
        <div class="fl-kpis">
          <div class="fl-kpi">
            <div class="fl-kpi-label">Active</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)($count_map['active'] ?? 0))); ?></div>
          </div>
          <div class="fl-kpi">
            <div class="fl-kpi-label">Expired</div>
            <div class="fl-kpi-value"><?php echo esc_html(number_format_i18n((int)($count_map['expired'] ?? 0))); ?></div>
          </div>
        </div>
        <p class="description">Ringkasan jumlah Produk Aktif. Pendapatan (grafik) hanya menghitung transaksi dengan status pembayaran <strong>paid</strong>.</p>
      </div>
    </div>

    <div class="fl-card">
      <div class="fl-card-head"><h2 style="margin:0">Pendapatan per Bulan (12 bulan)</h2></div>
      <div class="fl-card-body">
        <div id="wrpmRevenueChart" style="width:100%; height:320px"></div>
      </div>
    </div>
  </div>

  <script>
  (function(){
    var months = <?php echo $months_json ?: '[]'; ?>;
    var totals = <?php echo $totals_json ?: '[]'; ?>;

    function loadEcharts(cb){
      if (window.echarts) return cb();
      var s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js';
      s.onload = cb;
      document.head.appendChild(s);
    }

    loadEcharts(function(){
      var el = document.getElementById('wrpmRevenueChart');
      if (!el || !window.echarts) return;
      var chart = echarts.init(el);
      chart.setOption({
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: months },
        yAxis: { type: 'value' },
        series: [{ type: 'line', data: totals }]
      });
      window.addEventListener('resize', function(){ chart.resize(); });
    });
  })();
  </script>
</div>
