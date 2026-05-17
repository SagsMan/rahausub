<?php
require_once '../inc/user_session.inc.php';

$PAGE_TITLE = 'SME/Cheap Data Bundle';
$URL_NAME = 'dashboard/cheap-data';
require_once '../inc/accessbility_controller.inc.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once 'layout/header-propt.inc.php'; ?>

    <title><?= $PAGE_TITLE . ' | ' . SITE_TITLE ?> </title>
    <style type="text/css">
        table {
            width: 100%
        }

        #table th,
        #table td {
            border: none;
            padding: 7px;
        }
    </style>
</head>

<body>

    <?php require_once 'layout/preloader.inc.php'; ?>

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
            <?php include 'layout/minor-top-navbar.inc.php'; ?>
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





                <div class="row ">
                    <div class="col-12">
                        <div class="card  text-white bg-secondary">
                            <div class="card-body ">
                                <?php if (
                                    isset($_POST['buy_airtime']) &&
                                    !empty($_POST['amount'])
                                ) {
                                    $trans_id = trim($_POST['trans_id']);
                                    if (
                                        !$WalletController->Check_If_My_Transaction_Id_Exist(
                                            $trans_id,
                                            'transactions_tbl'
                                        )
                                    ) {
                                        if (
                                            $TopupController->Store_My_Trans(
                                                $trans_id,
                                                $_POST['amount'],
                                                $_POST['amount'],
                                                $Auth->email,
                                                $_POST['mobile_num'],
                                                $Auth->phone,
                                                $_POST['network_data_name'],
                                                1
                                            )
                                        ) {
                                            if (
                                                $WalletController->Check_Available_Balance_From_Wallet_To_Make_Transaction(
                                                    $_POST['amount'],
                                                    $Auth->email
                                                )
                                            ) {
                                                if (
                                                    $WalletController->Make_Tansaction_From_My_Wallet(
                                                        $trans_id,
                                                        $_POST['amount'],
                                                        $Auth->email
                                                    )
                                                ) {
                                                    if (
                                                        $Airtime_result = $TopupController->BuyCheaperDataBundle(
                                                            $_POST
                                                        )
                                                    ) { ?>



                                                        <?php if (
                                                            (strtolower($Airtime_result->Status ?? '') === 'successful') ||
                                                            isset($Airtime_result->id)
                                                        ) {
                                                            $status = 1;
                                                            if (
                                                                $WalletController->Update_Successful_Remove_Wallet_Money_Trans_Status(
                                                                    'cheap-data',
                                                                    $_POST['amount'],
                                                                    $trans_id,
                                                                    $Auth->email
                                                                )
                                                            ) {
                                                                if (
                                                                    $TopupController->Confirm_My_Trans(
                                                                        $Airtime_result->Status,
                                                                        $trans_id,
                                                                        $Airtime_result->id,
                                                                        $status
                                                                    )
                                                                ) {
                                                                    if (
                                                                        $trans_info = $TopupController->Get_Trans_Info(
                                                                            $trans_id
                                                                        )
                                                                    ) { ?>

                                                                        <div class="col-xl-12">
                                                                            <div class="card text-white bg-success">
                                                                                <div class="card-header">
                                                                                    <h5 class="card-title text-white">Transaction Successful :
                                                                                        <?= $Airtime_result->api_response ?> </h5>
                                                                                </div>
                                                                                <div class="card-body mb-0">


                                                                                    <div class="table-responsive">
                                                                                        <table class="table text-white" id="table">

                                                                                            <tr>
                                                                                                <th>Product Name</th>
                                                                                                <td><?= $Airtime_result->plan_name ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Phone Number</th>
                                                                                                <td><?= $Airtime_result->mobile_number ?>
                                                                                                <td>
                                                                                            </tr>


                                                                                            <tr>
                                                                                                <th>Amount</th>
                                                                                                <td><?= $trans_info->amount ?>
                                                                                                <td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <th>Email : </th>
                                                                                                <td><?= $trans_info->email ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Transaction ID</th>
                                                                                                <td><?= $trans_info->transaction_id ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Request ID</th>
                                                                                                <td><?= $trans_info->request_id ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Status</th>
                                                                                                <td><?= $trans_info->response_description ?>
                                                                                                <td>
                                                                                            </tr>
                                                                                        </table>


                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>


                                                                    <?php }
                                                                }
                                                            }
                                                        } else {
                                                            $status = 2;

                                                            if (
                                                                $trans_info = $TopupController->Get_Trans_Info(
                                                                    $trans_id
                                                                )
                                                            ) {
                                                                if (
                                                                    $TopupController->Confirm_My_Trans(
                                                                        'Transaction Failed',
                                                                        $trans_info->request_id,
                                                                        $trans_info->request_id,
                                                                        $status
                                                                    )
                                                                ) {
                                                                    if (
                                                                        $WalletController->Update_Refund_failed_Wallet_Money_Trans_Status(
                                                                            $trans_info->request_id,
                                                                            $Auth->email,
                                                                            $trans_info->amount
                                                                        )
                                                                    ) {
                                                                        array_push(
                                                                            $SITE_ERRORS,
                                                                            'TRANSACTION FAILED'
                                                                        ); ?>

                                                                        <div class="col-xl-12">
                                                                            <div class="card text-white bg-danger">
                                                                                <div class="card-header">
                                                                                    <h5 class="card-title text-white"><?= strtoupper(
                                                                                                                            $trans_info->product_name
                                                                                                                        ) ?> : Transaction Failed </h5>
                                                                                </div>
                                                                                <div class="card-body mb-0">


                                                                                    <div class="table-responsive">
                                                                                        <table class="table text-white" id="table">

                                                                                            <tr>
                                                                                                <th>Product Name</th>
                                                                                                <td><?= $trans_info->product_name ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Phone Number</th>
                                                                                                <td><?= $trans_info->unique_element ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Amount</th>
                                                                                                <td><?= $trans_info->amount ?>
                                                                                                <td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <th>Email : </th>
                                                                                                <td><?= $trans_info->email ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Transaction ID</th>
                                                                                                <td><?= $trans_info->transaction_id ?>
                                                                                                <td>
                                                                                            </tr>

                                                                                            <tr>
                                                                                                <th>Request ID</th>
                                                                                                <td><?= $trans_info->request_id ?>
                                                                                                <td>
                                                                                            </tr>


                                                                                            <tr>
                                                                                                <th>Status</th>
                                                                                                <td>Failed
                                                                                                <td>
                                                                                            </tr>
                                                                                        </table>


                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>


                                                <?php
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            } else {
                                                if (
                                                    $TopupController->Confirm_My_Trans(
                                                        'Insuficient Balance',
                                                        $trans_id
                                                    )
                                                ) {
                                                    array_push(
                                                        $SITE_ERRORS,
                                                        'Insuficient Balance. Please fund your wallet and try again!'
                                                    );
                                                } ?>
                                                <div class="alert alert-danger" style="text-align:center">Insuficient Balance. Please <a
                                                        href="<?= SITE_URL ?>/dashboard/credit-wallet">Click Here</a> To Fund Your
                                                    Wallet</div>
                                                <a href="<?= SITE_URL ?>/dashboard/cheap-data" class="btn btn-primary">Re-try again</a>

                                        <?php
                                            }
                                        }
                                    } else {
                                        array_push(
                                            $SITE_ERRORS,
                                            'Duplicate Transaction Id or Key!'
                                        ); ?>

                                        <div class="alert alert-danger" style="text-align:center">Duplicate Transaction Id or
                                            Key</div>
                                        <a href="<?= SITE_URL ?>/dashboard/topup" class="btn btn-primary">Re-try again</a>

                                    <?php
                                    }
                                } elseif (
                                    isset($_GET['phone']) &&
                                    is_numeric(strip_tags(trim($_GET['phone'])))
                                ) {
                                    $trans_id = $WalletController->Generate_Trans_id();
                                    $phone_num = strip_tags(
                                        htmlspecialchars($_GET['phone'])
                                    );
                                    if (
                                        $Operator = $Reloadly_API->AutoDectedOperatorNumber(
                                            $phone_num,
                                            'NG',
                                            RELOADLY_API
                                        )
                                    ) {
                                        switch ($Operator?->name ?? '') {
                                            case 'MTN Nigeria':
                                                $network_id = '1';
                                                $network_data_name =
                                                    'MTN Cheap Data';

                                                break;

                                            case 'Airtel Nigeria':
                                                $network_id = '4';
                                                $network_data_name =
                                                    'Airtel Cheap Data';
                                                break;

                                            case 'Glo Nigeria':
                                                $network_id = '2';
                                                $network_data_name =
                                                    'Glo Cheap Data';
                                                break;

                                            case '9Mobile (Etisalat) Nigeria':
                                                $network_id = '3';
                                                $network_data_name =
                                                    '9Mobile Cheap Data';
                                                break;

                                            default:
                                                $network_id = '6';
                                                $network_data_name =
                                                    'Smile Cheap Data';
                                        } ?>

                                        <div class="card-header">
                                            <h5 class="card-title text-white">Phone No.: <?= $phone_num ?></h5>
                                        </div>
                                        <div class="card-body mb-0">


                                            <div class="bootstrap-media">
                                                <div class="media">
                                                    <?php if (isset($Operator->errorCode)): ?>
                                                        <p class="text-warning"><?= $Operator->message; ?></p>
                                                    <?php else: ?>
                                                        <img src="<?= $Operator
                                                                        ->logoUrls[1] ?>" alt="<?= $Operator
                                                                                                    ->logoUrls[1] ?>" class="img-responsive" style="max-width: 70px">
                                                </div>
                                            <?php endif; ?>

                                            <div class="media-body">
                                                <h3 class="text-white" style="padding: 10px;"><?= $Operator?->name ?? '' ?>
                                                </h3>

                                            </div>
                                            </div>
                                        </div>
                                        <hr />

<form class="form-valide-with-icon" method="POST" action="">
    <div class="form-group col-md-12 data-type-wrapper">
        <label>Select Data Type:</label>
        <select data-shb-product-option="data-shb-product-option" id="data_type" data-live-search="true" name="data_type" class="form-control select select-block select-bordered selectpicker variation-type" required>
            <option value="">Choose Option</option>
            <?php 
            if ($typeResponse = $TopupController->GetDataPlanType($network_id)) {
                $dataTypes = json_decode($typeResponse, true);
                foreach ($dataTypes as $value) { 
            ?>
                <option value="<?php echo htmlspecialchars($value['id']); ?>" class="data-type network-<?php echo htmlspecialchars($value['network_id']); ?>">
                    <?php echo htmlspecialchars($value['title']); ?>
                </option>
            <?php 
                }
            } 
            ?>
        </select>
    </div>
    
    <div class="form-group col-md-12">
        <label>Select SME/Cheap Data Bundle:</label>
        <select data-shb-product-option="data-shb-product-option" id="s_option_1" data-live-search="true" name="variation_code" class="form-control select select-block select-bordered selectpicker variation-type" required>
            <option value="">Choose Option</option>
            <?php 
            if ($response = $TopupController->GetCheapDataPlan($network_id)) {
                $Variations = json_decode($response, true);
                foreach ($Variations as $value) { 
            ?>
                <option value="<?php echo htmlspecialchars($value['plan_id']); ?>" class="plans plan-type-<?php echo htmlspecialchars($value['data_type_id']); ?>">
                    <?php echo htmlspecialchars($value['title'] . ' ' . $value['data_bundle'] . ' ₦' . $value['our_price'] . ' => ' . $value['data_duration']); ?>
                </option>
            <?php 
                }
            } 
            ?>
        </select>
    </div>

    <div class="col-md-12 form-group">
        <label>Amount:</label><br>
        <span style="color: red">(Minimum: ₦50 - Maximum: ₦50000)</span>
        <div class="input-group">
            <input type="number" class="form-control" placeholder="Enter Amount" id="s_amount" name="amount" readonly required>
            <input type="hidden" name="buy_airtime">
            <input type="hidden" name="mobile_num" value="<?php echo htmlspecialchars($phone_num); ?>">
            <input type="hidden" name="network_name" value="<?php echo htmlspecialchars($network_id); ?>">
            <input type="hidden" name="trans_id" value="<?php echo htmlspecialchars($trans_id); ?>">
            <input type="hidden" name="network_data_name" value="<?php echo htmlspecialchars($network_data_name); ?>">
            <input type="hidden" id="f_amount" name="f_amount">
            <input type="hidden" id="sub_type" name="sub_type">
            <input type="hidden" id="s_identifier" name="s_identifier">
            <input type="hidden" id="var_idx" name="var_idx">
        </div>
        <span id="price-per-qty"></span>
    </div>
    <a href="data-topup" class="btn btn-danger btn-sm pull-left light">Cancel</a>
    <span class="btn btn-danger btn-sm pull-right" data-toggle="modal" data-target="#exampleModalpopover" id="btn-continue">Buy Now</span>
</form>


                            </div>


                        <?php
                                    }
                                } else {
                        ?>





                        <div class="card-header">
                            <h4 class="card-title text-white">Buy SME/Cheap Data Bundle</h4>
                        </div>
                        <div class="card-body">

                            <form action="cheap-data" method="GET" class="form-valide-with-icon">

                                <div class="form-group">
                                    <label class="text-label">Phone Number : </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"> +234 </span>
                                        </div>
                                        <input type="tel" name="phone" required="" class="form-control"
                                            autocomplete="off" placeholder="Eg: 08060989901">
                                    </div>
                                </div>

                                <button class="btn btn-danger">Continue</button>



                            </form>

                        </div>






                    <?php
                                } ?>



                        </div>
                    </div>

                </div>








            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->

    </div>
    <!-- Modal -->
    <div class="modal fade" id="exampleModalpopover">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Authentication PIN</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="mb-1"><strong>Enter Your Pass Pin : </strong></label>
                        <div class="input-group">
                            <input type="password" name="pass" value="" required="" id="ss_amount"
                                class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary" id="btn-submit" data-dismiss="modal">Continue</a>
                </div>

            </div>
        </div>


    </div>
    <?php
    require_once 'layout/footer.inc.php';
    require_once 'layout/footer-propt.inc.php';
    ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mainVariation = <?php echo $response; ?>; // Use the raw JSON response

    // Debug data to console
    console.log('Data Types:', <?php echo $typeResponse; ?>);
    console.log('Variations:', mainVariation);

    // Initialize selectpicker if using Bootstrap Select
    $('.selectpicker').selectpicker();

    // Initially hide all bundle options
    $('option.plans').hide();

    let availableTypes = $('option.data-type');

    // If only one data type, auto-select it and show bundles
    if (availableTypes.length === 1) {
        $('.data-type-wrapper').hide();
        availableTypes.prop('selected', true);

        let dataType = availableTypes.val();
        console.log('Auto-selected Data Type:', dataType);
        if (dataType) {
            $('option.plan-type-' + dataType).show();
        }
        $('#s_option_1').selectpicker('refresh');
    }

    // Handle data type selection change
    $('#data_type').on('change', function() {
        let dataType = $(this).val();
        console.log('Selected Data Type:', dataType);

        // Hide all bundles first
        $('option.plans').hide();

        // Show bundles that match the selected data type
        if (dataType) {
            $('option.plan-type-' + dataType).show();
        }

        // Refresh selectpicker and reset fields
        $('#s_option_1').val('').selectpicker('refresh');
        $('#s_amount').val('');
        $('#f_amount').val('');
        $('#sub_type').val('');
        $('#s_identifier').val('');
        $('#var_idx').val('');
        $('#price-per-qty').html('');
    });

    // Handle bundle selection change to update amount
    $('#s_option_1').on('change', function() {
        var varx = $(this).val();
        console.log('Selected Plan ID:', varx);

        var amount = 0;
        var f_amount = 0;
        var sub_type = '';
        var identifier = '';
        var var_idx = '';

        // Find matching variation in mainVariation
        for (var i = 0; i < mainVariation.length; i++) {
            if (mainVariation[i].plan_id == varx) {
                amount = mainVariation[i].our_price || 0;
                f_amount = mainVariation[i].direct_price || 0;
                sub_type = mainVariation[i].data_bundle || '';
                identifier = mainVariation[i].plan_id || '';
                var_idx = mainVariation[i].plan_id || '';
                console.log('Found Plan:', mainVariation[i]);
                break;
            }
        }

        if (varx && varx !== '') {
            $('#price-per-qty').html('');
            if (!amount) {
                $('#s_amount').val('0').attr('readonly', true);
            } else {
                var p_amount_edit = 1;
                if (amount > 1 && p_amount_edit == 0) {
                    $('#s_amount').attr('readonly', 'readonly');
                } else {
                    $('#s_amount').removeAttr('readonly');
                }
                $('#s_amount').val(amount);
                $('#f_amount').val(f_amount);
                $('#sub_type').val(sub_type);
                $('#price-per-qty').html('(₦' + Math.round(amount).toFixed(0) + ' per month)');
                if (identifier) {
                    $('#s_identifier').val(identifier);
                }
                $('#var_idx').val(var_idx);
            }
        } else {
            $('#s_amount').val('');
        }
    });
});
</script>

    <script type="text/javascript">
        $(document).ready(function() {
            
            $('.selectpicker').selectpicker({
                style: 'btn-info',
                size: 4
            });






            $('#btn-submit').on('click', function(e) {
                e.preventDefault();
                var form = $('form');
                var send_to_confirm = "<?= $Auth->pin ?>";
                var send_to_confirm_entered = $('#ss_amount').val();
                var ss_amount = md5(send_to_confirm_entered);
                if (send_to_confirm === ss_amount) {
                    swal.fire({
                        title: "<br><span style='font-size: 20px; color:red'>Please confirm your request? </span> <br> <p style='font-size:18px; font-weight:1px'>Phone : <?= $phone_num ?> <br> Operator : <?= $Operator->name ?> <br> Amount : <?= $Operator->senderCurrencySymbol ?> " +
                            document.getElementById('s_amount').value + "<br>Cheap Data Bundle  </p>",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#003366",
                        confirmButtonText: "Confirm",
                    }).then(function(result) {
                        if (result.value === true) {
                            //console.log("Submitted");
                            form.submit();
                        }
                    });
                } else {
                    toastr.error("Invalid Pass Pin. Please try again !", "Error Occurs!", {
                        positionClass: "toast-top-right",
                        timeOut: 5e3,
                        closeButton: !0,
                        debug: !1,
                        newestOnTop: !0,
                        progressBar: !0,
                        preventDuplicates: !0,
                        onclick: null,
                        showDuration: "300",
                        hideDuration: "1000",
                        extendedTimeOut: "1000",
                        showEasing: "swing",
                        hideEasing: "linear",
                        showMethod: "fadeIn",
                        hideMethod: "fadeOut",
                        tapToDismiss: !1
                    })
                }
            });
        });
    </script>



</body>

</html>
