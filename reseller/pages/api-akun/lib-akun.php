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

function tampilkanSSH($username, $expired, $key) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $ip = gethostbyname($domain);
    echo <<<EOL
<div style="background-color: #111827; color: #00ff7f; padding: 1em; border-radius: 10px; border: 2px solid #f97316;">
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            SSH ACCOUNT             
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Username       : $username
Password       : $key
Host/IP        : $domain ($ip)
Port OpenSSH   : 22
Port Dropbear  : 443, 109, 143
Port SSL/TLS   : 443
Expired On     : $expired
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
</div>
EOL;
}

function tampilkanXRAY($proto, $username, $expired, $key) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $tls = "443";
    $ntls = "80";
    $grpcService = $proto . "-grpc";
    $path = "/$proto-ws";

    echo <<<EOL
<div style="background-color: #111827; color: #00ff7f; padding: 1em; border-radius: 10px; border: 2px solid #f97316;">
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
          ${proto} ACCOUNT           
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Remarks        : $username
Host/IP        : $domain
Wildcard       : (bug.com).$domain
Port TLS       : $tls
Port none TLS  : $ntls
Port gRPC      : $tls
EOL;

    echo ($proto === 'vmess' || $proto === 'vless') ? "UUID           : $key\n" : "Password       : $key\n";

    echo <<<EOL
Path           : $path
ServiceName    : $grpcService
Expired On     : $expired
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOL;

    switch ($proto) {
        case 'vmess':
            $vmessLink = "vmess://" . base64_encode(json_encode([
                "v" => "2",
                "ps" => $username,
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
            echo "Link TLS       : $vmessLink\n";
            break;
        case 'vless':
            echo "Link TLS       : vless://$key@$domain:$tls?path=$path&security=tls&type=ws#$username\n";
            break;
        case 'trojan':
            echo "Link TLS       : trojan://$key@$domain:$tls?path=$path&security=tls&type=ws#$username\n";
            break;
        case 'shadowsocks':
            $encoded = base64_encode("aes-128-gcm:$key");
            echo "Link SS (TLS)  : ss://$encoded@$domain:$tls#$username\n";
            break;
    }

    echo "</div>\n";
}

