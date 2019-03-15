//mpesa

    /**
     *register url
     */
    public function register_url()
    {
        $access_token = $this->generateAccessToken();
        //echo 'Authorization:Bearer '.$access_token;
        $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';
        $short_code = '603021';
        $confirmation_url = 'https://demo.dawati.co.ke/confirmation';
        $validation_url = 'https://demo.dawati.co.ke/validation';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token)); //setting custom header

        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'ShortCode' => '603021',
            'ResponseType' => 'Confirmed',
            'ConfirmationURL' => $confirmation_url,
            'ValidationURL' => $validation_url
        );

        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        $curl_response = curl_exec($curl);
        //print_r($curl_response);
        echo $curl_response;
    }

    /**
     *generate access token
     */
    public function generateAccessToken()
    {
        $consumer_key = '8hdfLUWM1nEjaRRhxLCX7xPzp4ffi4kN';
        $consumer_secret = '4fi9kAgT7GfdNMt4';
        $headers = array(
            'Content-Type' => 'application/json',
            'charset' => 'utf8'

        );
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials)); //setting a custom header
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $consumer_key . ':' . $consumer_secret);
        $curl_response = curl_exec($curl);
        $response = json_decode($curl_response);
        $access_token = $response->access_token;
       
        return $access_token;

    }

    /**
     *mpesa validation url
     */

    public function validation()
    {
        header("Content-Type: application/json");
        $response = '{ "ResultCode": 0, "ResultDesc": "Confirmation Received Successfully" }';

        echo $response;
    }

    /**
     * confirmation url
     */
    public function confirmation()
    {
        header("Content-Type: application/json");
        $system_response = '{ "ResultCode": 0, "ResultDesc": "Confirmation Received Successfully" }';

        $mpesa_response = file_get_contents('php://input');
        $decoded_mpesa_response = json_decode($mpesa_response);
        $data = array(

            'transaction_ID' => $decoded_mpesa_response->TransID,
            'transaction_Type' => $decoded_mpesa_response->TransactionType,
            'transaction_Time' => $decoded_mpesa_response->TransTime,
            'transaction_Amount' => $decoded_mpesa_response->TransAmount,
            'business_short_code' => $decoded_mpesa_response->BusinessShortCode,
            'bill_reference_number' => $decoded_mpesa_response->BillRefNumber,
            'invoice_number' => $decoded_mpesa_response->InvoiceNumber,
            'MSISDN' => $decoded_mpesa_response->MSISDN,
            'first_name' => $decoded_mpesa_response->FirstName,
            'middle_name' => $decoded_mpesa_response->MiddleName,
            'last_name' => $decoded_mpesa_response->LastName,
            'org_account_balance' => $decoded_mpesa_response->OrgAccountBalance,
        );

        $this->dawati_model->insert_mpesa_details($data);

        echo $system_response;
    }

    /**
     *This method is used in sandbox to simulate a whole transaction
     */
    public function simulate_transaction()
    {
        $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';

        $access_token = $this->generateAccessToken();
        $ShortCode = '603021'; 
        $amount = '85'; 
        $msisdn = '254708374149'; 
        $billRef = 'inv9775'; 
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token));
        $curl_post_data = array(
            'ShortCode' => $ShortCode,
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'Msisdn' => $msisdn,
            'BillRefNumber' => $billRef
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        $curl_response = curl_exec($curl);
        echo $curl_response;
    }

    public function account_balance()
    {

    }


    public function stk_push()
    {
        $mobile = $this->input->post('mobile');
        $subscription_type = $this->input->post('subscription_type');
        $description = '';
        $formatted_mobile = '';
        $amount = '';
        $regex07 = "^07[0-9]^";
        $regex_plus254 = "^\+254[0-9]^";
        $regex_254= "^254[0-9]^";
        if (preg_match($regex07, $mobile)) {
            $sub_mobile = substr($mobile, 1);
            $formatted_mobile = '254' . $sub_mobile;
        } elseif (preg_match($regex_plus254, $mobile)) {

            $formatted_mobile = str_replace('+', '', $mobile);

        }elseif ((preg_match($regex_254,$mobile))){
            $formatted_mobile = $mobile;
        }
        if ($subscription_type == 'yearly') {
            $description = "Yearly Subscription";
            $amount = '1000';
        } elseif ($subscription_type == 'termly') {
            $amount = '250';
            $description = "Monthly Subscription";
        }
        date_default_timezone_set("Africa/Nairobi");
        $access_token = $this->generateAccessToken(); //call to method to generate access code
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token)); //setting custom header

        //variables
        $business_short_code = '174379'; // same as party B
        $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
        $timestamp = date('YmdHis');
        $password = base64_encode($business_short_code . $passkey . $timestamp);
        $amount = $amount;
        $partyA = $formatted_mobile;  //clients phone number
        $account_reference = $this->session->userdata('username');
        $transaction_desc = $description;

        $curl_post_data = array(
            'BusinessShortCode' => $business_short_code,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $partyA,
            'PartyB' => $business_short_code,
            'PhoneNumber' => $partyA,
            'CallBackURL' => 'https://demo.dawati.co.ke/callBack',
            'AccountReference' => $account_reference,
            'TransactionDesc' => $transaction_desc
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        $curl_response = curl_exec($curl);
        echo $curl_response;
    }

//end mpesa
}
