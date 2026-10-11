<?php
// Smoke test HTTP via built-in server (dijalankan manual)
$base = 'http://localhost:8003';
function check($method, $path, $expect) {
    global $base;
    for ($i = 0; $i < 4; $i++) {
        $ch = curl_init($base . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code !== 0) break;
        sleep(1);
    }
    $ok = ($code === $expect) ? 'PASS' : 'FAIL';
    echo "$ok $method $path => $code (expect $expect)\n";
    return $code === $expect;
}
$all = true;
$all &= check('GET', '/', 200);
$all &= check('GET', '/login', 200);
$all &= check('GET', '/register', 200);
$all &= check('GET', '/home', 302);
$all &= check('GET', '/tidak-ada-xyz', 404);
$all &= check('GET', '/api/packages', 200);
exit($all ? 0 : 1);
