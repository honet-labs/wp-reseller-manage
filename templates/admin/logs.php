<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap fl-wrap">
  <?php $this->admin_notice_from_query(); ?>
  <?php echo $this->page_header_html('Logs'); ?>

  <div class="fl-card fl-mt">
    <div class="fl-card-body">
      <form method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="page" value="wrpm-logs" />
        <div>
          <label>Cari</label><br/>
          <input type="text" name="q" value="<?php echo esc_attr($q); ?>" class="regular-text" placeholder="message/user/meta" />
        </div>
        <div>
          <label>Entity</label><br/>
          <input type="text" name="entity" value="<?php echo esc_attr($entity); ?>" class="regular-text" placeholder="customer, active_product, ..." />
        </div>
        <div>
          <label>Action</label><br/>
          <input type="text" name="action" value="<?php echo esc_attr($action); ?>" class="regular-text" placeholder="create, update, reminder_sent" />
        </div>
        <div><button class="button button-primary">Filter</button></div>
      </form>

      <div class="fl-mt">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>User</th>
              <th>Action</th>
              <th>Entity</th>
              <th>Entity ID</th>
              <th>Message</th>
              <th>JSON</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="8">Belum ada log.</td></tr>
            <?php else: foreach ((array)$rows as $r): ?>
              <tr>
                <td><?php echo esc_html($r['happened_at']); ?></td>
                <td><?php echo esc_html($r['user_login']); ?></td>
                <td><code><?php echo esc_html($r['action']); ?></code></td>
                <td><?php echo esc_html($r['entity']); ?></td>
                <td><code><?php echo esc_html($r['entity_id']); ?></code></td>
                <td><?php echo esc_html($r['message']); ?></td>
                <td>
                  <button type="button" class="button button-small wrpm-view-json" data-title="Log JSON" data-json="<?php echo esc_attr(wp_json_encode($r)); ?>">View</button>
                </td>
                <td><?php echo esc_html($r['ip']); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
