<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;
use App\Integration\Walmart;
use App\Product;
use App\WalmartItemAlert;
use App\WalmartSettings;
use App\User;
use Illuminate\Support\Facades\Config;

class WalmartGetAllTemsController extends Controller
{
    public function index()
    {
        return view('walmartIntegration.walmart');
    }

    public function checkProduct(Request $request)
    {
        ini_set('max_execution_time', '700');
        $client_id = $request->get('clientID');
        $secret = $request->get('clientSecretID');

        $this->validate($request, [

            'clientName' => 'required',
            'clientID' => 'required',
            'clientSecretID' => 'required',

        ]);
        //End of validation

        $token = Walmart::getToken($client_id, $secret);
        $token = $token['access_token'];  // Token generated

        $total_records = Walmart::getItemTotal($client_id, $secret, $token);

        if($total_records > 0){

        $per_page = Config::get('constants.walmart.per_page');  // 100 Records on per page
        $no_of_pages = $total_records / $per_page; // Total record divided into per page

        for ($i = 0; $i < $no_of_pages; $i++) {

            $offset = $i * $per_page;
            $url = "https://marketplace.walmartapis.com/v3/items?offset=" . $offset . "&limit=" . $per_page;
            $requestID = uniqid();
            $authorization = base64_encode($client_id . ":" . $secret);

            $curl = curl_init();

            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'WM_SVC.NAME: Walmart Marketplace',
                    'Authorization: Basic ' . $authorization,
                    'WM_QOS.CORRELATION_ID: ' . $requestID,
                    'WM_SEC.ACCESS_TOKEN: ' . $token,
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Cookie: TS01f4281b=0130aff232afca32ba07d065849e80b32e6ebaf11747c58191b2b4c9d5dd53a042f7d890988bf797d7007bddb746c3b59d5ee859d0'
                ),

                CURLOPT_HTTPGET => true,
            );

            curl_setopt_array($curl, $options);
            $response = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            $response = json_decode($response, true);
            $res = $response['ItemResponse'];

            if (count($res) > 0) {
                foreach ($response['ItemResponse'] as $items) {
                    if ($items['publishedStatus'] === "SYSTEM_PROBLEM") {

                        $unpublishedReasons = '';
                        if (array_key_exists('unpublishedReasons', $items)) {
                            $unpublished = $items['unpublishedReasons']['reason'];
                            $unpublishedReasons = implode(' ', $unpublished);
                        }

                        $exp = explode(" ", $unpublishedReasons);
                        $alert_type = '';
                        if (in_array("intellectual", $exp)) {
                            $alert_type = Config::get('constants.walmart.ip_claim');
                        }
                        if (in_array("compliance", $exp)) {
                            $alert_type = 'regulatory_compliance';
                        }
                        if (in_array("partnered", $exp)) {
                            $alert_type = 'brand_partnership_violation';
                        }
                        if (in_array("Safety", $exp)) {
                            $alert_type = 'offensive_product';
                        }
                        $walmartAlerts = WalmartItemAlert::create([
                            'sku' => $items['sku'] ? $items['sku'] : '',
//                          'wpid' => $items['wpid'] ? $items['wpid'] : '',
                            'product_name' => isset($items['productName']) ? $items['productName'] : '',
                            'reason' => $unpublishedReasons,
                            'alert_type' => $alert_type,
                            'status' => $items['publishedStatus'] ? $items['publishedStatus'] : '',
                            'product_url' => $items['wpid'] ? $items['wpid'] : '',
                        ]);

                    }
                }
                //End loop

            }

        }
        // End of for loop

        $walmartData = WalmartItemAlert::all()->groupBy('alert_type');
        // Get data from DB to send email

        $user = User::where('id', '=', '26')->get()->first();
        $email = $user->email;
        // match condition to unique user

        if (!empty($email)) {
            if (isset($walmartData['ip_claim']) && count($walmartData['ip_claim']) > 0) {
                $detail = [];
                foreach ($walmartData['ip_claim'] as $ipClaim) {
                    $detail[] = [
                        'productID' => $ipClaim['sku'],
                        'productName' => $ipClaim['product_name'],
                        'publishedStatus' => $ipClaim['status'],
                        'reason' => $ipClaim['reason'],
                        'AlertType' => $ipClaim['alert_type'] ? 'IP Claim Alert' : '',
                        'productLink' => "https://www.walmart.com/ip/" . $ipClaim['sku'],
                        'userEmail' => $email
                    ];
                }
                Mail::to($email)->send(new SendMail($detail));
            }
            // IP Claim condition

            if (isset($walmartData['offensive_product']) && count($walmartData['offensive_product']) > 0) {
                $detail = [];
                foreach ($walmartData['offensive_product'] as $offensiveProduct) {
                    $detail[] = [
                        'productID' => $offensiveProduct['sku'],
                        'productName' => $offensiveProduct['product_name'],
                        'publishedStatus' => $offensiveProduct['status'],
                        'reason' => $offensiveProduct['reason'],
                        'AlertType' => $offensiveProduct['alert_type'] ? 'Offensive Product Alert' : '',
                        'productLink' => "https://www.walmart.com/ip/" . $offensiveProduct['sku'],
                        'userEmail' => $email
                    ];
                }

                Mail::to($email)->send(new SendMail($detail));
            }
            // Offensive Product

            if (isset($walmartData['regulatory_compliance']) && count($walmartData['regulatory_compliance']) > 0) {
                $detail = [];
                foreach ($walmartData['regulatory_compliance'] as $regulatoryCompliance) {
                    $detail[] = [
                        'productID' => $regulatoryCompliance['sku'],
                        'productName' => $regulatoryCompliance['product_name'],
                        'publishedStatus' => $regulatoryCompliance['status'],
                        'reason' => $regulatoryCompliance['reason'],
                        'AlertType' => $regulatoryCompliance['alert_type'] ? 'Regulatory Compliance Alert' : '',
                        'productLink' => "https://www.walmart.com/ip/" . $regulatoryCompliance['sku'],
                        'userEmail' => $email
                    ];
                }

                Mail::to($email)->send(new SendMail($detail));
            }
            // regulatory compliance Product

            if (isset($walmartData['brand_partnership_violation']) && count($walmartData['brand_partnership_violation']) > 0) {
                $detail = [];
                foreach ($walmartData['brand_partnership_violation'] as $brandPartnershipViolation) {
                    $detail[] = [
                        'productID' => $brandPartnershipViolation['sku'],
                        'productName' => $brandPartnershipViolation['product_name'],
                        'publishedStatus' => $brandPartnershipViolation['status'],
                        'reason' => $brandPartnershipViolation['reason'],
                        'AlertType' => $brandPartnershipViolation['alert_type'] ? 'Walmart Brand Partnership Violation' : '',
                        'productLink' => "https://www.walmart.com/ip/" . $brandPartnershipViolation['sku'],
                        'userEmail' => $email
                    ];
                }

                Mail::to($email)->send(new SendMail($detail));
            }
            // brand Partnership Violation Product

        }
        // Email is here

    }
     // End of total items condition
        return redirect()->back()->withSuccess('Email Has Been Sent Successfully');

    }
    //End function

    function emailTemplate()
    {
        return view('email.sendemail');
    }

}
// End of WalmartGetAllTemsController class