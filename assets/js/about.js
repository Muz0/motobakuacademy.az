document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("about-article");
  if (!container) {
    return;
  }

  const article = container.querySelector(".article-full");
  if (!article) {
    return;
  }

  const heroTitle = document.querySelector(".section-hero__title .uk-h1");
  const defaultContent = article.innerHTML;
  const defaultTitle = heroTitle ? heroTitle.textContent : document.title;

  const htmlLang = (document.documentElement.lang || "az").toLowerCase();
  const supportedLangs = ["az", "ru", "en"];
  const lang = supportedLangs.includes(htmlLang) ? htmlLang : "az";

  const envConfig = window.APP_CONFIG || {};
  const origin =
    (window.location && window.location.origin) || "https://motobakuacademy.az";
  const defaultApiBase = `${origin.replace(/\/$/, "")}/api`;
  const apiBase = normalizeApiBase(envConfig.apiBase || defaultApiBase);

  fetch(`${apiBase}/team.php`)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Failed to load about content");
      }
      return response.json();
    })
    .then((payload) => {
      const about = payload?.about || {};

      const title =
        about[`title_${lang}`] ||
        about.title_az ||
        about.title_en ||
        defaultTitle;
      const contentHtml =
        about[`content_${lang}`] ||
        about.content_az ||
        about.content_en ||
        defaultContent;

      if (contentHtml) {
        article.innerHTML = contentHtml;
      }

      if (title) {
        if (heroTitle) {
          heroTitle.textContent = title;
        }
        document.title = `Moto Baku Academy – ${title}`;
      }
    })
    .catch((error) => {
      console.error("Unable to load about content:", error);
    });
});

function normalizeApiBase(raw) {
  const trimmed = String(raw || "").trim();
  if (!trimmed) {
    return "";
  }
  return trimmed.endsWith("/") ? trimmed.slice(0, -1) : trimmed;
}
