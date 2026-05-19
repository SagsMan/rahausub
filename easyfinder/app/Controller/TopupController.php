<?php

namespace EduTech\Controller;

use \PDO;
use EduTech\C_Base;
use EduTech\SessionHelper\Session;
use SimpleValidator\Validator;
use EduTech\Controller\WalletController;

class TopupController extends C_base
{
    private $ref_id;

    public function buyAirtime(
        $arrayForm
    )    {
        global $Auth;
        $network_slug = $arrayForm['network_slug'];
        $mobile_number = $arrayForm['mobile_num'];

        $apiSetting = $this->GetAPISetting();

        $curl = curl_init();

        $params = [
            "request_id" => $arrayForm['trans_id'],
            "billersCode" => $mobile_number,
            "serviceID" => $network_slug,
            "amount" => $arrayForm["amount"],
            "phone" => $mobile_number,
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => VTPASS_LINK."api/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER =>[
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . VTPASS_AUTH,
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        $res1 = json_decode($response, true);

        if (isset($res1["code"]) && isset($res1["content"]["transactions"])){
            $res = $res1["content"]["transactions"];
            $array = ["id" => $res["transactionId"], "status" => $res["status"] == "delivered" ||  $res["status"] == "pending"?"Successful":"Failed", "api_response" => $res1["response_description"], "plan_name" => $res["product_name"], "mobile_number" => $mobile_number];
            $obj = (object) $array;
            return $obj;
        }else {
            return false;
        }
    }

    public function buyTvSubscription(
        $arrayForm, $Auth
    ){
    $id = $arrayForm["serviceID"];
    $plan = $arrayForm["variation_code"];
    $number = $arrayForm["phone"];
    $amount = $arrayForm["amount"];
    $trans_id = $arrayForm["request_id"];
    if ($id != "showmax"){
        $biller = $arrayForm["billersCode"];
    }else{
        $biller = $number;
    }
    $qty = $arrayForm["quantity"];

    $url = VTPASS_LINK."api/pay";

    if (strtolower($id) == "showmax"){
        $params = [
            "request_id" => $trans_id,
            "billersCode" => $biller,
            "serviceID" => $id,
            "variation_code" => $plan,
            "amount" => $amount,
            "phone" =>  $number,
            "quantity" => $qty,
        ];
    }else{
        $params = [
            "request_id" => $trans_id,
            "billersCode" => $biller,
            "serviceID" => $id,
            "variation_code" => $plan,
            "amount" => $amount,
            "subscription_type" => "change",
            "phone" => $number,
            "quantity" => $qty,
        ];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        return false;
    }

    $json = json_decode($res);

    return $json;
   }

    public function GetTvSubscriptionVariations($serviceID)
    {
    $url = VTPASS_LINK."api/service-variations?serviceID={$serviceID}";
    $res = file_get_contents($url);
    $json = json_decode($res, true);
    if (!isset($json["response_description"]) || $json["response_description"] != "000" || isset($json["content"]["error"])){
        return [];
    }
    $variations = $json["content"]["variations"];
    if (!$variations){
        return [];
    }
    $data = [];
    foreach ($variations as $item){
            $c_fee = $json["content"]["convinience_fee"];
            $amount = (int) $item["variation_amount"];
            if (strpos($c_fee, "%") != FALSE){
                $num = (int) str_replace("%", "", $c_fee);
                $fees = ($num/100) * $amount;
                $amount += $fees;
            }else if (strpos($c_fee, "N") != FALSE || strpos($c_fee, "n") != FALSE){
                $fees = (int) str_replace("n", "", str_replace("N", "", $c_fee));
                $amount += $fees;
            }

            $data[] = [
                "id" => $item["variation_code"],
                "name" => $item["name"]." + ".$json["content"]["convinience_fee"],
                "amount" => $amount,
            ];
    }
    return $data;
}

    public function GetDataNowMerchantProducts($serviceID, $type = '')
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.datanow.ng/api/merchant',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvYXBpLmRhdGFub3cubmdcL2FwaVwvbWVyY2hhbnRcL3NpZ25pbiIsImlhdCI6MTY3NTAxNTUyMywiZXhwIjoxODkxMDE1NTIzLCJuYmYiOjE2NzUwMTU1MjMsImp0aSI6ImpnNnRRdnFpVHZHTUFISEkiLCJzdWIiOjE2MCwicHJ2IjoiOWRhNWQ1MzI2YTE4NGFmN2I0ZTRjZDZmNzJhZTU5NDFmMDUzZDIzNCJ9.BIRftpS-vphbRrcGQmlP2P_ogu2kL8ULF1TmuIPs0Vg',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($serviceID == 'data_plans') {
            $resData = json_decode($response);
            return $resData->data->data_plans;
        } elseif ($serviceID == 'electric_plans') {
            $resData = json_decode($response);
            return $resData->data->electric_plans;
        } elseif ($serviceID == 'cable_plans') {
            $resData = json_decode($response);
            return $resData->data->cable_plans;
        }
    }

    public function GetDataNowSingleMerchantProducts($serviceID, $product_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.datanow.ng/api/merchant',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvYXBpLmRhdGFub3cubmdcL2FwaVwvbWVyY2hhbnRcL3NpZ25pbiIsImlhdCI6MTY3NTAxNTUyMywiZXhwIjoxODkxMDE1NTIzLCJuYmYiOjE2NzUwMTU1MjMsImp0aSI6ImpnNnRRdnFpVHZHTUFISEkiLCJzdWIiOjE2MCwicHJ2IjoiOWRhNWQ1MzI2YTE4NGFmN2I0ZTRjZDZmNzJhZTU5NDFmMDUzZDIzNCJ9.BIRftpS-vphbRrcGQmlP2P_ogu2kL8ULF1TmuIPs0Vg',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        if ($serviceID == 'data_plans') {
            $resData = json_decode($response);
            return $resData->data->data_plans;
        } elseif ($serviceID == 'electric_plans') {
            $resData = json_decode($response);
            return $resData->data->electric_plans[$product_id + 1];
        }
    }
    public function Update_SME_Data($id, $d_price, $o_price, $bundle, $duration, $bardetech_plan_id = null)
    {
        if ($bardetech_plan_id !== null) {
            if (
                $this->data = parent::$db->run_insert(
                    'UPDATE sme_data_tbl SET direct_price = ?, our_price =?, data_bundle = ?, data_duration =?, bardetech_plan_id = ? WHERE id = ?',
                    [$d_price, $o_price, $bundle, $duration, $bardetech_plan_id, $id]
                )
            ) {
                return true;
            }
        } else {
            if (
                $this->data = parent::$db->run_insert(
                    'UPDATE sme_data_tbl SET direct_price = ?, our_price =?, data_bundle  = ?, data_duration =?  WHERE id = ? ',
                    [$d_price, $o_price, $bundle, $duration, $id]
                )
            ) {
                return true;
            }
        }
    }

    public function Get_All_Discount()
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT * FROM discount_tbl ORDER BY id DESC'
            )
        ) {
            return $this->data;
        }
    }
    public function Get_All_SME_Data()
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT * FROM sme_data_tbl ORDER BY id DESC'
            )
        ) {
            return $this->data;
        }
    }
    public function Update_Discount_Percent(
        $id,
        $gene_discount,
        $refer_discount,
        $agent_discount
    ) {
        if (
            $this->data = parent::$db->run_insert(
                'UPDATE discount_tbl SET percentage_off = ?, referal_share =?, agent = ?  WHERE id = ? ',
                [$gene_discount, $refer_discount, $agent_discount, $id]
            )
        ) {
            return true;
        }
    }
    public function Get_Available_Bill_Payment_Services($serviceID)
    {
        $url = 'https://vtpass.com/api/services?identifier=' . $serviceID;

        $client = curl_init($url);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($client);

        if (!empty($response)) {
            return $response;
        }
    }

    public function VerifyTvSubscriptionSmartCard(
        $card_ID,
        $serviceID,
        $type = ''
    ) {
    $iuc = $card_ID;
    $id = $serviceID;

    $url = VTPASS_LINK."api/merchant-verify";

    $params = [
    'billersCode' => $iuc,
    'serviceID' => $id,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        return false;
    }

    $json = json_decode($res);

    return $json;
 }

    public function Store_My_Trans(
        $trans,
        $amount,
        $real_amount,
        $email,
        $unique_element,
        $phone,
        $product_name,
        $is_bill = '0'
    ) {
        if (
            $this->data = parent::$db->run_insert(
                'INSERT INTO transactions_tbl(request_id,amount,real_amount,email,unique_element,phone,product_name,is_bill) VALUES(?,?,?,?,?,?,?,?)',
                [
                    $trans,
                    $amount,
                    $real_amount,
                    $email,
                    $unique_element,
                    $phone,
                    $product_name,
                    $is_bill,
                ]
            )
        ) {
            return true;
        }
    }

    public function Store_Buy_Token_OR_Pin($pin, $email, $trans_id)
    {
        if (
            $this->data = parent::$db->run_insert(
                'INSERT INTO save_pin_and_token_buy(pin,email,trans_id) VALUES(?,?,?)',
                [$pin, $email, $trans_id]
            )
        ) {
            return true;
        }
    }

    public function Get_Trans_Info($trans_id)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT * FROM transactions_tbl WHERE request_id = ? LIMIT 1',
                [$trans_id]
            )
        ) {
            return $this->data[0];
        }
    }

    public function Get_Trans_Category($is_bill, $email, $role)
    {
        if (
            $this->data = parent::$db->run_select(
                "SELECT * FROM transactions_tbl WHERE is_bill = ? AND (email = ? OR super_admin IN($role))",
                [$is_bill, $email]
            )
        ) {
            return $this->data;
        }
    }

    public function Confirm_My_Trans(
        $reason,
        $trans_id,
        $transaction_id = '',
        $status = '0'
    ) {
        if (
            $this->data = parent::$db->run_insert(
                'UPDATE transactions_tbl SET response_description = ?, transaction_id =?, status =?  WHERE request_id = ?',
                [$reason, $transaction_id, $status, $trans_id]
            )
        ) {
            return true;
        }
    }

    /**
     * Internal: fetch live plans from Bardetech API for a given network_id.
     * Bardetech returns all networks in one response:
     *   { "MTN_PLAN": [...], "GLO_PLAN": [...], "AIRTEL_PLAN": [...], "9MOBILE_PLAN": [...] }
     * network_id mapping: 1=MTN, 2=GLO, 3=9MOBILE, 4=AIRTEL
     * Returns array of plan objects or null on failure.
     */
    private function _fetchBardePlansLive($network_id)
    {
        $apiSetting = $this->GetAPISetting();
        $token    = trim($apiSetting->api_key ?? '');
        $url      = 'https://bardetech.com/api/network/?network_id=' . intval($network_id);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Token ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) return null;
        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) return null;

        // Bardetech returns { "MTN_PLAN": [...], "GLO_PLAN": [...], ... }
        // Pick the right key based on network_id
        $networkKeyMap = [
            1 => 'MTN_PLAN',
            2 => 'GLO_PLAN',
            3 => '9MOBILE_PLAN',
            4 => 'AIRTEL_PLAN',
        ];
        $key = $networkKeyMap[intval($network_id)] ?? null;
        if ($key && isset($decoded->$key) && is_array($decoded->$key)) {
            return $decoded->$key;
        }
        // Fallback: return first array found in response
        if (is_object($decoded)) {
            foreach ((array)$decoded as $v) {
                if (is_array($v) && count($v) > 0) return $v;
            }
        }
        if (is_array($decoded)) return $decoded;
        return null;
    }

    public function GetCheapDataPlan($network_id)
    {
        // Check which provider is active
        $apiSetting = $this->GetAPISetting();
        $providerName = strtolower($apiSetting->api_name ?? '');

        if ($providerName === 'bardetech') {
            // Fetch live plans directly from Bardetech API — no manual DB mapping needed
            $plans = $this->_fetchBardePlansLive($network_id);
            if (!$plans || !is_array($plans) || count($plans) === 0) {
                return null;
            }

            // Map Bardetech plan fields to the format cheap-data.php expects
            $result = [];
            foreach ($plans as $p) {
                $planType = strtoupper(trim($p->plan_type ?? 'SME'));
                // Generate a stable integer ID for each plan type string
                $typeId   = abs(crc32($planType)) % 90000 + 10000;

                $result[] = [
                    'plan_id'      => $p->dataplan_id ?? ($p->id ?? ''),
                    'data_type_id' => $typeId,
                    'title'        => $planType,
                    'data_bundle'  => $p->plan ?? '',
                    'our_price'    => $p->plan_amount ?? 0,
                    'direct_price' => $p->plan_amount ?? 0,
                    'data_duration'=> $p->month_validate ?? '',
                    'network_id'   => $network_id,
                ];
            }
            return json_encode($result);
        }

        // Default: Datastation / Husmodata (original plan_id)
        if (
            $this->data = parent::$db->run_select(
                'SELECT t1.*, t2.data_type, t2.title FROM sme_data_tbl as t1 
                JOIN sme_data_type_tbl as t2 ON t1.data_type_id = t2.id
                 WHERE t1.network_id  = ?',
                [$network_id]
            )
        ) {
            return json_encode($this->data);
        }
    }

    public function GetDataPlan($network_id)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT t1.*, t2.data_type, t2.title, t3.api_name as provider FROM plans as t1 
                JOIN plan_types as t2 ON t1.plan_type_id = t2.id
                JOIN api_settings as t3 ON t1.api_id = t3.id 
                 WHERE t1.network_id  = ? AND t3.is_active = ?',
                [$network_id, 1]
            )
        ) {
            return json_encode($this->data);
        }
    }

    public function GetDataPlanType1($network_id, $str = null)
    {
        $url = VTPASS_LINK."api/service-variations?serviceID={$network_id}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
        ]);

        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            return [];
        }
        $json = json_decode($res, true);
        if (!$json["response_description"] || $json["response_description"] != "000"){
            return [];
        }
        $array = [];
        $data = $json["content"];

        foreach ($data["variations"] as $variation){
            if ($str && stripos($variation["name"], $str) == FALSE){
                continue;
            }
            array_push($array, ["name" => $variation["name"], "amount" => $variation["variation_amount"], "id" => $variation["variation_code"]]);
        }
        
        return $array;
    }

    // public function GetDataPlanType($network_id)
    // {
    //     if (
    //         $this->data = parent::$db->run_select(
    //             'SELECT * FROM plan_types WHERE network_id  = ?',
    //             [$network_id]
    //         )
    //     ) {
    //         return json_encode($this->data);
    //     }
    // }
    public function GetDataPlanType($network_id)
    {
        // When Bardetech is active, derive plan types from live API
        $apiSetting   = $this->GetAPISetting();
        $providerName = strtolower($apiSetting->api_name ?? '');

        if ($providerName === 'bardetech') {
            $plans = $this->_fetchBardePlansLive($network_id);
            if (!$plans || !is_array($plans) || count($plans) === 0) {
                return null;
            }

            $seen   = [];
            $types  = [];
            foreach ($plans as $p) {
                $planType = strtoupper(trim($p->plan_type ?? 'SME'));
                if (isset($seen[$planType])) continue;
                $seen[$planType] = true;
                $typeId = abs(crc32($planType)) % 90000 + 10000;
                $types[] = [
                    'id'         => $typeId,
                    'title'      => $planType,
                    'data_type'  => $planType,
                    'network_id' => $network_id,
                ];
            }
            return json_encode($types);
        }

        // Default: Datastation / Husmodata — use sme_data_type_tbl
        if (
            $this->data = parent::$db->run_select(
                'SELECT DISTINCT t2.id, t2.title, t2.data_type, t1.network_id 
                 FROM sme_data_tbl as t1
                 JOIN sme_data_type_tbl as t2 ON t1.data_type_id = t2.id
                 WHERE t1.network_id = ?
                 ORDER BY t2.title',
                [$network_id]
            )
        ) {
            return json_encode($this->data);
        }
    }

    public function GetExamTypes()
    {
        $supported =  ["waec-registration" => ["id" => "waec-registration", "name" => "WAEC Reg.", "variation" => "api/service-variations?serviceID=waec-registration", "id_verify" => null, "pay" => "api/pay"], "waec" => ["id" => "waec", "name" => "WAEC Result Checker", "variation" => "api/service-variations?serviceID=waec",  "id_verify" => null, "pay" => "api/pay"], "jamb" => ["id" => "jamb", "name" => "JAMB PIN", "variation" => "api/service-variations?serviceID=jamb",  "id_verify" => "api/merchant-verify", "pay" => "api/pay"]];
        return $supported;
    }

    public function GetSingleExamType($exam_type)
    {
        $supported =  ["waec-registration" => ["id" => "waec-registration", "name" => "WAEC Reg.", "variation" => "api/service-variations?serviceID=waec-registration", "id_verify" => null, "pay" => "api/pay"], "waec" => ["id" => "waec", "name" => "WAEC Result Checker", "variation" => "api/service-variations?serviceID=waec",  "id_verify" => null, "pay" => "api/pay"], "jamb" => ["id" => "jamb", "name" => "JAMB PIN", "variation" => "api/service-variations?serviceID=jamb",  "id_verify" => "api/merchant-verify", "pay" => "api/pay"]];
        return (isset($supported[$exam_type])?$supported[$exam_type]:[]);
    }

    public function getExamTypeVariation($id){
    $exam = $this->GetSingleExamType($id);
    if (!$exam){
        return [];
    }


    $url = VTPASS_LINK.$exam["variation"];
    $res = file_get_contents($url);
    $json = json_decode($res, true);
    if (!isset($json["response_description"]) || $json["response_description"] != "000" || isset($json["content"]["error"])){
        return [];
    }
    $variations = $json["content"]["variations"];
    if (!$variations){
        return [];
    }

    $data = [];
    foreach ($variations as $item){
            $c_fee = $json["content"]["convinience_fee"];
            $amount = (int) $item["variation_amount"];
            if (strpos($c_fee, "%") != FALSE){
                $num = (int) str_replace("%", "", $c_fee);
                $fees = ($num/100) * $amount;
                $amount += $fees;
            }else if (strpos($c_fee, "N") != FALSE || strpos($c_fee, "n") != FALSE){
                $fees = (int) str_replace("n", "", str_replace("N", "", $c_fee));
                $amount += $fees;
            }

            $data[] = [
                "id" => $item["variation_code"],
                "name" => $item["name"]." - ".$amount,
                "amount" => $amount,
            ];
    }
    return $data;
    }

    public function verifyJambId($id, $id_num, $type){
    $exam = $this->GetSingleExamType($id);
    if (!isset($exam)){
        echo json_encode(["status" => "failed", "msg" => "unknown exam service", "data" => []]);
        return;
    }

    if ($exam["id_verify"] == null){
        echo json_encode(["status" => "failed", "msg" => "{$id} does not require profile ID verification"]);
        return;
    }

    $url = VTPASS_LINK.$exam["id_verify"];

    $params = [
    'billersCode' => $id_num,
    'serviceID' => $id,
    'type' => $type,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(["status" => "failed", "error" => "cURL Error: " . curl_error($ch), "data" => []]);
        return;
    }

    $json = json_decode($res, true);

    if (!$json["code"] || $json["code"] != "000" || isset($json["content"]["error"])){
        echo json_encode(["status" => "failed", "msg" => "failed trying to verifying id number"]);
        return;
    }

    $data = $json["content"];
        $array = [
        "name" => $data["Customer_Name"],
        ];
    echo json_encode(["status" => "success", "data" => $array]);
    }

    public function buyExamPin($post){
    global $Auth;
    $id = $post["exam_type"];
    $plan = $post["exam_plan"];
    $number = $post["profile_id"];
    $amount = $post["amount"];
    $trans_id = $post["trans_id"];
    $qty = $post["qty"];

    $exam = $this->GetSingleExamType($id);

    if (!$exam){
        return false;
    }

    $url = VTPASS_LINK.$exam["pay"];

    if ($id == "jamb"){
        $params = [
          "request_id" => $trans_id,
          "billersCode" => $number,
          "serviceID" => $id,
          "variation_code" => $plan,
          "phone" =>  $Auth->phone,
       ];
    }else{
       $params = [
          "request_id" => $trans_id,
          "serviceID" => $id,
          "variation_code" => $plan,
          "phone" => $Auth->phone,
          "quantity" => $qty,
      ];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, CURL_SSL_VERIFY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . VTPASS_AUTH,
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        return false;
    }

    $json = json_decode($res, true);

    if (!$json["code"] || $json["code"] != "000" || isset($json["content"]["error"])){
        return false;
    }

    $data = $json["content"];
    if (strtolower($data["transactions"]["status"]) == "delivered"){
        $array = [];
        if ($id == "jamb"){
            $pin = trim(explode(":", $json["Pin"])[1]);
            array_push($array, [$pin, ""]);
        }else if ($id == "waec-registration"){
            foreach ($json["tokens"] as $key => $tk){
                array_push($array, [$tk, ""]);
            }
        }else if ($id == "waec"){
            foreach ($json["cards"] as $key => $card){
                array_push($array, [$card["Pin"], $card["Serial"]]);
            }
        }
        return json_decode(json_encode(["success" => "true", "status" => "Successful", "reference_no" => $data["transactions"]["transactionId"],"msg" => "payment made successfully", "data" => $array]));
    }else{
        return false;
    }
    }

    public function GetCheapDataPlanDirectPrice($plan_id)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT direct_price FROM sme_data_tbl WHERE plan_id  = ? LIMIT 1',
                [$plan_id]
            )
        ) {
            return $this->data[0];
        }
    }

    public function GetPinAndTokenTrans($email, $role)
    {
        if (
            $this->data = parent::$db->run_select(
                "SELECT * FROM save_pin_and_token_buy WHERE super_admin IN($role) OR email  = ?",
                [$email]
            )
        ) {
            return $this->data;
        }
    }

    public function GetSinglePinAndTokenTrans($trans_id)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT save_pin_and_token_buy.*, transactions_tbl.amount,transactions_tbl.phone FROM save_pin_and_token_buy LEFT JOIN transactions_tbl ON transactions_tbl.transaction_id = save_pin_and_token_buy.trans_id  WHERE save_pin_and_token_buy.trans_id = ?',
                [$trans_id]
            )
        ) {
            return $this->data;
        }
    }

    public function GetAPISetting($status = 1)
    {
        if (
            $this->data = parent::$db->run_select(
                'SELECT * FROM api_settings  WHERE is_active = ?',
                [$status]
            )
        ) {
            return $this->data[0];
        }
    }

    public function BuyCheaperDataBundle($arrayForm)
    {
        $network_id = $_POST['network_name'];
        $mobile_number = $_POST['mobile_num'];
        $plan_id = $_POST['variation_code'];
        $apiSetting = $this->GetAPISetting();

        $providerName = strtolower($apiSetting->api_name ?? '');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiSetting->api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>
            '{"network": "' .
                $network_id .
                '",
            "mobile_number": "' .
                $mobile_number .
                '",
            "plan": "' .
                $plan_id .
                '",
            "Ported_number":true}',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $apiSetting->api_key,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            error_log("BuyCheaperDataBundle cURL error [{$providerName}]: " . $curl_error);
            return false;
        }

        $Response_result = json_decode($response);

        if (!$Response_result) {
            error_log("BuyCheaperDataBundle invalid JSON from [{$providerName}]: " . $response);
            return false;
        }

        // Normalize Status field across providers:
        // Datastation/Husmodata return "Successful" (capital S)
        // Bardetech returns "successful" (lowercase s)
        // We normalize to "Successful" so all downstream checks work uniformly.
        if (isset($Response_result->Status)) {
            $Response_result->Status = ucfirst(strtolower($Response_result->Status));
        }

        // Bardetech uses "plan_name" — ensure plan_name is set for display
        if (!isset($Response_result->plan_name) && isset($Response_result->plan_network)) {
            $Response_result->plan_name = ($Response_result->plan_network ?? '') . ' ' . ($Response_result->data_bundle ?? '');
        }

        // Bardetech uses "ident" as the transaction identifier (similar to "id")
        if (!isset($Response_result->id) && isset($Response_result->ident)) {
            $Response_result->id = $Response_result->ident;
        }

        return $Response_result;
    }

    // public function BuyCheaperDataBundle1($arrayForm)
    // {
    //     global $Auth;
    //     $network_slug = $arrayForm["network_slug"];
    //     $variation_code = $arrayForm["variation_code"];
    //     $amount = $arrayForm["amount"];
    //     $mobile_no = $arrayForm["mobile_num"];
    //     $trans_id = $arrayForm["trans_id"];

    //     $params = [
    //         "request_id" => $trans_id,
    //         "billersCode" => $mobile_no,
    //         "serviceID" => $network_slug,
    //         "variation_code" => $variation_code,
    //         "amount" => $amount,
    //         "phone" => $mobile_no,
    //     ];

    //     //$apiSetting = $this->GetAPISetting();

    //     $curl = curl_init();

    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => VTPASS_LINK."api/pay",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'POST',
    //         CURLOPT_POSTFIELDS => json_encode($params),
    //         CURLOPT_HTTPHEADER =>[
    //             'Content-Type: application/json',
    //             'Accept: application/json',
    //             'Authorization: Basic ' . VTPASS_AUTH,
    //         ],
    //     ]);

    //     $response = curl_exec($curl);

    //     curl_close($curl);
    //     $res1 = json_decode($response, true);

    //     if (isset($res1["code"]) && isset($res1["content"]["transactions"])){
    //         $res = $res1["content"]["transactions"];
    //         $array = ["id" => $res["transactionId"], "status" => $res["status"] == "delivered" ||  $res["status"] == "pending"?"Successful":"Failed", "api_response" => $res1["response_description"], "plan_name" => $res["product_name"], "mobile_number" => $mobile_no];
    //         $obj = (object) $array;
    //         return $obj;
    //     }else {
    //         return false;
    //     }
    // }
    public function BuyCheaperDataBundle1($arrayForm)
    {
        global $Auth;
        $network_slug = $arrayForm["network_slug"];
        $variation_code = $arrayForm["variation_code"];
        $amount = $arrayForm["amount"];
        $mobile_no = $arrayForm["mobile_num"];
        $trans_id = $arrayForm["trans_id"];
    
        // Extract variation code properly (remove amount part if exists)
        $variation_parts = explode("{BRK}", $variation_code);
        $clean_variation_code = $variation_parts[0];
    
        $params = [
            "request_id" => $trans_id,
            "billersCode" => $mobile_no,
            "serviceID" => $network_slug,
            "variation_code" => $clean_variation_code,
            "amount" => $amount,
            "phone" => $mobile_no,
        ];
    
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL => VTPASS_LINK."api/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Reduced from 0 to 30 seconds
            CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . VTPASS_AUTH,
            ],
        ]);
    
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
    
        // Log the API response for debugging
        error_log("VTPass API Response: " . $response);
        error_log("VTPass HTTP Code: " . $http_code);
    
        if ($response === false) {
            error_log("CURL Error: " . $curl_error);
            return false;
        }
    
        $res1 = json_decode($response, true);
    
        if (isset($res1["code"]) && $res1["code"] == "000" && isset($res1["content"]["transactions"])) {
            $res = $res1["content"]["transactions"];
            $array = [
                "id" => $res["transactionId"], 
                "status" => ($res["status"] == "delivered" || $res["status"] == "pending") ? "Successful" : "Failed", 
                "api_response" => $res1["response_description"], 
                "plan_name" => $res["product_name"], 
                "mobile_number" => $mobile_no
            ];
            return (object) $array;
        } else {
            // Return detailed error information
            $error_msg = $res1["response_description"] ?? $res1["error"] ?? "Unknown API error";
            error_log("VTPass API Error: " . $error_msg);
            return false;
        }
    }

    public function BuyCheaperDataBundle_Requery(
        $mobile_number,
        $plan_id,
        $network_id,
        $trans_id
    ) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://www.datahouse.com.ng/api/data/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>
            '{"network": "' .
                $network_id .
                '",
"mobile_number": "' .
                $mobile_number .
                '",
"plan": "' .
                $plan_id .
                '",
"Ported_number":true}',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token 630e90c465df180bb0b542dc9b40a2143c65c832',
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        $Response_result = json_decode($response);

        if (isset($Response_result->Status)) {
            if ($Response_result->Status === 'successful') {
                $status = 1;

                if (
                    $this->Confirm_My_Trans(
                        $Response_result->Status,
                        $trans_id,
                        $trans_id,
                        $status
                    )
                ) {
                    return $Response_result;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function BuyResultCheckerPin($exam_type, $qty)
    {
        switch ($exam_type) {
            case 'neco':
                $result_type = 'neco_v2';
                break;
            case 'waec':
                $result_type = 'waec_v2';
                break;
            case 'nabteb':
                $result_type = 'nabteb_v2';
                break;
            case 'nbais':
                $result_type = 'nbais_v2';
                break;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL =>
            'https://easyaccess.com.ng/api/' . $result_type . '.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'no_of_pins' => intval($qty),
            ],
            CURLOPT_HTTPHEADER => [
                'AuthorizationToken: 607bd98987afb996040bcffa261ff', //replace this with your authorization_token
                // 'AuthorizationToken: 48d95439cb3473b2c0d5de460abbf863', 
                'cache-control: no-cache',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
    /**
     * Fetch all available data plans from the Bardetech API for a given network.
     * Network IDs: 1=MTN, 2=Glo, 3=9mobile, 4=Airtel
     * Returns an array of plan objects on success, or null on failure.
     */
    public function FetchBardePlans(int $network_id)
    {
        $api_settings = $this->db->query("SELECT * FROM api_settings WHERE api_name = 'bardetech' LIMIT 1")->getRow();
        if (!$api_settings) return null;

        $token   = trim($api_settings->api_key);
        $api_url = rtrim(trim($api_settings->api_url), '/');

        // Bardetech plans endpoint: GET /api/network/?network_id=1
        $plans_url = 'https://bardetech.com/api/network/?network_id=' . intval($network_id);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $plans_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Token ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) return null;
        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) return null;

        // API returns either an array directly or {"data":[...]}
        if (is_array($decoded)) return $decoded;
        if (isset($decoded->data) && is_array($decoded->data)) return $decoded->data;
        return null;
    }

}
