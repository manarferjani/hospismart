<?php
if ($argc < 2) {
    echo "Usage: php simulate_delete.php <medicamentId>\n";
    exit(1);
}
$id = (int)$argv[1];
$base = 'http://127.0.0.1:9000';
$cookieFile = __DIR__ . '/cookies_sim.txt';

// GET the show page
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$base/medicament/$id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);
if ($err) {
    echo "GET error: $err\n";
    exit(2);
}
// extract token
if (preg_match('/name="_token" value="([^"]+)"/', $html, $m)) {
    $token = $m[1];
    echo "Found token: $token\n";
} else {
    echo "Token not found in page.\n";
    file_put_contents(__DIR__ . '/show_page.html', $html);
    exit(3);
}

// POST delete
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$base/medicament/$id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['_token' => $token]);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);
if ($err) {
    echo "POST error: $err\n";
    exit(4);
}
echo "POST HTTP code: " . ($info['http_code'] ?? 'n/a') . "\n";
file_put_contents(__DIR__ . '/post_response.html', $response);
echo "Saved response to scripts/post_response.html\n";

// show last lines of dev.log for context
$log = @file_get_contents(__DIR__ . '/../var/log/dev.log');
if ($log !== false) {
    $last = implode("\n", array_slice(explode("\n", $log), -200));
    file_put_contents(__DIR__ . '/devlog_tail.txt', $last);
    echo "Saved log tail to scripts/devlog_tail.txt\n";
}
