<?php
// Bisa tambahkan validasi akses reseller jika diperlukan
echo "<h2 class='text-lg font-semibold mb-4'>General Settings</h2>";
?>

<form method="POST" class="space-y-4">
  <div>
    <label class="block text-sm font-medium">Ubah Email</label>
    <input type="email" name="email" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white" placeholder="user@example.com">
  </div>

  <div>
    <label class="block text-sm font-medium">Ganti Password</label>
    <input type="password" name="password" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white" placeholder="••••••••">
  </div>

  <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
</form>

