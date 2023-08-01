<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreatedMail;
use App\Services\SallaAuthService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class OAuthController extends Controller
{
    /**
     * @var SallaAuthService
     */
    private $service;

    public function __construct(SallaAuthService $service)
    {
        $this->service = $service;
    }

    public function redirect()
    {
        return redirect($this->service->getProvider()->getAuthorizationUrl());
    }

    public function callback(Request $request)
    {
        abort_if($this->service->isEasyMode(), 401, 'The Authorization mode is not supported');

        // Try to obtain an access token by utilizing the authorisations code grant.
        try {
            $token = $this->service->getAccessToken('authorization_code', [
                'code' => $request->code ?? ''
            ]);

            /** @var \Salla\OAuth2\Client\Provider\SallaUser $user */
            $user = $this->service->getResourceOwner($token);
            /**
             *  {
             *      "id": 181690847,
             *      "name": "eman elsbay",
             *      "email": "user@salla.sa",
             *      "mobile": "555454545",
             *      "role": "user",
             *      "created_at": "2018-04-28 17:46:25",
             *      "store": {
             *        "id": 633170215,
             *        "owner_id": 181690847,
             *        "owner_name": "eman elsbay",
             *        "username": "good-store",
             *        "name": "متجر الموضة",
             *        "avatar": "https://cdn.salla.sa/XrXj/g2aYPGNvafLy0TUxWiFn7OqPkKCJFkJQz4Pw8WsS.jpeg",
             *        "store_location": "26.989000873354787,49.62477639657287",
             *        "plan": "special",
             *        "status": "active",
             *        "created_at": "2019-04-28 17:46:25"
             *      }
             *    }
             */
            // var_export($user->toArray());

            // echo 'User ID: '.$user->getId()."<br>";
            // echo 'User Name: '.$user->getName()."<br>";
            // echo 'Store ID: '.$user->getStoreID()."<br>";
            // echo 'Store Name: '.$user->getStoreName()."<br>";

            //
            // 🥳
            //
            // You can now save the access token and refresh token in your database
            // with the merchant details and redirect him again to Salla dashboard (https://s.salla.sa/apps)
            $request->user()->token()->delete();

            $request->user()->token()->create([
                'access_token'  => $token->getToken(),
                'expires_in'    => $token->getExpires(),
                'refresh_token' => $token->getRefreshToken()
            ]);

            // TODO :: change it later to https://s.salla.sa/apps before go alive
            return redirect('/dashboard');
        } catch (IdentityProviderException $e) {
            // Failed to get the access token or merchant details.
            // show an error message to the merchant with good UI
            return redirect('/dashboard')->withStatus($e->getMessage());
        }
    }

    public function webhook(Request $request)
    {
        Log::info("WEBHOOK WORKING");

        if ($request->event == "app.store.authorize") {

            $object['status'] = true;
            $object['message'] = "App Authorization Saved or Updated";
            $object['data'] = null;

            Log::Info($object);
            return $object;
        }

        if ($request->event == "order.created") {

            $payload = $request;

            // Log::info($payload);

            $name = $payload->data['customer']['first_name'] . ' ' . $payload->data['customer']['last_name'];
            $orderId = $payload->data['reference_id'];
            $ean = $payload->data['items'][0]['product']['sku'];
            $obj = (object) [
                'username' => 'serial.single',
                'data' => (object) [
                    "ean" => $ean,
                    "terminal_id" => 'ss123456',
                    "order_id" => $payload->data['id'],
                    "request_type" => "purchase",
                    "response_type" => "short"

                ],
            ];
            $data = json_encode($obj);
            $data_array = json_decode($data, true);

            //Log::info(json_encode($obj, JSON_PRETTY_PRINT));
            $secret_key = '018499c5ec56b5fab63c6e47576caac6';
            $http_verb = 'POST';
            $url_encoded_data = http_build_query($data_array);

            $currentDate = Carbon::now();
            $date = $currentDate->format('Ymd\THi');

            //$date = gmdate('Ymd\THi');
            $string_to_sign = $http_verb . $url_encoded_data . $date;
            Log::info($string_to_sign);
            //$hmac_hash = hash_hmac('sha256', $string_to_sign, $secret_key, true);
            $signature = base64_encode(hash_hmac(
                'sha256',
                $string_to_sign,
                $secret_key,
                true
            ));

            $timestring = $currentDate->format('Ymd\THis\Z');
            Log::info($currentDate);
            Log::info($timestring);

            $curl = curl_init();
            $algorithm = "hmac-sha256";
            $credential = "mHFwwqEp/" . $currentDate->format('Ymd');
            $auth_key = 'algorithm="' . $algorithm . '",' . 'credential="' . $credential . '",' . 'signature="' . $signature . '"';

            Log::info($auth_key);
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://mconnect2.mintroute.com/voucher/v2/api/voucher',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($obj),
                CURLOPT_HTTPHEADER => array(
                    "Accept: application/json",
                    'Content-Type: application/json',
                    "Authorization: " . $auth_key,
                    "X-Mint-Date: $timestring"

                ),
            ));
            $response = curl_exec($curl);
            Log::info($response);
            $api_response = json_decode($response, true);

            curl_close($curl);

            if ($api_response['status'] == 'true') {
                try {
                    $serialNumber = $api_response['data']['voucher']['pin_code'];
                    $response = Mail::to($payload->data['customer']['email'])->send(new OrderCreatedMail($serialNumber, $name, $orderId));
                    Log::info("email sent");
                } catch (Exception $e) {
                    Log::info($e);
                }
            }

            $object['status'] = true;
            $object['message'] = "Order Recieved";
            $object['data'] = null;

            Log::Info($object);
            return $object;
        }

        if ($request->event == "app.uninstalled") {

            $object['status'] = true;
            $object['message'] = "App Uninstalled Successfully";
            $object['data'] = null;

            Log::Info($object);
            return $object;
        }
    }
}
