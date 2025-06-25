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
    $output = <<<EOL
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
EOL;

    echo <<<HTML
<div class="max-w-full overflow-auto p-2">
    <pre class="bg-gray-900 text-green-400 border-2 border-orange-400 rounded-xl p-4 text-sm leading-relaxed font-mono whitespace-pre-wrap">$output</pre>
</div>
HTML;
}

function tampilkanXRAY($proto, $username, $expired, $key) {
    $domain = trim(@file_get_contents('/etc/xray/domain'));
    $tls = "443";
    $ntls = "80";
    $grpcService = $proto . "-grpc";
    $path = "/$proto-ws";

    $output = <<<EOL
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
            $output .= "Link TLS       : $vmessLink\n";
            break;
        case 'vless':
            $output .= "Link TLS       : vless://$key@$domain:$tls?path=$path&security=tls&type=ws#$username\n";
            break;
        case 'trojan':
            $output .= "Link TLS       : trojan://$key@$domain:$tls?path=$path&security=tls&type=ws#$username\n";
            break;
        case 'shadowsocks':
            $encoded = base64_encode("aes-128-gcm:$key");
            $output .= "Link SS (TLS)  : ss://$encoded@$domain:$tls#$username\n";
            break;
    }

    echo <<<HTML
<div class="max-w-full overflow-auto p-2">
    <pre class="bg-gray-900 text-green-400 border-2 border-orange-400 rounded-xl p-4 text-sm leading-relaxed font-mono whitespace-pre-wrap">$output</pre>
</div>
HTML;
}

