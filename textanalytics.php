<?php

// NOTE: Be sure to uncomment the following line in your php.ini file.
// ;extension=php_openssl.dll

// **********************************************
// *** Update or verify the following values. ***
// **********************************************

// Replace the accessKey string value with your valid access key.
$accessKey = '4c6d713603d64163a569c36334862fb8';

// Replace or verify the region.

// You must use the same region in your REST API call as you used to obtain your access keys.
// For example, if you obtained your access keys from the westus region, replace 
// "westcentralus" in the URI below with "westus".

// NOTE: Free trial access keys are generated in the westcentralus region, so if you are using
// a free trial access key, you should not need to change this region.
$host = 'http://192.168.102.51:5000';
$path = '/text/analytics/v2.1/sentiment';

function GetSentiment ($host, $path, $key, $data) {

	$headers = "Content-type: text/json\r\n" .
		"Ocp-Apim-Subscription-Key: $key\r\n";

	$data = json_encode ($data);

	// NOTE: Use the key 'http' even if you are making an HTTPS request. See:
	// https://php.net/manual/en/function.stream-context-create.php
	$options = array (
		'http' => array (
			'header' => $headers,
			'method' => 'POST',
			'content' => $data
		)
	);
	$context  = stream_context_create ($options);
	$result = file_get_contents ($host . $path, false, $context);
	return $result;
}

$phrase = $_GET['inputText'];
$data = array (
	'documents' => array (
		array ( 'id' => '1', 'language' => 'en', 'text' => $phrase ),
	)
);

#print "Please wait a moment for the results to appear. <br>";



$result = GetSentiment ($host, $path, $accessKey, $data);
//print_r($data);
//echo "<br>";
echo json_encode (json_decode ($result), JSON_PRETTY_PRINT);
#echo json_encode($result);
?>