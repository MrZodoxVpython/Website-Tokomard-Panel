<!-- Header: Profil dan Avatar -->
<div class="flex flex-col md:flex-row items-center md:items-start gap-4 mt-20 mb-10">
  <!-- Box Profil -->
  <div class="bg-gray-800 rounded-lg p-2.5 w-full md:w-1/3 text-center shadow border border-gray-700 overflow-hidden">
    <img src="<?= $avatar ?>?v=<?= time() ?>" alt="Avatar" class="w-24 h-24 mx-auto rounded-full" />
    <h2 class="text-xl font-semibold mt-2"><?= $reseller ?></h2>
    <p class="text-gray-400 text-sm"><?= $email ?></p>
    <div class="text-center mt-4 text-left text-sm">
      <p><strong>Account ID:</strong> <?= $account_id ?></p>
      <p><strong>Email:</strong> <?= $email ?></p>
    </div>
  </div>

  <!-- Konten Kanan -->
  <div class="flex-1 w-full space-y-6">
    <!-- Balance & Reseller Box -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
      <div class="flex flex-col justify-center items-center text-center bg-green-500/10 text-green-300 p-10 rounded-lg shadow border border-green-400/30">
        <h3 class="text-sm font-semibold mb-1">Balance</h3>
        <p class="text-2xl font-bold">Rp. <?= number_format($balance, 0, ',', '.') ?></p>
        <p class="text-xs text-gray-400">Earn reward points with every purchase.</p>
      </div>

      <div class="flex flex-col justify-center items-center text-center bg-green-500/10 text-green-300 p-10 rounded-lg shadow border border-green-400/30">
        <h1 class="text-7xl py-4">🏆</h1>
        <h3 class="text-sm font-semibold mb-1">Reseller</h3>
        <p class="text-sm">Keep up to date with your account.</p>
      </div>
    </div>
  </div>
</div>

<!-- Riwayat Transaksi -->
<div class="bg-gray-800 p-4 -mt-6 rounded-lg shadow border max-w-full border-gray-700">
  <div class="flex justify-between items-center mb-3">
    <h3 class="text-lg font-semibold">Transaction History</h3>
    <input type="text" placeholder="Search Transaction" class="bg-gray-900 text-sm rounded px-3 py-1 border border-gray-600 focus:outline-none" />
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-[11px] sm:text-sm border-collapse table-fixed">
      <thead class="bg-gray-100 dark:bg-gray-700">
        <tr>
          <th class="px-2 md:px-4 py-2 w-[16%] text-left">TYPE</th>
          <th class="px-2 md:px-4 py-2 w-[25%] text-left">STATUS</th>
          <th class="px-2 md:px-4 py-2 w-[25%] text-left">AMOUNT</th>
          <th class="px-2 md:px-4 py-2 w-[26%] text-left">DETAIL</th>
          <th class="px-2 md:px-4 py-2 w-[25%] text-left">DATE</th>
        </tr>
      </thead>
      <tbody class="text-gray-300">
        <?php foreach ($transactions as $trx): ?>
        <tr class="border-t border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
          <td class="px-2 md:px-4 py-2 w-[16%]"><?= $trx['type'] ?></td>
          <td class="px-2 md:px-4 py-2 w-[25%] text-green-400 font-semibold"><?= $trx['status'] ?></td>
          <td class="px-2 md:px-4 py-2 w-[25%]">Rp. <?= number_format($trx['amount'], 0, ',', '.') ?></td>
          <td class="px-2 md:px-4 py-2 w-[26%]"><?= $trx['detail'] ?></td>
          <td class="px-2 md:px-4 py-2 w-[25%]"><?= $trx['date'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

