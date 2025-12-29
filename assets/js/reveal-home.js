/*
  Home page scroll animations (Front-end only)
  - Adds "in-view" to elements when they enter the viewport
  - Supports staggered children inside containers marked with [data-stagger]
*/
document.addEventListener("DOMContentLoaded", function () {
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const targets = Array.from(document.querySelectorAll(".reveal, .reveal-item"));

  // If user prefers reduced motion, just show everything
  if (prefersReducedMotion) {
    targets.forEach((el) => el.classList.add("in-view"));
    return;
  }

  // Stagger support (optional)
  document.querySelectorAll("[data-stagger]").forEach((wrap) => {
    const items = wrap.querySelectorAll(".reveal-item");
    items.forEach((item, i) => {
      item.style.transitionDelay = `${Math.min(i * 90, 450)}ms`;
    });
  });

  const io = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("in-view");
        obs.unobserve(entry.target);
      });
    },
    {
      threshold: 0.12,
      rootMargin: "0px 0px -10% 0px",
    }
  );

  
  /* Smooth hero-to-next-section transition (scroll-driven) */
  const hero = document.querySelector(".hero--fullscreen");
  if (hero && !prefersReducedMotion) {
    let ticking = false;
    const updateHeroProgress = () => {
      const h = window.innerHeight || 1;
      const y = window.scrollY || window.pageYOffset || 0;

      // progress reaches 1 around 65% of the first viewport scroll
      const p = Math.min(1, Math.max(0, y / (h * 0.65)));
      hero.style.setProperty("--hero-progress", p.toFixed(3));
      ticking = false;
    };

    const onScroll = () => {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(updateHeroProgress);
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    updateHeroProgress();
  }


  targets.forEach((el) => io.observe(el));
});
