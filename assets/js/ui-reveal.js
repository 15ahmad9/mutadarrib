(function(){
  const els = Array.from(document.querySelectorAll('.reveal, .reveal-item'));
  if (!els.length) return;

  // Fallback for older browsers
  if (!('IntersectionObserver' in window)) {
    els.forEach(el => el.classList.add('in-view'));
    return;
  }

  const obs = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if (e.isIntersecting){
        e.target.classList.add('in-view');
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

  els.forEach(el=>obs.observe(el));
})();
