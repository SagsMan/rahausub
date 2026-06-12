<?php
require_once '../inc/user_session.inc.php';

if (!in_array(1, explode(',', $Auth->admin_role)) && !in_array(2, explode(',', $Auth->admin_role))) {
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? intval($_GET['id']) : 0;

$conn = mysqli_connect('localhost', 'eduowrav_abz', 'uCq.4WRLNOsT', 'eduowrav_rahausub');

/* ── DELETE ───────────────────────────────────────────────────────────────── */
if ($action === 'delete' && $id > 0) {
    $nr = mysqli_query($conn, "SELECT id, legacy_notif_id FROM admin_notifications_tbl WHERE id=$id LIMIT 1");
    if ($nr && $row = mysqli_fetch_assoc($nr)) {
        mysqli_query($conn, "DELETE FROM admin_notif_delivery_tbl WHERE notification_id=$id");
        if (!empty($row['legacy_notif_id'])) {
            $lid = intval($row['legacy_notif_id']);
            mysqli_query($conn, "UPDATE notifications_tbl SET status=0 WHERE id=$lid");
        }
        mysqli_query($conn, "DELETE FROM admin_notifications_tbl WHERE id=$id");
    }
    mysqli_close($conn);
    header('Location: admin-notifications'); exit;
}

/* ── RESEND ───────────────────────────────────────────────────────────────── */
if ($action === 'resend' && $id > 0) {
    $nr = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$id LIMIT 1");
    if (!$nr || !($notif = mysqli_fetch_assoc($nr))) {
        mysqli_close($conn); header('Location: admin-notifications'); exit;
    }

    $already_r = mysqli_query($conn,
        "SELECT user_email FROM admin_notif_delivery_tbl
         WHERE notification_id=$id AND delivery_status IN('delivered','read')");
    $already = [];
    if ($already_r) { while ($ar = mysqli_fetch_row($already_r)) $already[] = $ar[0]; }

    $users_r = mysqli_query($conn, "SELECT id, sname, oname, email, phone FROM users_tbl");
    $resent  = 0;
    if ($users_r) {
        while ($u = mysqli_fetch_assoc($users_r)) {
            if (in_array($u['email'], $already)) continue;
            $ue  = mysqli_real_escape_string($conn, $u['email']);
            $un  = mysqli_real_escape_string($conn, trim(($u['sname']??'').' '.($u['oname']??'')));
            $up  = mysqli_real_escape_string($conn, $u['phone'] ?? '');
            $uid = intval($u['id']);
            $ex  = mysqli_query($conn, "SELECT id FROM admin_notif_delivery_tbl WHERE notification_id=$id AND user_email='$ue' LIMIT 1");
            if ($ex && mysqli_fetch_row($ex)) {
                mysqli_query($conn, "UPDATE admin_notif_delivery_tbl SET delivery_status='delivered', sent_at=NOW(), delivered_at=NOW() WHERE notification_id=$id AND user_email='$ue'");
            } else {
                mysqli_query($conn, "INSERT INTO admin_notif_delivery_tbl (notification_id,user_id,user_name,user_email,user_phone,delivery_status,sent_at,delivered_at) VALUES($id,$uid,'$un','$ue','$up','delivered',NOW(),NOW())");
            }
            $resent++;
        }
    }

    $total_r = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$id");
    $total_v = $total_r ? intval(mysqli_fetch_row($total_r)[0]) : 0;
    $deliv_r = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$id AND delivery_status IN('delivered','read')");
    $deliv_v = $deliv_r ? intval(mysqli_fetch_row($deliv_r)[0]) : 0;
    $read_r  = mysqli_query($conn, "SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE notification_id=$id AND delivery_status='read'");
    $read_v  = $read_r ? intval(mysqli_fetch_row($read_r)[0]) : 0;
    mysqli_query($conn, "UPDATE admin_notifications_tbl SET status='sent', sent_at=IF(sent_at IS NULL,NOW(),sent_at), total_recipients=$total_v, delivered_count=$deliv_v, read_count=$read_v, failed_count=0 WHERE id=$id");

    mysqli_close($conn);
    header('Location: admin-notification-detail?id='.$id.'&resent='.$resent); exit;
}

/* ── EXPORT (single notification CSV) ────────────────────────────────────── */
if ($action === 'export' && $id > 0) {
    $nr = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE id=$id LIMIT 1");
    if (!$nr || !($notif = mysqli_fetch_assoc($nr))) {
        mysqli_close($conn); header('Location: admin-notifications'); exit;
    }
    $filename = 'rahausub_notification_' . $id . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache'); header('Expires: 0');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Notification Report — Rahausub']);
    fputcsv($out, ['ID', $notif['id']]);
    fputcsv($out, ['Title', $notif['title']]);
    fputcsv($out, ['Type', $notif['notif_type']]);
    fputcsv($out, ['Priority', $notif['priority']]);
    fputcsv($out, ['Status', $notif['status']]);
    fputcsv($out, ['Total Recipients', $notif['total_recipients']]);
    fputcsv($out, ['Delivered', $notif['delivered_count']]);
    fputcsv($out, ['Read Count', $notif['read_count']]);
    fputcsv($out, ['Sent At', $notif['sent_at'] ?? '—']);
    fputcsv($out, ['Created At', $notif['created_at']]);
    fputcsv($out, ['Created By', $notif['created_by']]);
    fputcsv($out, []);
    fputcsv($out, ['#', 'User Name', 'Email', 'Phone', 'Delivery Status', 'Sent At', 'Delivered At', 'Read At']);
    $dr = mysqli_query($conn, "SELECT * FROM admin_notif_delivery_tbl WHERE notification_id=$id ORDER BY id ASC");
    $i = 1;
    if ($dr) {
        while ($drow = mysqli_fetch_assoc($dr)) {
            fputcsv($out, [$i++, $drow['user_name'], $drow['user_email'], $drow['user_phone'], $drow['delivery_status'], $drow['sent_at']??'', $drow['delivered_at']??'', $drow['read_at']??'']);
        }
    }
    fclose($out);
    mysqli_close($conn); exit;
}

/* ── EXPORT ALL ───────────────────────────────────────────────────────────── */
if ($action === 'export_all') {
    $filename = 'rahausub_notifications_report_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache'); header('Expires: 0');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Notifications Report — Rahausub — Generated: ' . date('d M Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['#','ID','Title','Type','Priority','Status','Recipients','Delivered','Read','Failed','Delivery Rate %','Created By','Sent At','Created At']);
    $all_r = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl ORDER BY id DESC");
    $i = 1;
    if ($all_r) {
        while ($row = mysqli_fetch_assoc($all_r)) {
            $dr = $row['total_recipients'] > 0 ? round(($row['delivered_count']/$row['total_recipients'])*100,1) : 0;
            fputcsv($out, [$i++, $row['id'], $row['title'], $row['notif_type'], $row['priority'], $row['status'],
                $row['total_recipients'], $row['delivered_count'], $row['read_count'], $row['failed_count'],
                $dr.'%', $row['created_by'], $row['sent_at']??'', $row['created_at']]);
        }
    }
    fclose($out);
    mysqli_close($conn); exit;
}

/* ── STATS (JSON) ─────────────────────────────────────────────────────────── */
if ($action === 'stats') {
    header('Content-Type: application/json');
    $s = function($sql) use ($conn) {
        $r = mysqli_query($conn, $sql);
        return $r ? intval(mysqli_fetch_row($r)[0]) : 0;
    };
    echo json_encode([
        'success'       => true,
        'total'         => $s("SELECT COUNT(*) FROM admin_notifications_tbl"),
        'sent'          => $s("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='sent'"),
        'pending'       => $s("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='pending'"),
        'failed'        => $s("SELECT COUNT(*) FROM admin_notifications_tbl WHERE status='failed'"),
        'users_reached' => $s("SELECT SUM(total_recipients) FROM admin_notifications_tbl WHERE status='sent'"),
        'total_read'    => $s("SELECT COUNT(*) FROM admin_notif_delivery_tbl WHERE delivery_status='read'"),
        'timestamp'     => date('Y-m-d H:i:s'),
    ]);
    mysqli_close($conn); exit;
}

/* ── PROCESS SCHEDULED (cron trigger) ────────────────────────────────────── */
if ($action === 'process_scheduled') {
    header('Content-Type: application/json');
    $sched = mysqli_query($conn, "SELECT * FROM admin_notifications_tbl WHERE status='pending' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()");
    $processed = 0;
    if ($sched) {
        while ($sn = mysqli_fetch_assoc($sched)) {
            $sid     = intval($sn['id']);
            $users_r = mysqli_query($conn, "SELECT id, sname, oname, email, phone FROM users_tbl");
            $total   = 0;
            if ($users_r) {
                while ($u = mysqli_fetch_assoc($users_r)) {
                    $ue = mysqli_real_escape_string($conn, $u['email']);
                    $un = mysqli_real_escape_string($conn, trim(($u['sname']??'').' '.($u['oname']??'')));
                    $up = mysqli_real_escape_string($conn, $u['phone'] ?? '');
                    $uid = intval($u['id']);
                    mysqli_query($conn, "INSERT INTO admin_notif_delivery_tbl (notification_id,user_id,user_name,user_email,user_phone,delivery_status,sent_at,delivered_at) VALUES($sid,$uid,'$un','$ue','$up','delivered',NOW(),NOW())");
                    $total++;
                }
            }
            $map   = ['important'=>'warning','update'=>'info','promotion'=>'success','system_alert'=>'danger','general'=>'info'];
            $lt    = mysqli_real_escape_string($conn, $sn['title']);
            $lm    = mysqli_real_escape_string($conn, $sn['message']);
            $ltype = $map[$sn['notif_type']] ?? 'info';
            $ins   = mysqli_query($conn, "INSERT INTO notifications_tbl(title,message,type,target,created_by,status) VALUES('$lt','$lm','$ltype','all','{$sn['created_by']}',1)");
            $legacy_id = $ins ? mysqli_insert_id($conn) : 'NULL';
            mysqli_query($conn, "UPDATE admin_notifications_tbl SET status='sent', sent_at=NOW(), total_recipients=$total, delivered_count=$total, legacy_notif_id=$legacy_id WHERE id=$sid");
            $processed++;
        }
    }
    echo json_encode(['success'=>true,'processed'=>$processed]);
    mysqli_close($conn); exit;
}

mysqli_close($conn);
header('Location: admin-notifications');
exit;
