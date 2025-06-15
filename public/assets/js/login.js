/* public/assets/js/login.js */
document.addEventListener("DOMContentLoaded", () => {
  const root = document.documentElement;
  const btn  = document.getElementById("themeToggle");
  const icon = document.querySelector("#themeToggle i");

  const safeGet = k => { try { return localStorage.getItem(k); } catch { return null; } };
  const safeSet = (k,v) => { try { localStorage.setItem(k,v); } catch {} };

  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    safeSet("theme", theme);
    if (icon) {
     icon.setAttribute("data-feather", theme === "dark" ? "sun" : "moon");
     feather.replace();
   } else {
     console.warn("⚠️  Couldn’t find #themeToggle i — skipping icon swap");
   }
  }

  const stored    = safeGet("theme");
  const initial   = stored || root.getAttribute("data-theme") ||
                    (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
  applyTheme(initial);

  btn.addEventListener("click", () => {
    const next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
    applyTheme(next);
  });
});
