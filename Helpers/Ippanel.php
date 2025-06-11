<?php

namespace Helpers;

use function App\Helpers\env;

class Ippanel
{
    public function sendPattern($pattern_code, $to, $input_data = array())
    {

        $username = env('IPPANEL_USERNAME');
        $password = env('IPPANEL_PASSWORD');
        $from = env('IPPANEL_ORIGINATOR_PATTERN');


        $url = "https://example.com/patterns/pattern?username=" . $username . "&password=" . urlencode($password) . "&from=$from&to=" . json_encode($to) . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=$pattern_code";
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $input_data);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);
        echo $response;

    }

    function sendSms($recipient, $message, $time = null)
    {
        $apiKey = env('IPPANEL_APIKEY');
        $sender = env('IPPANEL_ORIGINATOR');
        $url = 'https://example.com/api/v1/sms/send/webservice/single';

        if ($time === null) {
            $time = date('c'); // ISO8601 format like: 2025-03-21T09:12:50.824Z
        }

        $data = [
            'recipient' => [$recipient],
            'sender' => $sender,
            'time' => $time,
            'message' => $message
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'apikey: ' . $apiKey,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            return ['success' => false, 'error' => $error];
        }

        curl_close($curl);
        return [
            'success' => $httpCode === 200,
            'status' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }

}
