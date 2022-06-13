<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Amazon extends Model
{
    private $roleARN;
    private $accessKey;
    private $secretKey;
    private $clientID;
    private $clientSecret;
    private $refreshToken;
    private $accessToken;    
    private $stsAccessKeyId;
    private $stsSecretAccessKey;
    private $stsSessionToken;
    private $restrictedDataToken;
    private $host = 'sandbox.sellingpartnerapi-na.amazon.com';    
    private $userAgent = 'cs-php-sp-api-client/2.1';
    private $durationSeconds = 3600;

    public function __construct($roleARN, $accessKey, $secretKey, $clientID, $clientSecret, $refreshToken)
    {
        $this->roleARN = $roleARN;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
        $this->getToken();
        $this->getSessionToken();
    }

    
    public function getToken($RDToken = '')
    {
        dd('svf');
        $url = 'https://api.amazon.com/auth/o2/token';
        if(!empty($RDToken))
            $postParam = 'grant_type=refresh_token&refresh_token='.$RDToken.'&client_id='.$this->clientID.'&client_secret='.$this->clientSecret;
        else
            $postParam = 'grant_type=refresh_token&refresh_token='.$this->refreshToken.'&client_id='.$this->clientID.'&client_secret='.$this->clientSecret;
        $response = $this->callAPI($url, 'POST', $headers = array('Content-Type: application/x-www-form-urlencoded','Cookie: aws_lang=en'), $postParam);
        /*if(!empty($RDToken))
        {
            echo $response;
            exit;
        }*/
        /*echo $response;
        exit;*/
        $response = json_decode($response, true);
       
        $this->accessToken = $response['access_token'];
    }
    public function getOrders($marketplace_ids, $created_after, $created_before, $last_updated_after, $last_updated_before, $order_statuses, $fulfillment_channels, $payment_methods, $buyer_email, $seller_order_id, $max_results_per_page, $easy_ship_shipment_statuses, $next_token, $amazon_order_ids)
    {
        
        $query = 'AmazonOrderIds='.implode(",", $amazon_order_ids).'&BuyerEmail='.$buyer_email.'&CreatedAfter='.$created_after.'&CreatedBefore='.$created_before.'&EasyShipShipmentStatuses='.implode(",", $easy_ship_shipment_statuses).'&FulfillmentChannels='.implode(",", $fulfillment_channels).'&LastUpdatedAfter='.$last_updated_after.'&LastUpdatedBefore='.$last_updated_before.'&MarketplaceIds='.$marketplace_ids.'&MaxResultsPerPage='.$max_results_per_page.'&NextToken='.$next_token.'&OrderStatuses='.implode(",", $order_statuses).'&PaymentMethods='.implode(",", $payment_methods).'&SellerOrderId='.$seller_order_id;
        // $query = 'MarketplaceIds='.$marketplace_ids.'&OrderStatuses='.implode(",", $order_statuses).'&CreatedAfter='.$created_after.'&LastUpdatedAfter='.$last_updated_after;

        // $this->getRDToken('',$query);


        $header = self::calculateSignatureForService($this->host, 'GET', '/orders/v0/orders', $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        $orderHeader = [];
        // echo $this->restrictedDataToken;
        // array_push($orderHeader, "x-amz-access-token: ".$this->restrictedDataToken);
        foreach($header as $key => $value)
        {
            /*if($key == 'x-amz-access-token')
                array_push($orderHeader, $key.": ".$this->restrictedDataToken);
            else*/
                array_push($orderHeader, $key.": ".$value);
        }
        array_push($orderHeader, 'Content-Type: application/json');
        array_push($orderHeader, 'Accept: application/json');

        $URL = 'https://'.$this->host.'/orders/v0/orders';
        
        /*echo '<pre>';
        echo $URL.'?'.$query;
        print_r($orderHeader);
        exit();*/

        $orderList = $this->callAPI($URL.'?'.$query, 'GET', $orderHeader);
        $orders = json_decode($orderList)->payload;
        
        return $orders->Orders;
    }
    public function getOrderItem($orderID)
    {
        $query = '';
        $header = self::calculateSignatureForService($this->host, 'GET', '/orders/v0/orders/'.$orderID.'/orderItems', $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Accept: application/json');
        $orderItemHeader = [];
        foreach($header as $key => $value)
        {
            array_push($orderItemHeader, $key.": ".$value);
        }

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/orders/v0/orders/'.$orderID.'/orderItems';

        $orderItem = $this->callAPI($URL, 'GET', $orderItemHeader);
        return $orderItem;
    }
    public function getOrderAddress($orderID)
    {
        $query = '';
        $header = self::calculateSignatureForService($this->host, 'GET', '/orders/v0/orders/'.$orderID.'/address', $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Accept: application/json');
        $orderItemHeader = [];
        foreach($header as $key => $value)
        {
            array_push($orderItemHeader, $key.": ".$value);
        }

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/orders/v0/orders/'.$orderID.'/address';

        $orderAddress = $this->callAPI($URL, 'GET', $orderItemHeader);
        return $orderAddress;
    }
    public function getOrderBuyer($orderID)
    {
        $query = '';
        $header = self::calculateSignatureForService($this->host, 'GET', '/orders/v0/orders/'.$orderID.'/buyerInfo', $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Accept: application/json');
        $orderItemHeader = [];
        foreach($header as $key => $value)
        {
            array_push($orderItemHeader, $key.": ".$value);
        }

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/orders/v0/orders/'.$orderID.'/buyerInfo';

        $orderAddress = $this->callAPI($URL, 'GET', $orderItemHeader);
        return $orderAddress;
    }
    public function getItemDetails($marketplaceID, $ASIN)
    {
        $query = 'MarketplaceId='.$marketplaceID;
        $header = self::calculateSignatureForService($this->host, 'GET', '/catalog/v0/items/'.$ASIN, $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        $itemHeader = [];
        foreach($header as $key => $value)
        {
            array_push($itemHeader, $key.": ".$value);
        }
        array_push($itemHeader, 'Content-Type: application/json');
        array_push($itemHeader, 'Accept: application/json');

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/catalog/v0/items/'.$ASIN;

        echo '<pre>';
        echo $URL.'?'.$query;
        print_r($itemHeader);
        exit;

        $itemDetail = $this->callAPI($URL.'?'.$query, 'GET', $itemHeader);
        return $itemDetail;
    }
    public function getShippingRate($data)
    {
        $query = '';
        $header = self::calculateSignatureForService($this->host, 'POST', '/shipping/v1/rates', $query, $data, 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Accept: application/json');
        $rateHeader = [];
        foreach($header as $key => $value)
        {
            array_push($rateHeader, $key.": ".$value);
        }
        echo '<pre>';
        echo json_encode($data);
        print_r($rateHeader);
        exit();
        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/shipping/v1/rates';

        $rateDetail = $this->callAPI($URL, 'POST', $rateHeader, json_encode($data));
        return $rateDetail;
    }

    public function createReport($parameter){

        //$parameter = array("reportOptions"=>array(), "reportType" => $reportType, "dataStartTime"=>"2020-09-22T08:41:00.794Z", "dataEndTime" => "2021-11-09T11:30:10.802Z", "marketplaceIds"=> array($marketplaceIds));
        //echo "<pre>";print_r($parameter);exit;
        
        $query = '';
        $data = json_encode($parameter, 0, 512);
        $data = (string)$data;
        $header = self::calculateSignatureForService($this->host, 'POST', '/reports/2021-06-30/reports', $query, $data, 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        $reportheader = [];
        foreach($header as $key => $value)
        {
            array_push($reportheader, $key.": ".$value);
        }
        array_push($reportheader, 'Content-Type: application/json');
        array_push($reportheader, 'Accept: application/json');
        array_push($reportheader, 'Content-Length: '.strlen($data));

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/reports/2021-06-30/reports';   

        $feedback = $this->callAPI($URL, 'POST', $reportheader, $data);
        return $feedback;
    }

    public function getReport($reportId){
        $query = '';
        $header = self::calculateSignatureForService($this->host, 'GET', '/reports/2021-06-30/reports/'.$reportId, $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Accept: application/json');
        $orderItemHeader = [];
        foreach($header as $key => $value)
        {
            array_push($orderItemHeader, $key.": ".$value);
        }

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/reports/2021-06-30/reports/'.$reportId;

        $orderItem = $this->callAPI($URL, 'GET', $orderItemHeader);
        return $orderItem;
    }

    public function getReportDocument($reportDocumentId){
        $query = '';
        $header = self::calculateSignatureForService($this->host, 'GET', '/reports/2021-06-30/documents/'.$reportDocumentId, $query, '', 'execute-api', $this->stsAccessKeyId, $this->stsSecretAccessKey,'us-east-1', $this->accessToken, $this->stsSessionToken, $this->userAgent);
        array_push($header, 'Content-Type: application/json');
        array_push($header, 'Accept: application/json');
        $orderItemHeader = [];
        foreach($header as $key => $value)
        {
            array_push($orderItemHeader, $key.": ".$value);
        }

        $URL = 'https://sandbox.sellingpartnerapi-na.amazon.com/reports/2021-06-30/documents/'.$reportDocumentId;

        $orderItem = $this->callAPI($URL, 'GET', $orderItemHeader);
        return $orderItem;
    }

    private function callAPI($URL, $method = 'GET', $headers = [], $postParam = '')
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $URL,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_POSTFIELDS => $postParam,
          CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function getSessionToken()
    {
        $url = 'https://sts.amazonaws.com';
        $method = 'POST';
        $uri = '/';
        $formData = array('Action'          =>  'AssumeRole',
                          'DurationSeconds' =>  '3600',
                          'RoleArn'         =>  $this->roleARN,
                          'RoleSessionName' =>  'amazon-sp-api-php',
                          'Version'         =>  '2011-06-15');

        $signedHeader = self::calculateSignatureForService(
                $host = str_replace('https://','',$url),
                'POST',
                $uri,
                '',
                http_build_query($formData),
                'sts',
                $this->accessKey,
                $this->secretKey,
                'us-east-1',
                null,
                null,
                'cs-php-sp-api-client/2.1'
            );
        $stsHeader = [];
        foreach($signedHeader as $key => $value)
        {
            array_push($stsHeader, $key.": ".$value);
        }
        array_push($stsHeader,'Accept: application/json');
        array_push($stsHeader,'Content-Type: application/x-www-form-urlencoded');
        $stsResponse = $this->callAPI($url, 'POST', $stsHeader, http_build_query($formData));
        $json = json_decode($stsResponse, true);
        $credentials = $json['AssumeRoleResponse']['AssumeRoleResult']['Credentials'] ?? null;
        echo "<pre>";print_r($json);exit;
        $this->stsAccessKeyId = isset($credentials['AccessKeyId'])?$credentials['AccessKeyId']:null;
        $this->stsSecretAccessKey = isset($credentials['SecretAccessKey'])?$credentials['SecretAccessKey']:null;
        $this->stsSessionToken = isset($credentials['SessionToken'])?$credentials['SessionToken']:null;
    }
    public static function calculateSignatureForService(
        string $host,
        string $method,
        $uri,
        $queryString,
        $data,
        string $service,
        string $accessKey,
        string $secretKey,
        string $region,
        $accessToken,
        $securityToken,
        $userAgent
    ): array {
        $terminationString = 'aws4_request';
        $algorithm = 'AWS4-HMAC-SHA256';
        $amzdate = gmdate('Ymd\THis\Z');
        $date = substr($amzdate, 0, 8);

        // Prepare payload
        if (is_array($data)) {
            $param = json_encode($data);
            if ('[]' == $param) {
                $requestPayload = '';
            } else {
                $requestPayload = $param;
            }
        } else {
            $requestPayload = $data;
        }

        // Hashed payload
        $hashedPayload = hash('sha256', $requestPayload);

        //Compute Canonical Headers
        $canonicalHeaders = [
            'host' => $host,
            'user-agent' => $userAgent,
        ];

        // Check and attach access token to request header.
        if (!is_null($accessToken)) {
            $canonicalHeaders['x-amz-access-token'] = $accessToken;
        }
        $canonicalHeaders['x-amz-date'] = $amzdate;
        // Check and attach STS token to request header.
        if (!is_null($securityToken)) {
            $canonicalHeaders['x-amz-security-token'] = $securityToken;
        }

        $canonicalHeadersStr = '';
        foreach ($canonicalHeaders as $h => $v) {
            $canonicalHeadersStr .= $h.':'.$v."\n";
        }
        $signedHeadersStr = join(';', array_keys($canonicalHeaders));

        //Prepare credentials scope
        $credentialScope = $date.'/'.$region.'/'.$service.'/'.$terminationString;

        //prepare canonical request
        $canonicalRequest = $method."\n".$uri."\n".$queryString."\n".$canonicalHeadersStr."\n".$signedHeadersStr."\n".$hashedPayload;

        //Prepare the string to sign
        $stringToSign = $algorithm."\n".$amzdate."\n".$credentialScope."\n".hash('sha256', $canonicalRequest);

        //Start signing locker process
        //Reference : https://docs.aws.amazon.com/general/latest/gr/signature-version-4.html
        $kSecret = 'AWS4'.$secretKey;
        $kDate = hash_hmac('sha256', $date, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', $terminationString, $kService, true);

        //Compute the signature
        $signature = trim(hash_hmac('sha256', $stringToSign, $kSigning));

        //Finalize the authorization structure
        $authorizationHeader = $algorithm." Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeadersStr}, Signature={$signature}";

        return array_merge($canonicalHeaders, [
            'Authorization' => $authorizationHeader,
        ]);
    }
}
