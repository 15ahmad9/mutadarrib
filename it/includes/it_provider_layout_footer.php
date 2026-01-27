<?php
// closes tags opened in it_provider_layout.php
?>
    </main>
  </div>
</div>

<script>
  // Sidebar toggle
  (function(){
    const shell = document.getElementById('itShell');
    const btn = document.getElementById('itToggleSidebar');

    // حفظ الحالة
    const saved = localStorage.getItem('it_sidebar_collapsed');
    if(saved === '1') shell.classList.add('sidebar-collapsed');

    btn?.addEventListener('click', function(){
      shell.classList.toggle('sidebar-collapsed');
      localStorage.setItem('it_sidebar_collapsed', shell.classList.contains('sidebar-collapsed') ? '1' : '0');
    });
  })();

  // User dropdown
  (function(){
    const btn = document.getElementById('itUserBtn');
    const menu = document.getElementById('itUserMenu');
    if(!btn || !menu) return;

    btn.addEventListener('click', function(e){
      e.stopPropagation();
      menu.classList.toggle('show');
    });

    document.addEventListener('click', function(e){
      if(!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove('show');
    });
  })();
</script>
