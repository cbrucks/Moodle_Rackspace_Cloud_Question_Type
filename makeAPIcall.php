<?php

// initiailize values
$url = '';
$command_type = 'GET';
$json_string = '';
$extra_headers = array();

// get query string
$query_string = $_SERVER['QUERY_STRING'];

// parse out values from query string
parse_str($query_string);

$headers = array(
    'Content-Type: application/json',
    'Accept: application/json',
    );
$headers = array_merge($headers, $extra_headers);

// Perform the cURL request
$curl_ch = curl_init($url);
curl_setopt($curl_ch, CURLINFO_HEADER_OUT, 1);  // Output message is displayed
curl_setopt($curl_ch, CURLOPT_RETURNTRANSFER, 1);  // Make silent
curl_setopt($curl_ch, CURLOPT_CUSTOMREQUEST, $command_type);  // HTTP Post
curl_setopt($curl_ch, CURLOPT_HTTPHEADER, $headers);  // Set headers
curl_setopt($curl_ch, CURLOPT_POSTFIELDS, $json_string);  // Set data
$curl_result = curl_exec($curl_ch);
curl_close($curl_ch);

// Parse the returned json string
echo $curl_result;

?>
