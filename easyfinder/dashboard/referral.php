<?php
require_once '../inc/user_session.inc.php';
$PAGE_TITLE = 'Referral & Earnings';
$URL_NAME   = 'referral';

$conn_r = mysqli_connect('localhost', 'eduowrav_abz', 'uCq.4WRLNOsT', 'eduowrav_rahausub');
$emailSafe  = mysqli_real_escape_string($conn_r, $Auth->email);
$myToken    = $Auth->referal_token;

$walBal  = $WalletController->Get_Single_User_Wallet_Balance($Auth->email);
$balance = $walBal ? intval($walBal->balance) : 0;

$referral_url = rtrim(SITE_URL, '/') . '/easyfinder/dashboard/register?join_with_referal=' . $myToken;

mysqli_query($conn_r, "CREATE TABLE IF NOT EXISTS referal_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referal VARCHAR(255) NOT NULL,
    referee VARCHAR(255) NOT NULL,
    date_refer TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_referal (referal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn_r, "CREATE TABLE IF NOT EXISTS referal_earn_transaction_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referal_email VARCHAR(255) NOT NULL,
    buyer_email VARCHAR(255) NOT NULL,
    earn_amount DECIMAL(10,2) DEFAULT 0,
    date_trans TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TINYINT(1) DEFAULT 0,
    INDEX idx_referal_email (referal_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$referred_rows = [];
$qRef = mysqli_query($conn_r,
    "SELECT u.sname, u.oname, u.email, u.phone, r.date_refer
     FROM referal_tbl r
     JOIN users_tbl u ON MD5(u.email) = r.referee
     WHERE r.referal = '$myToken'
     ORDER BY r.date_refer DESC"
);
while ($r = mysqli_fetch_assoc($qRef)) $referred_rows[] = $r;

$earnings_rows = [];
$total_earned  = 0;
$qEarn = mysqli_query($conn_r,
    "SELECT e.buyer_email, e.earn_amount, e.date_trans, e.status
     FROM referal_earn_transaction_tbl e
     WHERE e.referal_email = '$emailSafe'
     ORDER BY e.date_trans DESC"
);
while ($r = mysqli_fetch_assoc($qEarn)) {
    $earnings_rows[] = $r;
    $total_earned   += intval($r['earn_amount']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'layout/header-propt.inc.php'; ?>
    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?></title>
    <style>
    .stat-card { border-radius: 12px; padding: 24px; color: #fff; }
    .ref-link-box { background:#f0fdf8;border:2px dashed #10d596;border-radius:8px;padding:16px;font-size:14px;word-break:break-all; }
    </style>
</head>
<body>
<?php require_once 'layout/preloader.inc.php'; ?>
<div id="main-wrapper">
    <?php
    require_once 'layout/header.inc.php';
    require_once 'layout/sidebar.inc.php';
    ?>
    <div class="content-body">
        <?php include 'layout/minor-top-navbar.inc.php'; ?>
        <div class="container-fluid">
            <div class="row page-titles mx-0 mb-3">
                <div class="col-sm-6 p-md-0">
                    <h4 style="color:#10d596;font-weight:bold;"><?= $PAGE_TITLE ?></h4>
                    <p class="mb-0 text-muted">Earn cash every time someone you refer makes a purchase.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="stat-card" style="background:#10d596;">
                        <p class="mb-1" style="font-size:12px;opacity:.8;">TOTAL REFERRED</p>
                        <h2 class="mb-0" style="font-weight:bold;"><?= count($referred_rows) ?></h2>
                        <small>people you referred</small>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="stat-card" style="background:#0b9e72;">
                        <p class="mb-1" style="font-size:12px;opacity:.8;">TOTAL EARNED</p>
                        <h2 class="mb-0" style="font-weight:bold;">₦<?= number_format($total_earned) ?></h2>
                        <small>referral commissions</small>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="stat-card" style="background:#1a6e51;">
                        <p class="mb-1" style="font-size:12px;opacity:.8;">WALLET BALANCE</p>
                        <h2 class="mb-0" style="font-weight:bold;">₦<?= number_format($balance) ?></h2>
                        <small>available to spend</small>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="stat-card" style="background:#003d2a;">
                        <p class="mb-1" style="font-size:12px;opacity:.8;">EARNINGS THIS MONTH</p>
                        <?php
                        $this_month = 0;
                        foreach ($earnings_rows as $e) {
                            if (date('Y-m', strtotime($e['date_trans'])) === date('Y-m'))
                                $this_month += intval($e['earn_amount']);
                        }
                        ?>
                        <h2 class="mb-0" style="font-weight:bold;">₦<?= number_format($this_month) ?></h2>
                        <small><?= date('F Y') ?></small>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header" style="background:#10d596;">
                    <h5 class="mb-0 text-white"><i class="fa fa-link mr-2"></i>Your Referral Link</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Share this link. When someone registers and buys data, you earn a commission automatically!</p>
                    <div class="ref-link-box">
                        <span id="ref-link-text"><?= htmlspecialchars($referral_url) ?></span>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success mr-2" onclick="copyRefLink()" style="background:#10d596;border-color:#10d596;">
                            <i class="fa fa-copy mr-1"></i> Copy Link
                        </button>
                        <a href="https://wa.me/?text=<?= urlencode('Join me on ' . SITE_TITLE . ' to buy cheap data! Use my referral link: ' . $referral_url) ?>"
                           target="_blank" class="btn btn-success mr-2" style="background:#25D366;border-color:#25D366;">
                            <i class="fab fa-whatsapp mr-1"></i> Share on WhatsApp
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode('Buy cheap data on ' . SITE_TITLE . '! Use my link: ' . $referral_url) ?>"
                           target="_blank" class="btn btn-info">
                            <i class="fab fa-twitter mr-1"></i> Share on X
                        </a>
                    </div>
                    <div id="copy-alert" class="alert alert-success mt-2" style="display:none;">
                        <i class="fa fa-check mr-1"></i> Link copied to clipboard!
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa fa-users mr-2"></i>People You Referred (<?= count($referred_rows) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($referred_rows)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa fa-users fa-3x mb-2" style="opacity:.3;"></i>
                        <p>You haven't referred anyone yet. Share your link above to start earning!</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead style="background:#10d596;color:#fff;">
                                <tr><th>#</th><th>Name</th><th>Phone</th><th>Date Joined</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referred_rows as $i => $row): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($row['sname'] . ' ' . $row['oname']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= date('d M Y', strtotime($row['date_refer'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa fa-money mr-2"></i>Earnings History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($earnings_rows)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa fa-bar-chart fa-3x mb-2" style="opacity:.3;"></i>
                        <p>No earnings yet. Earnings appear when your referrals make purchases.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead style="background:#10d596;color:#fff;">
                                <tr><th>#</th><th>Buyer</th><th>Commission Earned</th><th>Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($earnings_rows as $i => $row): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars(substr($row['buyer_email'], 0, 4) . '****@' . explode('@', $row['buyer_email'])[1]) ?></td>
                                    <td><strong style="color:#10d596;">+₦<?= number_format($row['earn_amount']) ?></strong></td>
                                    <td><?= date('d M Y H:i', strtotime($row['date_trans'])) ?></td>
                                    <td>
                                        <?php if ($row['status'] == 1): ?>
                                        <span class="badge badge-success">Credited</span>
                                        <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>
</div>
<script>
function copyRefLink() {
    var text = document.getElementById('ref-link-text').innerText;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() { showCopyAlert(); });
    } else {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta); showCopyAlert();
    }
}
function showCopyAlert() {
    var el = document.getElementById('copy-alert');
    el.style.display = 'block';
    setTimeout(function(){ el.style.display = 'none'; }, 3000);
}
</script>
</body>
</html>
