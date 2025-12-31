(function(){
  const pairs = Array.from(document.querySelectorAll('.file-ui'));
  if (!pairs.length) return;

  pairs.forEach(ui=>{
    const input = ui.querySelector('input[type="file"]');
    const nameEl = ui.querySelector('.file-name');
    if (!input || !nameEl) return;

    const def = nameEl.getAttribute('data-default') || 'لم يتم اختيار ملف';

    const setName = () => {
      const file = input.files && input.files[0];
      if (file){
        nameEl.textContent = file.name;
        ui.classList.add('has-file');
      } else {
        nameEl.textContent = def;
        ui.classList.remove('has-file');
      }
    };

    input.addEventListener('change', setName);
    setName();
  });
})();
