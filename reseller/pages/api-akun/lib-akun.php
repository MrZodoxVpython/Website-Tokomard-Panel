<?php

function hitungTanggalExpired($expiredInput) {
    return preg_match('/^\d+$/', $expiredInput)
        ? date('Y-m-d', strtotime("+$expiredInput days"))
        : $expiredInput;
}

function insertIntoTag($configPath, $tag, $commentLine, $jsonLine) {
    if (!file_exists($configPath)) return false;
    $lines = file($configPath);
    foreach ($lines as $i => $line) {
        if (strpos($line, "#$tag") !== false) {
            array_splice($lines, $i + 1, 0, [$commentLine . "\n", $jsonLine . "\n"]);
            file_put_contents($configPath, implode("\n", array_map('rtrim', $lines)) . "\n");
            return true;
        }
    }
    return false;
}

function prosesXray($proto, $tagMap, $commentLine, $jsonLine, $username, $expired, $key) {
    $configPath = '/etc/xray/config.json';
    $success = true;
    foreach ($tagMap as $tag) {
        if (!insertIntoTag($configPath, $tag, $commentLine, $jsonLine) && $success) {
            $success = false;
        }
    }

    if ($success) {
        shell_exec('sudo systemctl restart xray');
        tampilkanXRAY($proto, $username, $expired, $key);
        catatLogReseller($_SESSION['username'] ?? 'unknown', $username, $expired);
    } else {
        echo "❌ Gagal menambahkan akun ke salah satu tag.\n";
    }
}

function tampilkanSSH($username, $expired, $password) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $ip = gethostbyname($domain);

    // Ambil username asli tanpa prefix reseller
    $displayUsername = $username;
    if (preg_match('/^(.+?)_(.+)$/', $username, $match)) {
        $displayUsername = $match[2];
    }

    $output = <<<EOL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            SSH ACCOUNT             
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Username       : $displayUsername
Password       : $password
Host/IP        : $domain ($ip)
Port OpenSSH   : 22
Port Dropbear  : 443, 109, 143
Port SSL/TLS   : 443
Expired On     : $expired
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOL;

    tampilkanHTML($output);
    catatLogReseller($_SESSION['username'] ?? 'unknown', $username, $expired);
}

function tampilkanXRAY($proto, $username, $expired, $key) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $tls = "443";
    $ntls = "80";
    $grpcService = $proto . "-grpc";
    $path = "/$proto-ws";

    $displayUsername = $username;
    if (preg_match('/^(.+?)_(.+)$/', $username, $match)) {
        $displayUsername = $match[2];
    }

    $output = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= str_pad(" " . strtoupper($proto) . " ACCOUNT", 35, " ", STR_PAD_BOTH) . "\n";
    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $output .= "Remarks        : $displayUsername\n";
    $output .= "Host/IP        : $domain\n";
    $output .= "Wildcard       : (bug.com).$domain\n";
    $output .= "Port TLS       : $tls\n";
    $output .= "Port none TLS  : $ntls\n";
    $output .= "Port gRPC      : $tls\n";

    $output .= ($proto === 'vmess' || $proto === 'vless') ? "UUID           : $key\n" : "Password       : $key\n";

    $output .= <<<EOL
Path           : $path
ServiceName    : $grpcService
Expired On     : $expired
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n
EOL;

    switch ($proto) {
        case 'vmess':
            $vmessConf = [
                "v" => "2",
                "ps" => $displayUsername,
                "add" => $domain,
                "port" => $tls,
                "id" => $key,
                "aid" => "0",
                "net" => "ws",
                "type" => "none",
                "host" => "",
                "path" => $path,
                "tls" => "tls"
            ];
            $vmessLink = "vmess://" . base64_encode(json_encode($vmessConf));
            $output .= "Link TLS       : $vmessLink\n";
            $vmessConf['port'] = $ntls;
            $vmessConf['tls'] = "none";
            $vmessLinkNonTLS = "vmess://" . base64_encode(json_encode($vmessConf));
            $output .= "Link non-TLS   : $vmessLinkNonTLS\n";
            $grpcLink = "vmess://".base64_encode(json_encode([
                "v" => "2",
                "ps" => $displayUsername,
                "add" => $domain,
                "port" => $tls,
                "id" => $key,
                "aid" => "0",
                "net" => "grpc",
                "type" => "none",
                "host" => "",
                "path" => "",
                "tls" => "tls",
                "serviceName" => $grpcService
            ]));
            $output .= "Link gRPC      : $grpcLink\n";
            break;

        case 'vless':
            $output .= "Link TLS       : vless://$key@$domain:$tls?path=$path&security=tls&type=ws#$displayUsername\n";
            $output .= "Link non-TLS   : vless://$key@$domain:$ntls?path=$path&security=none&type=ws#$displayUsername\n";
            $output .= "Link gRPC      : vless://$key@$domain:$tls?mode=gun&security=tls&type=grpc&serviceName=$grpcService#$displayUsername\n";
            break;

        case 'trojan':
            $output .= "Link TLS       : trojan://$key@$domain:$tls?path=$path&security=tls&type=ws#$displayUsername\n";
            $output .= "Link non-TLS   : trojan://$key@$domain:$ntls?path=$path&security=none&type=ws#$displayUsername\n";
            $output .= "Link gRPC      : trojan://$key@$domain:$tls?mode=gun&security=tls&type=grpc&serviceName=$grpcService#$displayUsername\n";
            break;

        case 'shadowsocks':
            $encoded = base64_encode("aes-128-gcm:$key");
            $output .= "Link SS (TLS)  : ss://$encoded@$domain:$tls#$displayUsername\n";
            $output .= "Link SS (non)  : ss://$encoded@$domain:$ntls#$displayUsername\n";
            break;
    }

    $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    // Simpan log akun ke file akun-reseller-username.txt
    $reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';
    catatLogReseller($reseller, $username, $expired, $output);
    tampilkanHTML($output);
}


function tampilkanHTML($content) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Output</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex justify-center items-center min-h-screen">
  <div class="max-w-full w-[95%] md:w-[70%] lg:w-[60%] xl:w-[50%] overflow-auto">
    <pre class="bg-gray-900 text-green-400 border-2 border-orange-400 rounded-xl p-4 text-sm font-mono whitespace-pre-wrap leading-relaxed shadow-md">
$content
    </pre>
  </div>
</body>
</html>
HTML;
}
function catatLogReseller($reseller, $usernamePembeli, $expired, $detailAkun) {
    $logDir = "/etc/xray/data-panel/reseller";
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Nama file: akun-usernameReseller-usernamePembeli.txt
    $safeReseller = preg_replace('/[^a-zA-Z0-9_\-]/', '', $reseller);
    $safeUsername = preg_replace('/[^a-zA-Z0-9_\-]/', '', $usernamePembeli);
    $logFile = "$logDir/akun-{$safeReseller}-{$safeUsername}.txt";

    // Simpan isi detail akun ke file
    if (is_writable($logDir)) {
        file_put_contents($logFile, $detailAkun);
    } else {
        error_log("❌ Tidak bisa menulis ke $logFile");
    }
}

