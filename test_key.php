<?php
// Single test - gemini-2.5-flash only
$key = 'AIzaSyCN57ucyicr2EcMP9lIYKKnPH7f1N_A2k0';
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$key";
$data = json_encode(['contents' => [['parts' => [['text' => 'Say OK']]]]]);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP $httpCode\n";
echo substr($response, 0, 500) . "\n";
