<?php
echo "<h2 class='text-lg font-semibold mb-4'>Advanced Settings</h2>";
?>

<div class="space-y-4">
  <div class="bg-gray-700 p-4 rounded">
    <p class="text-sm text-gray-300">🔒 Hapus Akun Permanen</p>
    <button onclick="confirm('Yakin ingin menghapus akun?')" class="mt-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Hapus Akun</button>
  </div>

  <div class="bg-gray-700 p-4 rounded">
    <p class="text-sm text-gray-300">📦 Export Data</p>
    <button class="mt-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Export ke CSV</button>
  </div>
</div>

