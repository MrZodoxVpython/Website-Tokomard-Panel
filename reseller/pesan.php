<form method="POST" action="kirim-notifikasi.php" class="space-y-3">
  <textarea name="pesan" placeholder="Tulis pesan notifikasi..." required class="w-full p-2 border rounded"></textarea>
  <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Kirim</button>
</form>

<?php if (isset($_GET['sukses'])): ?>
  <p class="text-green-600 mt-3">✅ Notifikasi berhasil dikirim!</p>
<?php endif; ?>

