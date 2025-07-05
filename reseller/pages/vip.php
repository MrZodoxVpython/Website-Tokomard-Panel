<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reseller') {
    header("Location: ../index.php");
    exit;
}

$reseller = $_SESSION['username'];
$trimmedReseller = strtolower(trim($reseller));

// Path absolut ke file JSON
$userFile = __DIR__ . '/../data/reseller_users.json';

// Ambil data user
$users = [];
if (file_exists($userFile)) {
    $jsonRaw = file_get_contents($userFile);
    echo "<pre>=== DEBUG JSON ===\n$jsonRaw\n</pre>";
    $users = json_decode($jsonRaw, true);
    if ($users === null) {
        echo "<pre>⚠️ JSON error: " . json_last_error_msg() . "</pre>";
    }
} else {
    echo "<pre>⚠️ File tidak ditemukan: $userFile</pre>";
}

// Cari user yang cocok
$current = array_filter($users, function ($u) use ($trimmedReseller) {
    return strtolower(trim($u['username'])) === $trimmedReseller;
});
$current = reset($current);
$approved = $current && strtolower($current['status']) === 'approved';

// Debug output
//echo "<pre>
//Session Username: {$_SESSION['username']}
//Session Role: {$_SESSION['role']}
//Reseller: $reseller
//Trimmed Session Username: [$trimmedReseller]
//Current user (dump): "; print_r($current);
//echo "Approved? "; var_dump($approved);
//echo "</pre>";

// Jika belum disetujui
if (!$approved) {
    echo "<p style='color:red;'>Akun Anda belum disetujui oleh admin.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Group VIP</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      background-color: #0a0a0a;
      color: #00ff00;
      font-family: monospace;
    }

    .wrapper {
      display: flex;
      flex-direction: column;
      height: calc(100vh - 95px);
      margin: 10px;
      background-color: #000;
      border: 1px solid #00ff00;
      border-radius: 8px;
      overflow: hidden;
      box-sizing: border-box;
    }

    .chat-box {
      flex: 1;
      overflow-y: auto;
      padding: 1rem;
      border-bottom: 1px solid #00ff00;
      box-sizing: border-box;
    }

    .input-bar {
      padding: 0.75rem 1rem;
      border-top: 1px solid #00ff00;
      background-color: #000;
    }

    #input {
      width: 100%;
      padding: 0.75rem 1rem;
      background-color: #000;
      border: 1px solid #00ff00;
      color: #00ff00;
      font-family: monospace;
      font-size: 1rem;
      outline: none;
      border-radius: 4px;
      box-sizing: border-box;
    }

    #input::placeholder {
      color: #00ff00;
    }

    .footer {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.5rem;
      border-top: 1px solid #00ff00;
      padding: 0.5rem;
      font-size: 0.75rem;
      background-color: #000;
      color: #00ff00;
      overflow-x: auto;
    }

    .footer a {
      flex-shrink: 0;
      color: #00ff00;
      text-decoration: none;
      white-space: nowrap;
    }

    .footer a:hover {
      text-decoration: underline;
    }

    button {
      background-color: #000;
      border: 1px solid #00ff00;
      color: #00ff00;
      font-family: monospace;
      padding: 0.5rem 1rem;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
    }

    button:hover {
      background-color: #00ff00;
      color: #000;
    }

    @media screen and (max-width: 768px) {
      .wrapper {
        margin: 8px;
        height: calc(100vh - 90px);
      }

      .chat-box {
        padding: 0.5rem;
      }

      .input-bar {
        padding: 0.5rem 0.75rem;
      }

      #input {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
      }

      .footer {
        font-size: 0.65rem;
        gap: 0.25rem;
        padding: 0.25rem;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper mt-6">
    <div class="chat-box" id="terminal">
      <?php if (!$approved): ?>
        <p>Anda belum disetujui untuk masuk Group Chat.</p>
        <form method="post" action="/reseller/pages/request_group.php">
          <input type="hidden" name="username" value="<?= htmlspecialchars($reseller) ?>">
          <button type="submit">Daftar ke Group Chat</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($approved): ?>
    <div class="input-bar">
      <input type="text" id="input" placeholder="Type your message..." autocomplete="off">
    </div>
    <?php endif; ?>

    <div class="footer">
      <a href="#">Settings</a>
      <a href="#">Help</a>
      <a href="#">About</a>
      <a href="#">Blog</a>
      <a href="#">Jobs</a>
      <a href="#">Learn Cybersecurity [Ad]</a>
      <a href="#">Get 70% Off NordVPN [Ad]</a>
      <a href="#">Become a real Hacker</a>
    </div>
  </div>

<?php if ($approved): ?>
<script>
  const terminal = document.getElementById('terminal');
  const input = document.getElementById('input');
  const username = "<?= htmlspecialchars($reseller) ?>";

  function appendLine(text, timestamp) {
    const div = document.createElement('div');
    const date = new Date(timestamp * 1000);
    const formattedDate = `${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`;
    div.textContent = `${formattedDate} ${text}`;
    terminal.appendChild(div);
    terminal.scrollTop = terminal.scrollHeight;
  }

  function sendMessage(message) {
    appendLine(username + ': ' + message);
    fetch('pages/group/send_message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'username=' + encodeURIComponent(username) + '&message=' + encodeURIComponent(message)
    });
  }

  function fetchMessages() {
    fetch('pages/group/get_messages.php')
      .then(res => res.json())
      .then(data => {
        terminal.innerHTML = '';
        data.forEach(msg => appendLine(msg.username + ': ' + msg.message, msg.time));
      });
  }

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && input.value.trim()) {
      sendMessage(input.value.trim());
      input.value = '';
    }
  });

  fetchMessages();
  setInterval(fetchMessages, 5000);
</script>
<?php endif; ?>
</body>
</html>

