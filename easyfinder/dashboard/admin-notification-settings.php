<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Notification API Settings';
$URL_NAME   = 'admin-notification-settings';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Location: ./'); exit;
}

$conn = mysqli_connect('localhost', 'eduowrav_abz', 'uCq.4WRLNOsT', 'eduowrav_rahausub');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_notif_api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function getSetting($conn, $key) {
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT setting_value FROM admin_notif_api_settings WHERE setting_key='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['setting_value'];
    return '';
}

function saveSetting($conn, $key, $value) {
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO admin_notif_api_settings (setting_key, setting_value) VALUES ('$k','$v') ON DUPLICATE KEY UPDATE setting_value='$v', updated_at=NOW()");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $fields = ['resend_api_key', 'resend_from_email', 'resend_from_name', 'bulksms_api_token', 'bulksms_sender_id', 'bulksms_gateway', 'sms_enabled', 'email_enabled'];
    foreach ($fields as $f) {
        saveSetting($conn, $f, trim($_POST[$f] ?? ''));
    }
    array_push($SITE_SUCCESS, 'API settings saved successfully.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $key       = getSetting($conn, 'resend_api_key');
    $from_em   = getSetting($conn, 'resend_from_email') ?: 'onboarding@resend.dev';
    $from_name = getSetting($conn, 'resend_from_name') ?: 'Rahausub';
    $test_to   = $Auth->email;

    if (empty($key)) {
        array_push($SITE_ERRORS, 'Resend API Key is not configured.');
    } else {
        $payload = json_encode([
            'from'    => "$from_name <$from_em>",
            'to'      => [$test_to],
            'subject' => 'Rahausub — Test Email Notification',
            'html'    => '<div style="font-family:sans-serif;max-width:500px;margin:auto;padding:24px;border:1px solid #e5e5e5;border-radius:8px;"><h2 style="color:#10d596;">✅ Email Notifications Working!</h2><p>This is a test email from your <strong>Rahausub</strong> Notifications system.</p><p style="color:#777;font-size:13px;">Sent via Resend API</p></div>'
        ]);
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res = json_decode($resp, true);
        if ($code === 200 || $code === 201) {
            array_push($SITE_SUCCESS, 'Test email sent to ' . $test_to . ' successfully!');
        } else {
            array_push($SITE_ERRORS, 'Email test failed (HTTP '.$code.'): ' . ($res['message'] ?? $resp));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_sms'])) {
    $api_token  = getSetting($conn, 'bulksms_api_token');
    $sender     = getSetting($conn, 'bulksms_sender_id') ?: 'Rahausub';
    $gateway    = getSetting($conn, 'bulksms_gateway') ?: '0';
    $test_phone = preg_replace('/[^0-9]/', '', $Auth->phone ?? '');
    if (strlen($test_phone) === 11 && $test_phone[0] === '0') $test_phone = '234'.substr($test_phone,1);

    if (empty($api_token)) {
        array_push($SITE_ERRORS, 'Bulk SMS Nigeria API Token is not configured.');
    } elseif (empty($test_phone)) {
        array_push($SITE_ERRORS, 'No phone number found on your profile to test SMS.');
    } else {
        $payload = http_build_query([
            'api_token' => $api_token, 'from' => $sender, 'to' => $test_phone,
            'body'      => 'Rahausub Test: SMS notifications are working! Your Bulk SMS Nigeria is connected.',
            'gateway'   => $gateway, 'append_sender' => '0',
        ]);
        $ch = curl_init('https://www.bulksmsnigeria.com/api/v1/sms/create');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
            CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 || $code === 201) {
            array_push($SITE_SUCCESS, 'Test SMS sent to ' . $test_phone . ' successfully!');
        } else {
            array_push($SITE_ERRORS, 'SMS test failed (HTTP '.$code.'): ' . $resp);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php require_once 'layout/header-propt.inc.php'; ?>
  <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
  <?php require_once 'layout/header.inc.php'; require_once 'layout/sidebar.inc.php'; ?>
  <div class="content-body">
    <?php include 'layout/minor-top-navbar.inc.php'; ?>
    <div class="container-fluid">
      <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-6 p-md-0">
          <h4 style="color:#10d596;font-weight:700;"><i class="fa fa-cogs mr-2"></i><?= $PAGE_TITLE ?></h4>
          <p class="mb-0 text-muted">Configure API credentials for Email and SMS notification channels.</p>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
          <a href="admin-notifications" class="btn btn-outline-secondary btn-sm align-self-center">
            <i class="fa fa-arrow-left mr-1"></i> Back to Notifications
          </a>
        </div>
      </div>

      <?php if (!empty($SITE_SUCCESS)): foreach ($SITE_SUCCESS as $msg): ?>
      <div class="alert alert-success"><i class="fa fa-check-circle mr-2"></i><?= htmlspecialchars($msg) ?></div>
      <?php endforeach; endif; ?>
      <?php if (!empty($SITE_ERRORS)): foreach ($SITE_ERRORS as $msg): ?>
      <div class="alert alert-danger"><i class="fa fa-exclamation-circle mr-2"></i><?= htmlspecialchars($msg) ?></div>
      <?php endforeach; endif; ?>

      <form method="POST">
      <div class="row">
        <!-- Resend Email -->
        <div class="col-lg-6 mb-4">
          <div class="card">
            <div class="card-header" style="background:#17a2b8;">
              <h5 class="mb-0 text-white"><i class="fa fa-envelope mr-2"></i>Resend Email API</h5>
            </div>
            <div class="card-body">
              <p class="text-muted small">Used to send bulk email notifications. Get your API key at <a href="https://resend.com" target="_blank">resend.com</a></p>
              <div class="form-group">
                <label class="font-w600">API Key</label>
                <input type="text" name="resend_api_key" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'resend_api_key')) ?>" placeholder="re_xxxxxxxxxxxx">
              </div>
              <div class="form-group">
                <label class="font-w600">From Email</label>
                <input type="email" name="resend_from_email" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'resend_from_email')) ?>" placeholder="noreply@rahausub.com.ng">
              </div>
              <div class="form-group mb-0">
                <label class="font-w600">From Name</label>
                <input type="text" name="resend_from_name" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'resend_from_name') ?: 'Rahausub') ?>" placeholder="Rahausub">
              </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
              <button type="submit" name="test_email" class="btn btn-outline-info btn-sm">
                <i class="fa fa-paper-plane mr-1"></i> Send Test Email
              </button>
              <span class="text-muted small align-self-center">Tests to: <?= htmlspecialchars($Auth->email) ?></span>
            </div>
          </div>
        </div>

        <!-- BulkSMS Nigeria -->
        <div class="col-lg-6 mb-4">
          <div class="card">
            <div class="card-header" style="background:#6f42c1;">
              <h5 class="mb-0 text-white"><i class="fa fa-mobile mr-2"></i>Bulk SMS Nigeria API</h5>
            </div>
            <div class="card-body">
              <p class="text-muted small">Used to send bulk SMS notifications. Get your token at <a href="https://www.bulksmsnigeria.com" target="_blank">bulksmsnigeria.com</a></p>
              <div class="form-group">
                <label class="font-w600">API Token</label>
                <input type="text" name="bulksms_api_token" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'bulksms_api_token')) ?>" placeholder="Your BulkSMS Nigeria API token">
              </div>
              <div class="form-group">
                <label class="font-w600">Sender ID</label>
                <input type="text" name="bulksms_sender_id" class="form-control" maxlength="11" value="<?= htmlspecialchars(getSetting($conn, 'bulksms_sender_id') ?: 'Rahausub') ?>" placeholder="Rahausub">
                <small class="text-muted">Max 11 characters, no spaces</small>
              </div>
              <div class="form-group mb-0">
                <label class="font-w600">Gateway</label>
                <select name="bulksms_gateway" class="form-control">
                  <option value="0" <?= getSetting($conn,'bulksms_gateway')==='0'?'selected':'' ?>>Gateway 0 (Default)</option>
                  <option value="1" <?= getSetting($conn,'bulksms_gateway')==='1'?'selected':'' ?>>Gateway 1 (DND)</option>
                </select>
              </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
              <button type="submit" name="test_sms" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-mobile mr-1"></i> Send Test SMS
              </button>
              <span class="text-muted small align-self-center">Tests to: <?= htmlspecialchars($Auth->phone ?? 'No phone') ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-1">Channel Enable/Disable</h6>
            <p class="text-muted mb-0 small">Quickly enable or disable channels globally (overridden per notification)</p>
          </div>
          <div class="d-flex" style="gap:16px;">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="email_enabled" name="email_enabled" value="1" <?= getSetting($conn,'email_enabled')==='1'?'checked':'' ?>>
              <label class="custom-control-label" for="email_enabled">Email</label>
            </div>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="sms_enabled" name="sms_enabled" value="1" <?= getSetting($conn,'sms_enabled')==='1'?'checked':'' ?>>
              <label class="custom-control-label" for="sms_enabled">SMS</label>
            </div>
          </div>
        </div>
      </div>

      <div class="text-center mb-5">
        <button type="submit" name="save_settings" class="btn btn-lg btn-primary px-5" style="background:#10d596;border-color:#10d596;">
          <i class="fa fa-save mr-2"></i> Save All Settings
        </button>
      </div>
      </form>
    </div>
  </div>
  <?php require_once 'layout/footer-propt.inc.php'; ?>
</div>
</body>
</html>
