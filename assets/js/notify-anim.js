/* Notifications Center Animations (Front-end only) */
(function () {
  function staggerRows(table) {
    const rows = table.querySelectorAll("tbody tr");
    rows.forEach((tr, idx) => {
      tr.classList.add("notify-row-anim");
      tr.style.animationDelay = (idx * 70) + "ms";
    });

    // Add mobile labels for cells (already set via data-label, keep safe)
    rows.forEach((tr) => {
      tr.querySelectorAll("td").forEach((td) => {
        if (!td.getAttribute("data-label")) return;
      });
    });
  }

  function enhanceCards() {
    const cards = document.querySelectorAll(".notify-section-card");
    cards.forEach((card, i) => {
      // Small cascade entrance
      card.style.animation = "notifyEnter .55s ease both";
      card.style.animationDelay = (i * 90) + "ms";

      if (card.querySelector("[data-empty]")) {
        card.classList.add("is-empty");
        // little pop for empty message
        const empty = card.querySelector("[data-empty]");
        empty.animate(
          [{ opacity: 0, transform: "translateY(8px)" }, { opacity: 1, transform: "translateY(0)" }],
          { duration: 420, easing: "cubic-bezier(.2,.8,.2,1)", fill: "both" }
        );
      }

      const tables = card.querySelectorAll("table[data-rows]");
      if (tables.length) {
        card.classList.add("has-rows");
        tables.forEach(staggerRows);
      }
    });
  }

  // Gentle pulse on the navbar badge when present
  function pulseBadge() {
    const badge = document.querySelector(".notif-badge");
    if (!badge) return;
    badge.animate(
      [{ transform: "scale(1)" }, { transform: "scale(1.15)" }, { transform: "scale(1)" }],
      { duration: 900, iterations: 2, easing: "ease-in-out" }
    );
  }

  document.addEventListener("DOMContentLoaded", function () {
    enhanceCards();
    pulseBadge();
  });
})();
