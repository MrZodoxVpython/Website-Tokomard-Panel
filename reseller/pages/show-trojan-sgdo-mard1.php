<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$reseller = $_SESSION['reseller'] ?? $_SESSION['username'] ?? 'unknown';

$remoteIP = '152.42.182.187'; // IP VPS remote
$sshUser = 'root';
$remotePath = "/etc/xray/data-panel/reseller";
$sshPrefix = "ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $sshUser@$remoteIP";

$cmdListFiles = "$sshPrefix 'ls $remotePath/akun-$reseller-*.txt 2>/dev/null'";
$fileListRaw = shell_exec($cmdListFiles);
$fileList = array_filter(explode("\n", trim($fileListRaw)));
$fileList = array_unique($fileList); // Hapus duplikat

$configPath = '/etc/xray/config.json';
$logDir = "/etc/xray/data-panel/reseller";
$akunFiles = glob("$logDir/akun-$reseller-*.txt");

// Handle DELETE
if (isset($_GET['hapus'])) {
    $userToDelete = $_GET['hapus'];
    $lines = file($configPath);
    $newLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($userToDelete) . '\s+\d{4}-\d{2}-\d{2}/', $lines[$i])) {
            $i++; // skip JSON line
            continue;
        }
        if (preg_match('/^##LOCK##/', trim($lines[$i]))) {
            continue; // skip LOCK lines if any
        }
        $newLines[] = $lines[$i];
    }

    file_put_contents($configPath, implode('', $newLines));
    shell_exec('sudo /usr/local/bin/restart-xray.sh');

    foreach (glob("$logDir/akun-$reseller-$userToDelete.txt") as $file) {
        unlink($file);
    }

    header("Location: show-trojan-sgdo-2dev.php");
    exit;
}

// Handle POST (Edit & Start/Stop)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Edit Expired
    if (isset($_POST['edit_user'])) {
        $userEdit = $_POST['edit_user'];
        $expiredInput = trim($_POST['expired']);
        $expiredBaru = null;

        if (preg_match('/^\d+$/', $expiredInput)) {
            $expiredBaru = date('Y-m-d', strtotime("+$expiredInput days"));
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiredInput)) {
            $expiredBaru = $expiredInput;
        }

        if ($expiredBaru) {
            $lines = file($configPath);
            $currentTag = '';

            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*#(trojan)(grpc|ws)?$/i', trim($line), $m)) {
                    $currentTag = '#' . strtolower($m[1] . ($m[2] ?? ''));
                }

                if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                    if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($userEdit, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
                        $prefix = $matches[1];
                        $lines[$i] = "$prefix $userEdit $expiredBaru\n";
                    }
                }
            }

            file_put_contents($configPath, implode('', $lines));

            foreach (glob("$logDir/akun-$reseller-$userEdit.txt") as $file) {
                $content = file_get_contents($file);
                $content = preg_replace('/(Expired On\s*:\s*)(\d{4}-\d{2}-\d{2})/', '${1}' . $expiredBaru, $content);
                file_put_contents($file, $content);
            }

            shell_exec('sudo /usr/local/bin/restart-xray.sh');
        }

        header("Location: show-trojan-sgdo-2dev.php");
        exit;
    }

    // START / STOP
    if (isset($_POST['toggle_user']) && isset($_POST['action'])) {
        $user = $_POST['toggle_user'];
        $action = $_POST['action']; // start or stop
        $lines = file($configPath);
        $currentTag = '';
        $updated = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Deteksi blok protokol
            if (preg_match('/^#trojan(ws|grpc)?$/i', $line)) {
                $currentTag = strtolower($line);
            }

            // Proses hanya jika sedang dalam blok trojan
            if (in_array($currentTag, ['#trojanws', '#trojangrpc'])) {
                if (preg_match('/^\s*(###|#!|#&|#\$)\s+' . preg_quote($user, '/') . '\s+\d{4}-\d{2}-\d{2}/', $line)) {
                    $jsonIndex = $i + 1;
                    if (!isset($lines[$jsonIndex])) continue;

                    $jsonLine = trim($lines[$jsonIndex]);

                    // STOP: lock akun
                    if ($action === 'stop') {
                        if (preg_match('/"password"\s*:\s*"([^"]+)"/', $jsonLine, $m)) {
                            $originalPassword = $m[1];
                            if ($originalPassword !== 'locked') {
                                $lines[$jsonIndex] = preg_replace('/"password"\s*:\s*"[^"]+"/', '"password": "locked"', $jsonLine) . "\n";
                                array_splice($lines, $jsonIndex, 0, ["##LOCK##$originalPassword\n"]);
                                $updated = true;
                            }
                        }
                    }

                    // START: unlock akun
                    if ($action === 'start') {
                        if (preg_match('/"password"\s*:\s*"locked"/', $jsonLine)) {
                            // Cari baris LOCK di atasnya, max 10 baris ke atas
                            for ($k = $jsonIndex - 1; $k >= max(0, $jsonIndex - 10); $k--) {
                                if (preg_match('/^##LOCK##(.+)/', trim($lines[$k]), $match)) {
                                    $realPassword = trim($match[1]);

                                    // Kembalikan password asli
                                    $lines[$jsonIndex] = preg_replace('/"password"\s*:\s*"locked"/', '"password": "' . $realPassword . '"', $lines[$jsonIndex]);

                                    // Hapus baris ##LOCK##
                                    array_splice($lines, $k, 1);
                                    $updated = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($updated) {
            file_put_contents($configPath, implode('', $lines));
            shell_exec('sudo /usr/local/bin/restart-xray.sh');
        }

        header("Location: show-trojan-sgdo-2dev.php");
        exit;
    }
}
?>

