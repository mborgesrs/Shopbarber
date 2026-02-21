      </main>
      <footer class="px-4 md:px-8 py-4 text-[11px] font-medium text-gray-400 border-t border-gray-50 bg-white/50 backdrop-blur-sm flex-shrink-0">
        <div class="flex items-center justify-between">
          <div>
            <?php
            $companyName = $app_name; // Default fallbak
            if(isset($_SESSION['company_id'])){
                $stmt = $pdo->prepare("SELECT fantasy_name FROM companies WHERE id = ?");
                $stmt->execute([$_SESSION['company_id']]);
                $comp = $stmt->fetch();
                if($comp && !empty($comp['fantasy_name'])) {
                    $companyName = $comp['fantasy_name'];
                }
            }
            ?>
            &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?> ShopBarber — Sistema de Gestão Salões e Barbearias.
          </div>
          <div class="flex items-center gap-1 opacity-75">
            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
            Sistema Online
          </div>
        </div>
      </footer>
    </div>
  </div>
  <script src="/assets/js/app.js"></script>
</body>
</html>
