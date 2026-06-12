<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Admin Notifications';
$URL_NAME   = 'admin-notifications';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$conn = mysqli_connect('localhost', 'eduowrav_abz', 'uCq.4WRLNOsT', 'eduowrav_rahausub');

foreach ([
    "CREATE TABLE IF NOT EXISTS admin_notifications_tbl (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, message TEXT NOT NULL,
        notif_type ENUM('important','update','promotion','system_alert','general') DEFAULT 'general',
        priority ENUM('low','medium','high') DEFAULT 'medium', target ENUM('all','specific') DEFAULT 'all',
        target_email VARCHAR(255) NULL, status ENUM('draft','pending','sent','failed') DEFAULT 'draft',
        channels VARCHAR(100) DEFAULT 'inapp', scheduled_at DATETIME NULL, sent_at DATETIME NULL,
        created_by VARCHAR(255) NULL, total_recipients INT DEFAULT 0, delivered_count INT DEFAULT 0,
        read_count INT DEFAULT 0, failed_count INT DEFAULT 0, email_sent INT DEFAULT 0, sms_sent INT DEFAULT 0,
        legacy_notif_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admin_notif_delivery_tbl (
        id INT AUTO_INCREMENT PRIMARY KEY, notification_id INT NOT NULL, user_id INT NULL,
        user_name VARCHAR(255) NULL, user_email VARCHAR(255) NULL, user_phone VARCHAR(50) NULL,
        delivery_status ENUM('pending','sent','delivered','failed','read') DEFAULT 'sent',
        sent_at DATETIME NULL, delivered_at DATETIME NULL, read_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notif_id (notification_id), INDEX idx_user_email (user_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS notifications_tbl (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, message TEXT NOT NULL,
        type ENUM('info','success','warning','danger') DEFAULT 'info', target ENUM('all','specific') DEFAULT 'all',
        target_email VARCHAR(255) NULL, created_by VARCHAR(255) NULL, is_read_by LONGTEXT NULL DEFAULT '[]',
        status TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
] as $sql) { mysqli_query($conn, $sql); }

$search   = trim($_GET['search']  ?? '');
$filter   = $_GET['filter']  ?? '';
$type_f   = $_GET['type']    ?? '';
$sort     = $_GET['sort']    ?? 'newest';
$perpage  = 15;
$page     = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perpage;

$where_parts = [];
if ($search !== '') {
    $ss = mysqli_real_escape_string($conn, $search);
    $where_parts[] = "(title LIKE '%$ss%' OR message LIKE '%$ss%' OR created_by LIKE '%$ss%')";
}
if (in_array($filter, ['draft','pending','sent','failed'])) { $where_parts[] = "status='$filter'"; }
if (in_array($type_f, ['important','update','promotion','system_alert','general'])) { $where_parts[] = "notif_type='$type_f'"; }
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';
$order_sql = match($sort) {
    'oldest'    => 'ORDER BY id ASC',
    'by_status' => 'ORDER BY status ASC, id DESC',
    default     => 'ORDER BY id DESC',
};

$total_count = intval(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admin_notifications_tbl $where_sql"))[0] ?? 0);
$total_pages = max(1, ceil($total_count / $perpage));
$notifs_r    = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl $where_sql $order_sql LIMIT $perpage OFFSET $offset");
$notifs      = [];
if ($notifs_r) { while ($row = mysqli_fetch_assoc($notifs_r)) $notifs[] = $row; }

$s = fn($sql) => intval(mysqli_fetch_row(mysqli_query($conn, $sql))[0] ?? 0);
$stats = [
    'total'         => $s("SELECT COUNT(*) FROM admin_notifications_tbl"),
    'sent'          => $s("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='sent'"),
    'pending'       => $s("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='pending'"),
    'draft'         => $s("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='draft'"),
    'users_reached' => $s("SELECT COALESCE(SUM(total_recipients),0) FROM admin_notifications_tbl WHERE status='sent'"),
];
mysqli_close($conn);

$type_badge = ['important'=>'warning','update'=>'info','promotion'=>'primary','system_alert'=>'danger','general'=>'secondary'];
$type_label = ['important'=>'Important','update'=>'Update','promotion'=>'Promotion','system_alert'=>'System Alert','general'=>'General'];
$status_badge = ['draft'=>'secondary','pending'=>'warning','sent'=>'success','failed'=>'danger'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
  <style>
    .stat-widget { border-radius:10px;padding:20px;color:#fff;text-align:center; }
    .stat-widget .num { font-size:28px;font-weight:800;line-height:1; }
    .stat-widget .lbl { font-size:11px;opacity:.85;margin-top:4px; }
    .notif-row:hover { background:#f0fdf8; }
    .filters-bar { gap:8px; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>
  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">

      <!-- Header -->
      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-7 p-md-0">
          <h4 style="color:#10d596;font-weight:700;"><i class="fa fa-bell mr-2"></i>Notification Management</h4>
          <p class="mb-0 text-muted">Create, send, and track in-app, email and SMS notifications to all users.</p>
        </div>
        <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex align-items-center flex-wrap" style="gap:6px;">
          <a href="admin-notification-create" class="btn btn-success" style="background:#10d596;border-color:#10d596;">
            <i class="fa fa-plus-circle mr-1"></i> New Notification
          </a>
          <a href="admin-notification-ajax?action=export_all" class="btn btn-outline-secondary btn-sm align-self-center">
            <i class="fa fa-download mr-1"></i> Export All
          </a>
          <a href="admin-notification-settings" class="btn btn-outline-secondary btn-sm align-self-center">
            <i class="fa fa-cog mr-1"></i> API Settings
          </a>
        </div>
      </div>

      <!-- Stats -->
      <div class="row mb-4">
        <?php foreach ([
            ['Total', $stats['total'], '#10d596'],
            ['Sent', $stats['sent'], '#0b9e72'],
            ['Pending', $stats['pending'], '#ffc107'],
            ['Drafts', $stats['draft'], '#6c757d'],
            ['Users Reached', number_format($stats['users_reached']), '#17a2b8'],
        ] as [$lbl,$val,$color]): ?>
        <div class="col-xl col-sm-4 col-6 mb-3">
          <div class="stat-widget" style="background:<?= $color ?>;">
            <div class="num"><?= $val ?></div>
            <div class="lbl"><?= $lbl ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Filters -->
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" class="d-flex flex-wrap filters-bar align-items-center">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:200px;"
                   placeholder="Search title/message..." value="<?= htmlspecialchars($search) ?>">
            <select name="filter" class="form-control form-control-sm" style="max-width:130px;">
              <option value="">All Status</option>
              <?php foreach (['draft'=>'Draft','pending'=>'Pending','sent'=>'Sent','failed'=>'Failed'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $filter===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <select name="type" class="form-control form-control-sm" style="max-width:150px;">
              <option value="">All Types</option>
              <?php foreach ($type_label as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $type_f===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <select name="sort" class="form-control form-control-sm" style="max-width:130px;">
              <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
              <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
              <option value="by_status" <?= $sort==='by_status'?'selected':'' ?>>By Status</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="background:#10d596;border-color:#10d596;">
              <i class="fa fa-filter mr-1"></i> Filter
            </button>
            <a href="admin-notifications" class="btn btn-outline-secondary btn-sm">
              <i class="fa fa-refresh mr-1"></i> Reset
            </a>
          </form>
        </div>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="card-body">
          <?php if (empty($notifs)): ?>
          <div class="text-center py-5">
            <i class="fa fa-bell-slash fa-3x text-muted mb-3" style="display:block;"></i>
            <p class="text-muted">No notifications found.</p>
            <a href="admin-notification-create" class="btn btn-success" style="background:#10d596;border-color:#10d596;">
              <i class="fa fa-plus mr-1"></i> Create First Notification
            </a>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead style="background:#10d596;color:#fff;">
                <tr>
                  <th>#</th>
                  <th>Title / Message</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Recipients</th>
                  <th>Delivery</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($notifs as $i => $n): ?>
                <?php
                  $dr_pct = $n['total_recipients'] > 0
                      ? round(($n['delivered_count'] / $n['total_recipients']) * 100) : 0;
                  $bar_color = $dr_pct >= 75 ? '#10d596' : ($dr_pct >= 40 ? '#ffc107' : '#dc3545');
                ?>
                <tr class="notif-row">
                  <td><?= $offset + $i + 1 ?></td>
                  <td style="max-width:250px;">
                    <a href="admin-notification-detail?id=<?= $n['id'] ?>" style="color:#10d596;font-weight:600;">
                      <?= htmlspecialchars(substr($n['title'], 0, 60)) ?><?= strlen($n['title'])>60?'…':'' ?>
                    </a>
                    <div class="text-muted small mt-1">
                      <?= htmlspecialchars(substr(strip_tags($n['message']), 0, 80)) ?><?= strlen($n['message'])>80?'…':'' ?>
                    </div>
                    <div class="mt-1">
                      <?php foreach (explode(',', $n['channels'] ?: 'inapp') as $ch): ?>
                      <span class="badge badge-light border" style="font-size:10px;"><?= htmlspecialchars(trim($ch)) ?></span>
                      <?php endforeach; ?>
                    </div>
                  </td>
                  <td><span class="badge badge-<?= $type_badge[$n['notif_type']] ?? 'secondary' ?>"><?= $type_label[$n['notif_type']] ?? $n['notif_type'] ?></span></td>
                  <td><span class="badge badge-<?= $status_badge[$n['status']] ?? 'secondary' ?>"><?= ucfirst($n['status']) ?></span></td>
                  <td class="text-center"><?= number_format($n['total_recipients']) ?></td>
                  <td style="min-width:100px;">
                    <?php if ($n['total_recipients'] > 0): ?>
                    <div><?= $dr_pct ?>%</div>
                    <div class="progress" style="height:4px;margin-top:3px;">
                      <div class="progress-bar" style="width:<?= $dr_pct ?>%;background:<?= $bar_color ?>;"></div>
                    </div>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:12px;white-space:nowrap;">
                    <?= $n['sent_at'] ? date('d M Y', strtotime($n['sent_at'])) : date('d M Y', strtotime($n['created_at'])) ?>
                    <div class="text-muted"><?= htmlspecialchars(substr($n['created_by'] ?? '', 0, 20)) ?></div>
                  </td>
                  <td style="white-space:nowrap;">
                    <a href="admin-notification-detail?id=<?= $n['id'] ?>" class="btn btn-xs btn-outline-success mr-1" title="View">
                      <i class="fa fa-eye"></i>
                    </a>
                    <?php if (in_array($n['status'], ['draft','failed'])): ?>
                    <a href="admin-notification-create?edit=<?= $n['id'] ?>" class="btn btn-xs btn-outline-warning mr-1" title="Edit">
                      <i class="fa fa-edit"></i>
                    </a>
                    <?php endif; ?>
                    <a href="admin-notification-ajax?action=delete&id=<?= $n['id'] ?>" class="btn btn-xs btn-outline-danger"
                       title="Delete" onclick="return confirm('Delete this notification and all delivery records?')">
                      <i class="fa fa-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$perpage,$total_count) ?> of <?= $total_count ?></small>
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <li class="page-item <?= $p===$page?'active':'' ?>">
                  <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&type=<?= $type_f ?>&sort=<?= $sort ?>">
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
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>
</body>
</html>
