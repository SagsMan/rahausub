<?php
require_once '../inc/user_session.inc.php';

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
        INDEX idx_notif_id (notification_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS notifications_tbl (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, message TEXT NOT NULL,
        type ENUM('info','success','warning','danger') DEFAULT 'info', target ENUM('all','specific') DEFAULT 'all',
        target_email VARCHAR(255) NULL, created_by VARCHAR(255) NULL, is_read_by LONGTEXT NULL DEFAULT '[]',
        status TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admin_notif_api_settings (
        id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
] as $sql) { mysqli_query($conn, $sql); }

function getSetting($conn, $key) {
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT setting_value FROM admin_notif_api_settings WHERE setting_key='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['setting_value'];
    return '';
}

$email_enabled = getSetting($conn, 'email_enabled') === '1' && !empty(getSetting($conn, 'resend_api_key'));
$sms_enabled   = getSetting($conn, 'sms_enabled') === '1' && !empty(getSetting($conn, 'bulksms_api_token'));

$edit_id = intval($_GET['edit'] ?? 0);
$editing = false;
$notif   = [
    'id'=>0,'title'=>'','message'=>'','notif_type'=>'general','priority'=>'medium',
    'target'=>'all','target_email'=>'','status'=>'draft','channels'=>'inapp','scheduled_at'=>''
];

if ($edit_id > 0) {
    $er = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$edit_id AND status IN('draft','failed') LIMIT 1");
    if ($er && $er_row = mysqli_fetch_assoc($er)) {
        $notif   = $er_row;
        $editing = true;
    }
}

$PAGE_TITLE = $editing ? 'Edit Notification' : 'Create Notification';
$URL_NAME   = 'admin-notification-create';

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']        ?? '');
    $message      = trim($_POST['message']      ?? '');
    $notif_type   = $_POST['notif_type']   ?? 'general';
    $priority     = $_POST['priority']     ?? 'medium';
    $target       = $_POST['target']       ?? 'all';
    $target_email = trim($_POST['target_email'] ?? '');
    $channels_arr = $_POST['channels']     ?? ['inapp'];
    $channels     = implode(',', (array)$channels_arr);
    $schedule     = trim($_POST['scheduled_at'] ?? '');
    $action       = $_POST['submit_action'] ?? 'draft';

    if (empty($title) || empty($message)) {
        array_push($SITE_ERRORS, 'Title and message are required.');
    } else {
        $ts  = mysqli_real_escape_string($conn, $title);
        $ms  = mysqli_real_escape_string($conn, $message);
        $nts = in_array($notif_type, ['important','update','promotion','system_alert','general']) ? $notif_type : 'general';
        $prs = in_array($priority, ['low','medium','high']) ? $priority : 'medium';
        $tgs = $target === 'specific' ? 'specific' : 'all';
        $tes = mysqli_real_escape_string($conn, $target_email);
        $chs = mysqli_real_escape_string($conn, $channels);
        $scs = !empty($schedule) ? mysqli_real_escape_string($conn, $schedule) : null;
        $sch_sql = $scs ? "'$scs'" : 'NULL';
        $who = mysqli_real_escape_string($conn, $Auth->email);
        $st  = ($action === 'send') ? 'pending' : 'draft';

        if ($editing) {
            mysqli_query($conn,
                "UPDATE admin_notifications_tbl SET
                    title='$ts', message='$ms', notif_type='$nts', priority='$prs',
                    target='$tgs', target_email='$tes', channels='$chs', scheduled_at=$sch_sql,
                    status='$st', updated_at=NOW()
                 WHERE id=$edit_id"
            );
            $notif_id = $edit_id;
        } else {
            mysqli_query($conn,
                "INSERT INTO admin_notifications_tbl
                    (title, message, notif_type, priority, target, target_email, channels, scheduled_at, status, created_by, created_at)
                 VALUES ('$ts','$ms','$nts','$prs','$tgs','$tes','$chs',$sch_sql,'$st','$who',NOW())"
            );
            $notif_id = mysqli_insert_id($conn);
        }

        if ($action === 'send' && $notif_id > 0) {
            // Determine user list
            if ($tgs === 'specific' && !empty($target_email)) {
                $te_safe = mysqli_real_escape_string($conn, $target_email);
                $users_r = mysqli_query($conn, "SELECT id, sname, oname, email, phone FROM users_tbl WHERE email='$te_safe' LIMIT 1");
            } else {
                $users_r = mysqli_query($conn, "SELECT id, sname, oname, email, phone FROM users_tbl WHERE status=1");
            }
            $total = 0; $email_sent = 0; $sms_sent = 0;
            if ($users_r) {
                while ($u = mysqli_fetch_assoc($users_r)) {
                    $ue  = mysqli_real_escape_string($conn, $u['email']);
                    $un  = mysqli_real_escape_string($conn, trim(($u['sname']??'').' '.($u['oname']??'')));
                    $up  = mysqli_real_escape_string($conn, $u['phone'] ?? '');
                    $uid = intval($u['id']);
                    mysqli_query($conn,
                        "INSERT INTO admin_notif_delivery_tbl (notification_id,user_id,user_name,user_email,user_phone,delivery_status,sent_at,delivered_at)
                         VALUES ($notif_id,$uid,'$un','$ue','$up','delivered',NOW(),NOW())"
                    );
                    $total++;

                    // Email via Resend
                    if ($email_enabled && in_array('email', (array)$channels_arr)) {
                        $resend_key  = getSetting($conn, 'resend_api_key');
                        $resend_from = getSetting($conn, 'resend_from_email') ?: 'noreply@rahausub.com.ng';
                        $resend_name = getSetting($conn, 'resend_from_name')  ?: 'Rahausub';
                        $html_body   = '<div style="font-family:sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #e5e5e5;border-radius:8px;">'
                                     . '<h2 style="color:#10d596;">' . htmlspecialchars($title) . '</h2>'
                                     . '<p style="font-size:15px;line-height:1.6;">' . nl2br(htmlspecialchars($message)) . '</p>'
                                     . '<hr style="border:0;border-top:1px solid #e5e5e5;"><p style="color:#999;font-size:12px;">This message was sent by '.$resend_name.'</p></div>';
                        $ep = json_encode(['from'=>"$resend_name <$resend_from>", 'to'=>[$u['email']], 'subject'=>$title, 'html'=>$html_body]);
                        $ech = curl_init('https://api.resend.com/emails');
                        curl_setopt_array($ech, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$ep,
                            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$resend_key,'Content-Type: application/json'],
                            CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
                        curl_exec($ech); $ec = curl_getinfo($ech, CURLINFO_HTTP_CODE); curl_close($ech);
                        if ($ec === 200 || $ec === 201) $email_sent++;
                    }

                    // SMS via BulkSMS Nigeria
                    if ($sms_enabled && in_array('sms', (array)$channels_arr) && !empty($u['phone'])) {
                        $sms_token  = getSetting($conn, 'bulksms_api_token');
                        $sms_sender = getSetting($conn, 'bulksms_sender_id') ?: 'Rahausub';
                        $sms_gw     = getSetting($conn, 'bulksms_gateway') ?: '0';
                        $phone_num  = preg_replace('/[^0-9]/', '', $u['phone']);
                        if (strlen($phone_num) === 11 && $phone_num[0] === '0') $phone_num = '234'.substr($phone_num,1);
                        if (strlen($phone_num) >= 10) {
                            $sch = curl_init('https://www.bulksmsnigeria.com/api/v1/sms/create');
                            curl_setopt_array($sch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
                                CURLOPT_POSTFIELDS=>http_build_query(['api_token'=>$sms_token,'from'=>$sms_sender,'to'=>$phone_num,'body'=>$title.': '.$message,'gateway'=>$sms_gw,'append_sender'=>'0']),
                                CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
                                CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
                            curl_exec($sch); $sc = curl_getinfo($sch, CURLINFO_HTTP_CODE); curl_close($sch);
                            if ($sc === 200 || $sc === 201) $sms_sent++;
                        }
                    }
                }
            }

            // Mirror to notifications_tbl for in-app
            if (in_array('inapp', (array)$channels_arr)) {
                $map = ['important'=>'warning','update'=>'info','promotion'=>'success','system_alert'=>'danger','general'=>'info'];
                $ltype = $map[$nts] ?? 'info';
                $ltar  = $tgs === 'specific' ? 'specific' : 'all';
                $ltear = $tgs === 'specific' ? "'$tes'" : 'NULL';
                $ins   = mysqli_query($conn, "INSERT INTO notifications_tbl(title,message,type,target,target_email,created_by,status) VALUES('$ts','$ms','$ltype','$ltar',$ltear,'$who',1)");
                $legacy_id = $ins ? mysqli_insert_id($conn) : 'NULL';
            } else {
                $legacy_id = 'NULL';
            }

            mysqli_query($conn,
                "UPDATE admin_notifications_tbl SET
                    status='sent', sent_at=NOW(), total_recipients=$total,
                    delivered_count=$total, read_count=0, failed_count=0,
                    email_sent=$email_sent, sms_sent=$sms_sent, legacy_notif_id=$legacy_id
                 WHERE id=$notif_id"
            );

            mysqli_close($conn);
            header('Location: admin-notification-detail?id=' . $notif_id . '&sent=1'); exit;
        }

        if ($action === 'schedule' && $notif_id > 0) {
            mysqli_query($conn, "UPDATE admin_notifications_tbl SET status='pending' WHERE id=$notif_id");
            mysqli_close($conn);
            header('Location: admin-notifications?scheduled=1'); exit;
        }

        array_push($SITE_SUCCESS, $editing ? 'Notification updated.' : 'Notification saved as draft.');
        if ($action === 'draft') {
            mysqli_close($conn);
            header('Location: admin-notifications'); exit;
        }
    }
}

$total_users = intval(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users_tbl WHERE status=1"))[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
  <style>
    .channel-btn { border:2px solid #dee2e6;border-radius:8px;padding:12px 16px;cursor:pointer;transition:.2s;text-align:center;min-width:120px; }
    .channel-btn input[type=checkbox] { display:none; }
    .channel-btn.active { border-color:#10d596;background:#f0fdf8; }
    .channel-btn .fa { font-size:22px;display:block;margin-bottom:4px; }
    .char-count { font-size:11px;color:#6c757d;float:right; }
  </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>
  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">
      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-7 p-md-0">
          <h4 style="color:#10d596;font-weight:700;">
            <i class="fa fa-<?= $editing ? 'edit' : 'plus-circle' ?> mr-2"></i><?= $PAGE_TITLE ?>
          </h4>
          <p class="mb-0 text-muted">
            <?= $editing ? 'Edit and resend this notification' : 'Compose and send a notification to your users' ?>
          </p>
        </div>
        <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
          <a href="admin-notifications" class="btn btn-outline-secondary btn-sm align-self-center">
            <i class="fa fa-arrow-left mr-1"></i> Back
          </a>
        </div>
      </div>

      <?php if (!empty($SITE_SUCCESS)): foreach ($SITE_SUCCESS as $m): ?>
      <div class="alert alert-success"><i class="fa fa-check-circle mr-2"></i><?= htmlspecialchars($m) ?></div>
      <?php endforeach; endif; ?>
      <?php if (!empty($SITE_ERRORS)): foreach ($SITE_ERRORS as $m): ?>
      <div class="alert alert-danger"><i class="fa fa-exclamation-circle mr-2"></i><?= htmlspecialchars($m) ?></div>
      <?php endforeach; endif; ?>

      <form method="POST" id="notif-form">

      <div class="row">
        <div class="col-xl-8 col-lg-7">
          <!-- Content -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="fa fa-edit mr-2" style="color:#10d596;"></i>Notification Content</h5>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="font-w600">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required maxlength="255"
                       id="notif-title" value="<?= htmlspecialchars($notif['title']) ?>"
                       placeholder="e.g. 🔥 New Data Deal — MTN 1GB for ₦200">
                <div class="char-count"><span id="title-count">0</span>/255</div>
              </div>
              <div class="form-group">
                <label class="font-w600">Message <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="6" required id="notif-msg"
                          placeholder="Write the full notification message here..."><?= htmlspecialchars($notif['message']) ?></textarea>
                <div class="char-count"><span id="msg-count">0</span> characters</div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-w600">Notification Type</label>
                    <select name="notif_type" class="form-control">
                      <?php foreach (['general'=>'General','update'=>'Update','promotion'=>'Promotion','important'=>'Important','system_alert'=>'System Alert'] as $v=>$l): ?>
                      <option value="<?= $v ?>" <?= $notif['notif_type']===$v?'selected':'' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-w600">Priority</label>
                    <select name="priority" class="form-control">
                      <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High'] as $v=>$l): ?>
                      <option value="<?= $v ?>" <?= $notif['priority']===$v?'selected':'' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Channels -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="fa fa-send mr-2" style="color:#10d596;"></i>Delivery Channels</h5>
            </div>
            <div class="card-body">
              <div class="d-flex flex-wrap" style="gap:12px;">
                <?php
                $active_channels = explode(',', $notif['channels'] ?: 'inapp');
                $channel_defs = [
                    'inapp' => ['fa-bell', 'In-App', 'Always on', true],
                    'email' => ['fa-envelope', 'Email', $email_enabled?'Resend':'Not configured', $email_enabled],
                    'sms'   => ['fa-mobile', 'SMS', $sms_enabled?'BulkSMS NG':'Not configured', $sms_enabled],
                ];
                foreach ($channel_defs as $ch_key => [$icon, $label, $sub, $avail]):
                    $is_checked = in_array($ch_key, $active_channels);
                    $disabled   = !$avail ? 'opacity:.4;cursor:not-allowed;' : '';
                ?>
                <label class="channel-btn <?= $is_checked?'active':'' ?>" id="ch-<?= $ch_key ?>"
                       style="<?= $disabled ?>" <?= !$avail?'title="'.$sub.' — configure in API Settings"':'' ?>>
                  <input type="checkbox" name="channels[]" value="<?= $ch_key ?>"
                         <?= $is_checked?'checked':'' ?> <?= !$avail?'disabled':'' ?>
                         onchange="toggleChannel('<?= $ch_key ?>', this.checked)">
                  <i class="fa <?= $icon ?>" style="color:#10d596;"></i>
                  <strong style="font-size:13px;"><?= $label ?></strong>
                  <small class="d-block text-muted" style="font-size:11px;"><?= $sub ?></small>
                </label>
                <?php endforeach; ?>
              </div>
              <?php if (!$email_enabled || !$sms_enabled): ?>
              <p class="text-muted small mt-2">
                <i class="fa fa-info-circle mr-1"></i>
                Configure Email and SMS API keys in <a href="admin-notification-settings">API Settings</a> to enable those channels.
              </p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-xl-4 col-lg-5">
          <!-- Audience -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="fa fa-users mr-2" style="color:#10d596;"></i>Target Audience</h5>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="font-w600">Send To</label>
                <select name="target" class="form-control" id="target-select" onchange="toggleTargetEmail(this.value)">
                  <option value="all" <?= $notif['target']==='all'?'selected':'' ?>>All Users (<?= $total_users ?> users)</option>
                  <option value="specific" <?= $notif['target']==='specific'?'selected':'' ?>>Specific User (by email)</option>
                </select>
              </div>
              <div class="form-group" id="target-email-group" style="display:<?= $notif['target']==='specific'?'block':'none' ?>;">
                <label class="font-w600">User Email</label>
                <input type="email" name="target_email" class="form-control"
                       value="<?= htmlspecialchars($notif['target_email'] ?? '') ?>"
                       placeholder="user@example.com">
              </div>
            </div>
          </div>

          <!-- Schedule -->
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="fa fa-clock-o mr-2" style="color:#10d596;"></i>Schedule (Optional)</h5>
            </div>
            <div class="card-body">
              <div class="form-group mb-0">
                <label class="font-w600">Send Date & Time</label>
                <input type="datetime-local" name="scheduled_at" class="form-control"
                       value="<?= htmlspecialchars($notif['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($notif['scheduled_at'])) : '') ?>">
                <small class="text-muted">Leave empty to send immediately when you click "Send Now".</small>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fa fa-check-circle mr-2" style="color:#10d596;"></i>Actions</h5>
            </div>
            <div class="card-body">
              <button type="submit" name="submit_action" value="send" class="btn btn-success btn-block mb-2"
                      style="background:#10d596;border-color:#10d596;font-size:15px;"
                      onclick="return confirmSend()">
                <i class="fa fa-send mr-2"></i> Send Now
              </button>
              <button type="submit" name="submit_action" value="schedule" class="btn btn-warning btn-block mb-2">
                <i class="fa fa-clock-o mr-2"></i> Schedule
              </button>
              <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary btn-block">
                <i class="fa fa-save mr-1"></i> Save as Draft
              </button>
            </div>
          </div>
        </div>
      </div>

      </form>
    </div>
  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>
<script>
function toggleTargetEmail(val) {
    document.getElementById('target-email-group').style.display = val === 'specific' ? 'block' : 'none';
}
function toggleChannel(key, checked) {
    var el = document.getElementById('ch-' + key);
    el.classList.toggle('active', checked);
}
function confirmSend() {
    var target = document.getElementById('target-select').value;
    var totalUsers = <?= $total_users ?>;
    var msg = target === 'all'
        ? 'Send this notification to ALL ' + totalUsers + ' users now?'
        : 'Send this notification to the specified user now?';
    return confirm(msg);
}
document.addEventListener('DOMContentLoaded', function() {
    var titleInput = document.getElementById('notif-title');
    var msgInput   = document.getElementById('notif-msg');
    var titleCount = document.getElementById('title-count');
    var msgCount   = document.getElementById('msg-count');
    function updateCount(el, countEl) {
        countEl.textContent = el.value.length;
    }
    titleInput.addEventListener('input', function(){ updateCount(titleInput, titleCount); });
    msgInput.addEventListener('input',   function(){ updateCount(msgInput, msgCount); });
    updateCount(titleInput, titleCount);
    updateCount(msgInput, msgCount);
});
</script>
</body>
</html>
