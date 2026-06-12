<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Notification Details';
$URL_NAME   = 'admin-notification-detail';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$notif_id = intval($_GET['id'] ?? 0);
if (!$notif_id) { header('Location: admin-notifications'); exit; }

$conn = mysqli_connect('localhost', 'eduowrav_abz', 'uCq.4WRLNOsT', 'eduowrav_rahausub');

$nr    = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$notif_id LIMIT 1");
if (!$nr || mysqli_num_rows($nr) === 0) { mysqli_close($conn); header('Location: admin-notifications'); exit; }
$notif = mysqli_fetch_assoc($nr);

$perpage  = 20;
$page     = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perpage;
$dsearch  = trim($_GET['dsearch'] ?? '');
$dstatus  = $_GET['dstatus'] ?? '';

$dwhere = ["notification_id=$notif_id"];
if ($dsearch !== '') {
    $ds = mysqli_real_escape_string($conn, $dsearch);
    $dwhere[] = "(user_name LIKE '%$ds%' OR user_email LIKE '%$ds%' OR user_phone LIKE '%$ds%')";
}
if (in_array($dstatus, ['pending','sent','delivered','failed','read'])) {
    $dwhere[] = "delivery_status='$dstatus'";
}
$dwhere_sql = 'WHERE ' . implode(' AND ', $dwhere);

$total_delivery = intval(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl $dwhere_sql"))[0] ?? 0);
$total_pages    = max(1, ceil($total_delivery / $perpage));
$delivery_r     = mysqli_query($conn, "SELECT * FROM admin_notif_delivery_tbl $dwhere_sql ORDER BY id ASC LIMIT $perpage OFFSET $offset");
$deliveries = [];
if ($delivery_r) { while ($drow = mysqli_fetch_assoc($delivery_r)) $deliveries[] = $drow; }

$count_by_status = [];
foreach (['sent','delivered','failed','read','pending'] as $ds_v) {
    $csr = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$notif_id AND delivery_status='$ds_v'");
    $count_by_status[$ds_v] = $csr ? intval(mysqli_fetch_row($csr)[0]) : 0;
}

$delivery_rate = $notif['total_recipients'] > 0 ? round(($notif['delivered_count'] / $notif['total_recipients']) * 100, 1) : 0;
$read_rate     = $notif['total_recipients'] > 0 ? round(($notif['read_count'] / $notif['total_recipients']) * 100, 1) : 0;

mysqli_close($conn);

$type_badge  = ['important'=>'warning','update'=>'info','promotion'=>'primary','system_alert'=>'danger','general'=>'secondary'];
$type_label  = ['important'=>'Important','update'=>'Update','promotion'=>'Promotion','system_alert'=>'System Alert','general'=>'General'];
$pri_colors  = ['low'=>'#6c757d','medium'=>'#ffc107','high'=>'#dc3545'];
$status_badge_map = ['draft'=>'secondary','pending'=>'warning','sent'=>'success','failed'=>'danger'];
$ds_badge    = ['pending'=>'warning','sent'=>'info','delivered'=>'success','failed'=>'danger','read'=>'primary'];

$sent_alert  = intval($_GET['sent']   ?? 0);
$resent_alert = intval($_GET['resent'] ?? -1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= htmlspecialchars($notif['title']) . ' | ' . SITE_TITLE ?></title>
  <style>
    .info-label { font-size:11px;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.5px; }
    .info-value { font-size:14px;font-weight:600;color:#222; }
    .stat-mini { text-align:center;padding:14px;border-radius:8px; }
    .stat-mini .num { font-size:24px;font-weight:800;line-height:1; }
    .stat-mini .lbl { font-size:11px;color:#6c757d;margin-top:3px; }
    .message-body { background:#f8fff8;border-left:4px solid #10d596;border-radius:4px;padding:16px;font-size:14px;line-height:1.6;white-space:pre-wrap;word-break:break-word; }
    .delivery-status-tabs .btn { font-size:12px;padding:5px 12px; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>
  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">

      <?php if ($sent_alert): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fa fa-check-circle mr-2"></i>
        <strong>Notification Sent!</strong> Delivered to <?= number_format($notif['total_recipients']) ?> user(s) successfully.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
      <?php endif; ?>
      <?php if ($resent_alert >= 0): ?>
      <div class="alert alert-info alert-dismissible fade show">
        <i class="fa fa-refresh mr-2"></i>
        <strong>Resent!</strong> <?= $resent_alert ?> additional user(s) received the notification.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
      <?php endif; ?>

      <!-- Header -->
      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-7 p-md-0">
          <h4 style="color:#10d596;font-weight:700;">
            <i class="fa fa-bell mr-2"></i><?= htmlspecialchars($notif['title']) ?>
          </h4>
          <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
            <span class="badge badge-<?= $type_badge[$notif['notif_type']] ?? 'secondary' ?>" style="font-size:12px;padding:5px 10px;">
              <?= $type_label[$notif['notif_type']] ?? 'General' ?>
            </span>
            <span class="badge badge-<?= $status_badge_map[$notif['status']] ?? 'secondary' ?>" style="font-size:12px;padding:5px 10px;">
              <?= ucfirst($notif['status']) ?>
            </span>
            <span style="font-size:12px;color:#6c757d;">
              <span style="width:10px;height:10px;border-radius:50%;background:<?= $pri_colors[$notif['priority']] ?? '#ccc' ?>;display:inline-block;"></span>
              <?= ucfirst($notif['priority']) ?> Priority
            </span>
          </div>
        </div>
        <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex align-items-center flex-wrap" style="gap:6px;">
          <a href="admin-notifications" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left mr-1"></i> Back
          </a>
          <?php if (in_array($notif['status'], ['draft','failed'])): ?>
          <a href="admin-notification-create?edit=<?= $notif_id ?>" class="btn btn-sm btn-warning">
            <i class="fa fa-edit mr-1"></i> Edit
          </a>
          <?php endif; ?>
          <a href="admin-notification-ajax?action=resend&id=<?= $notif_id ?>" class="btn btn-sm btn-success"
             style="background:#10d596;border-color:#10d596;"
             onclick="return confirm('Resend to all failed/pending users?')">
            <i class="fa fa-refresh mr-1"></i> Resend Failed
          </a>
          <a href="admin-notification-ajax?action=export&id=<?= $notif_id ?>" class="btn btn-sm btn-secondary">
            <i class="fa fa-download mr-1"></i> Export CSV
          </a>
          <a href="admin-notification-ajax?action=delete&id=<?= $notif_id ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('Permanently delete this notification and all delivery records?')">
            <i class="fa fa-trash mr-1"></i> Delete
          </a>
        </div>
      </div>

      <div class="row">
        <!-- Left: Details -->
        <div class="col-xl-4 col-lg-4 mb-4">
          <div class="card mb-3">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-info-circle mr-2" style="color:#10d596;"></i>Details</h4>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <div class="info-label">Created By</div>
                <div class="info-value"><?= htmlspecialchars($notif['created_by'] ?? '—') ?></div>
              </div>
              <div class="mb-3">
                <div class="info-label">Created At</div>
                <div class="info-value"><?= $notif['created_at'] ? date('d M Y, H:i', strtotime($notif['created_at'])) : '—' ?></div>
              </div>
              <div class="mb-3">
                <div class="info-label">Sent At</div>
                <div class="info-value"><?= $notif['sent_at'] ? date('d M Y, H:i', strtotime($notif['sent_at'])) : '—' ?></div>
              </div>
              <?php if ($notif['scheduled_at']): ?>
              <div class="mb-3">
                <div class="info-label">Scheduled At</div>
                <div class="info-value"><?= date('d M Y, H:i', strtotime($notif['scheduled_at'])) ?></div>
              </div>
              <?php endif; ?>
              <div class="mb-3">
                <div class="info-label">Target Audience</div>
                <div class="info-value">
                  <?php if ($notif['target'] === 'all'): ?>
                    <span class="badge badge-info">All Users</span>
                  <?php else: ?>
                    <span class="badge badge-warning"><?= htmlspecialchars($notif['target_email'] ?? 'Specific') ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="mb-3">
                <div class="info-label">Channels</div>
                <div class="info-value">
                  <?php foreach (explode(',', $notif['channels'] ?: 'inapp') as $ch): ?>
                  <span class="badge badge-light border" style="font-size:11px;"><?= htmlspecialchars(trim($ch)) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php if ($notif['email_sent'] > 0 || $notif['sms_sent'] > 0): ?>
              <div class="mb-0">
                <div class="info-label">Channel Delivery</div>
                <div class="info-value" style="font-size:13px;">
                  <?php if ($notif['email_sent'] > 0): ?>
                  <span class="mr-2"><i class="fa fa-envelope mr-1" style="color:#17a2b8;"></i><?= $notif['email_sent'] ?> emails</span>
                  <?php endif; ?>
                  <?php if ($notif['sms_sent'] > 0): ?>
                  <span><i class="fa fa-mobile mr-1" style="color:#6f42c1;"></i><?= $notif['sms_sent'] ?> SMS</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-header">
              <h4 class="card-title"><i class="fa fa-comment mr-2" style="color:#10d596;"></i>Message</h4>
            </div>
            <div class="card-body">
              <div class="message-body"><?= nl2br(htmlspecialchars($notif['message'])) ?></div>
            </div>
          </div>
        </div>

        <!-- Right: Stats + Delivery -->
        <div class="col-xl-8 col-lg-8">
          <!-- Stats Row -->
          <div class="row mb-3">
            <?php foreach ([
                ['num'=>number_format($notif['total_recipients']), 'lbl'=>'Total Recipients', 'col'=>'text-dark'],
                ['num'=>$delivery_rate.'%', 'lbl'=>'Delivery Rate', 'col'=>'', 'style'=>'color:#10d596;'],
                ['num'=>$read_rate.'%', 'lbl'=>'Read Rate', 'col'=>'text-primary'],
                ['num'=>number_format($notif['failed_count']), 'lbl'=>'Failed', 'col'=>'text-danger'],
            ] as $stat): ?>
            <div class="col-6 col-md-3 mb-3">
              <div class="card h-100 mb-0">
                <div class="card-body stat-mini">
                  <div class="num <?= $stat['col'] ?>" <?= isset($stat['style'])?"style=\"{$stat['style']}\"":'' ?>>
                    <?= $stat['num'] ?>
                  </div>
                  <div class="lbl"><?= $stat['lbl'] ?></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Per-status breakdown -->
          <div class="card mb-3">
            <div class="card-body py-2">
              <div class="row text-center">
                <?php foreach (['delivered'=>'Delivered','read'=>'Read','sent'=>'Sent','pending'=>'Pending','failed'=>'Failed'] as $sv=>$sl): ?>
                <div class="col border-right">
                  <div style="font-size:18px;font-weight:800;" class="<?= $sv==='failed'?'text-danger':($sv==='read'?'text-primary':'') ?>"
                       <?= $sv==='delivered'?"style=\"color:#10d596;\"":'' ?>>
                    <?= $count_by_status[$sv] ?>
                  </div>
                  <div style="font-size:11px;color:#6c757d;"><?= $sl ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Delivery List -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
              <h5 class="mb-0"><i class="fa fa-list mr-2"></i>Delivery Records</h5>
              <form method="GET" class="d-flex" style="gap:6px;">
                <input type="hidden" name="id" value="<?= $notif_id ?>">
                <input type="text" name="dsearch" class="form-control form-control-sm" placeholder="Search..." value="<?= htmlspecialchars($dsearch) ?>" style="width:150px;">
                <select name="dstatus" class="form-control form-control-sm">
                  <option value="">All</option>
                  <?php foreach (['delivered','read','sent','pending','failed'] as $sv): ?>
                  <option value="<?= $sv ?>" <?= $dstatus===$sv?'selected':'' ?>><?= ucfirst($sv) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Go</button>
              </form>
            </div>
            <div class="card-body p-0">
              <?php if (empty($deliveries)): ?>
              <div class="text-center py-5 text-muted">
                <i class="fa fa-inbox fa-2x mb-2" style="display:block;"></i>
                No delivery records match your filter.
              </div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:13px;">
                  <thead style="background:#f8f9fa;">
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Status</th>
                      <th>Sent At</th>
                      <th>Read At</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($deliveries as $i => $d): ?>
                    <tr>
                      <td><?= $offset + $i + 1 ?></td>
                      <td><?= htmlspecialchars($d['user_name'] ?? '—') ?></td>
                      <td style="word-break:break-all;"><?= htmlspecialchars($d['user_email'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($d['user_phone'] ?? '—') ?></td>
                      <td>
                        <span class="badge badge-<?= $ds_badge[$d['delivery_status']] ?? 'secondary' ?>">
                          <?= ucfirst($d['delivery_status']) ?>
                        </span>
                      </td>
                      <td style="white-space:nowrap;">
                        <?= $d['sent_at'] ? date('d M Y H:i', strtotime($d['sent_at'])) : '—' ?>
                      </td>
                      <td style="white-space:nowrap;">
                        <?= $d['read_at'] ? date('d M Y H:i', strtotime($d['read_at'])) : '—' ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <?php if ($total_pages > 1): ?>
              <div class="d-flex justify-content-between align-items-center p-3">
                <small class="text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$perpage,$total_delivery) ?> of <?= $total_delivery ?></small>
                <nav>
                  <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?= $p==$page?'active':'' ?>">
                      <a class="page-link" href="?id=<?= $notif_id ?>&page=<?= $p ?>&dsearch=<?= urlencode($dsearch) ?>&dstatus=<?= $dstatus ?>">
                        <?= $p ?>
                      </a>
                    </li>
                    <?php endfor; ?>
                  </ul>
                </nav>
              </div>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>
</body>
</html>
