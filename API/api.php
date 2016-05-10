<?php
header('Content-Type: application/json; charset=utf-8');
$url    = 'https://mcpehub.com/login';
$fields = array(
    'username' => urlencode($_GET['player']),
    'password' => urlencode($_GET['token'])
);
foreach ($fields as $key => $value) {
    $fields_string .= $key . '=' . $value . '&';
}
rtrim($fields_string, '&');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, TRUE); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
$response = curl_exec($ch);
preg_match_all('/^Location:(.*)$/mi', $response, $matches);
curl_close($ch);
$site = !empty($matches[1]) ? trim($matches[1][0]) : 'No redirect found';
if ($site == "/dashboard?welcome") {
    $status = true;
} else {
    $status = false;
}
$arr = array(
    'login' => $status
);
echo json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>