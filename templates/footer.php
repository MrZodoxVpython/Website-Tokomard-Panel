</main>

<!-- Footer -->
<footer class="bg-gray-800 text-center text-sm text-gray-400 py-4 mt-10">
  © <?= date("Y") ?> Tokomard. All rights reserved.
</footer>
<script>
  const toggle = document.getElementById('menu-toggle');
  const menu = document.getElementById('mobile-menu');

  toggle.addEventListener('click', () => {
    menu.classList.toggle('hidden');
  });
</script>
</body>
</html>

