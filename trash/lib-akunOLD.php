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
    } else {
        echo "❌ Gagal menambahkan akun ke salah satu tag.\n";
    }
}

function tampilkanSSH($username, $expired, $password) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $ip = gethostbyname($domain);

    // Ambil username asli tanpa prefix reseller (misalnya reseller1_user1 → user1)
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
}

function tampilkanXRAY($proto, $username, $expired, $key) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $tls = "443";
    $ntls = "80";
    $grpcService = $proto . "-grpc";
    $path = "/$proto-ws";

    // Tampilkan username tanpa prefix reseller (jika ada)
    $displayUsername = $username;
    if (preg_match('/^(.+?)_(.+)$/', $username, $match)) {
        $displayUsername = $match[2]; // hanya ambil bagian setelah underscore
    }

    $output = <<<EOL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
          ${proto} ACCOUNT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Remarks        : $displayUsername
Host/IP        : $domain
Wildcard       : (bug.com).$domain
Port TLS       : $tls
Port none TLS  : $ntls
Port gRPC      : $tls
EOL;

    $output .= ($proto === 'vmess' || $proto === 'vless') ? "UUID           : $key\n" : "Password       : $key\n";

    $output .= <<<EOL
Path           : $path
ServiceName    : $grpcService
Expired On     : $expired
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOL;

    switch ($proto) {
        case 'vmess':
            $vmessLink = "vmess://" . base64_encode(json_encode([
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
            ]));
            $output .= "Link TLS       : $vmessLink\n";
            break;
        case 'vless':
            $output .= "Link TLS       : vless://$key@$domain:$tls?path=$path&security=tls&type=ws#$displayUsername\n";
            break;
        case 'trojan':
            $output .= "Link TLS       : trojan://$key@$domain:$tls?path=$path&security=tls&type=ws#$displayUsername\n";
            break;
        case 'shadowsocks':
            $encoded = base64_encode("aes-128-gcm:$key");
            $output .= "Link SS (TLS)  : ss://$encoded@$domain:$tls#$displayUsername\n";
            break;
    }

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

