<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE   = 'Manage SME/Cheap DATA Bundle';
$URL_NAME     = 'dashboard/manage-sme-data';
require_once("../inc/accessbility_controller.inc.php"); 

if(isset($_POST['update'])){

foreach ($_POST['id'] as $id) {
$bardetech_id = isset($_POST['bardetech_plan_id'][$id]) ? trim($_POST['bardetech_plan_id'][$id]) : null;

if($TopupController->Update_SME_Data($_POST['id'][$id],$_POST['d_price'][$id],$_POST['o_price'][$id],$_POST['bundle'][$id],$_POST['duration'][$id], $bardetech_id)){
  array_push($SITE_SUCCESS, "Data Bundle successfully edited");
} else{
  array_push($SITE_ERRORS, "something went wrong!");
}
}

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <?php
   require_once 'layout/header-propt.inc.php';
   ?>

<title><?= $PAGE_TITLE." | ".SITE_TITLE ?> </title>
</head>
<body>

   <?php  require_once 'layout/preloader.inc.php'; ?>

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

      
   <?php
   require_once 'layout/header.inc.php';
   require_once 'layout/sidebar.inc.php';
   ?>


    <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
          <?php  include('layout/minor-top-navbar.inc.php'); ?>
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4 style="color: #003366; font-size: 20px"><?= $PAGE_TITLE ?></h4>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)"><?= SITE_TITLE ?> </a></li>
                            <li class="breadcrumb-item active"><a href="javascript:void(0)"><?= $PAGE_TITLE ?></a></li>
                        </ol>
                    </div>
                </div>

                <!-- Bardetech sync helper banner -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap" role="alert">
                            <div>
                                <strong>Bardetech Provider:</strong> Fill the <strong>Bardetech Plan ID</strong> for each bundle,
                                or use <strong>Auto-Match</strong> to sync IDs from the live Bardetech API automatically.
                            </div>
                            <a href="manage-bardetech-plans.php" class="btn btn-sm btn-primary mt-2 mt-md-0 ml-md-3">
                                View &amp; Sync Bardetech Plans
                            </a>
                        </div>
                    </div>
                </div>
      
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0"><?=$PAGE_TITLE ?></h4>
                                <a href="manage-bardetech-plans.php" class="btn btn-outline-primary btn-sm">
                                    Sync from Bardetech API
                                </a>
                            </div>
                            <div class="card-body">

                                <form action="" method="POST" class="form-valide-with-icon">
                                    <div class="row">

                                      <?php
                                        if($sme_datas = $TopupController->Get_All_SME_Data()){
                                          foreach ($sme_datas as $sme_data) {
                                      ?>

                                      <div class="form-group col-md-12">
                                          <hr>
                                          <h6 class="text-muted">Bundle ID: <?=$sme_data->id?> | Network ID: <?=$sme_data->network_id?></h6>
                                      </div>

                                      <div class="form-group col-md-2">
                                            <label class="mb-1"><strong>Direct Price</strong></label>
                                            <div class="input-group">
                                           <input type="text" readonly="" name="d_price[<?=$sme_data->id?>]" value="<?= trim($sme_data->direct_price) ?>" required=""  class="form-control">
                                           <input type="hidden" readonly="" name="id[<?=$sme_data->id?>]" value="<?= trim($sme_data->id) ?>" required=""  class="form-control">
                                        </div>
                                    </div>

                                        <div class="form-group col-md-2">
                                            <label class="mb-1"><strong>Our Price</strong></label>
                                            <div class="input-group">
                                           <input type="number" name="o_price[<?=$sme_data->id?>]" value="<?= $sme_data->our_price ?>" required="" class="form-control">
                                        </div>
                                    </div>

                                      <div class="form-group col-md-2">
                                            <label class="mb-1"><strong>Data Bundle</strong></label>
                                            <div class="input-group">
                                          <input type="text" name="bundle[<?=$sme_data->id?>]" value="<?= $sme_data->data_bundle   ?>"  required="" class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group col-md-2">
                                            <label class="mb-1"><strong>Data Duration</strong></label>
                                            <div class="input-group">
                                          <input type="text" name="duration[<?=$sme_data->id?>]" value="<?= $sme_data->data_duration ?>"  required="" class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group col-md-4">
                                            <label class="mb-1">
                                                <strong>Bardetech Plan ID</strong>
                                                <span class="text-warning">(for Bardetech provider)</span>
                                                <?php if (!empty($sme_data->bardetech_plan_id)): ?>
                                                    <span class="badge badge-success ml-1">Set</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary ml-1">Not set</span>
                                                <?php endif; ?>
                                            </label>
                                            <div class="input-group">
                                          <input type="text" name="bardetech_plan_id[<?=$sme_data->id?>]" 
                                                 value="<?= htmlspecialchars($sme_data->bardetech_plan_id ?? '') ?>"  
                                                 placeholder="e.g. 523 — or use Auto-Match above" 
                                                 class="form-control <?= !empty($sme_data->bardetech_plan_id) ? 'border-success' : '' ?>">
                                        </div>
                                    </div>

                                    <?php
                                      }
                                    }
                                    ?>
                                </div>

                                <div class="text-center mt-4">
                                    <button name="update" type="submit" value="update" class="btn btn-primary">Update now</button>
                                    <a href="manage-bardetech-plans.php" class="btn btn-outline-info ml-2">Auto-Match from Bardetech API</a>
                                </div>
                            </form>


                            </div>
                        </div>
                    </div>
                 
                </div>

            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->

    </div>
 
  <?php
  require_once 'layout/footer.inc.php';
   require_once 'layout/footer-propt.inc.php';
   ?>
  
</body>
</html>
