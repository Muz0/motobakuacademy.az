document.addEventListener("DOMContentLoaded", function () {
  const grid = document.getElementById("blogs-grid");
  if (!grid) return;

  // Detect language from <html lang="...">
  const htmlLang = (document.documentElement.lang || "az").toLowerCase();
  const supportedLangs = ["az", "ru", "en"];
  const lang = supportedLangs.includes(htmlLang) ? htmlLang : "az";

  // Simple i18n map
  const i18n = {
    az: {
      readMore: "Davamını oxu",
      noPosts: "Hazırda mövcud bloq yazısı yoxdur.",
      error: "Bloqları yükləyərkən xəta baş verdi.",
      months: [
        "Yan",
        "Fev",
        "Mar",
        "Apr",
        "May",
        "İyn",
        "İyl",
        "Avq",
        "Sen",
        "Okt",
        "Noy",
        "Dek",
      ],
    },
    ru: {
      readMore: "Читать далее",
      noPosts: "Сейчас нет доступных записей блога.",
      error: "Произошла ошибка при загрузке блога.",
      months: [
        "Янв",
        "Фев",
        "Мар",
        "Апр",
        "Май",
        "Июн",
        "Июл",
        "Авг",
        "Сен",
        "Окт",
        "Ноя",
        "Дек",
      ],
    },
    en: {
      readMore: "Read more",
      noPosts: "No blog posts available yet.",
      error: "An error occurred while loading blogs.",
      months: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
    },
  };

  const t = i18n[lang];

  const origin =
    (window.location && window.location.origin) || "https://motobakuacademy.az";
  const defaultApiBase = `${origin.replace(/\/$/, "")}/api`;
  const envConfig = window.APP_CONFIG || {};
  const rawApiBase = envConfig.apiBase || window.blogApiBase || defaultApiBase;
  const apiBase = normalizeApiBase(rawApiBase);
  const API_URL = `${apiBase}/blogs.php`;

  fetch(API_URL)
    .then((response) => {
      if (!response.ok) {
        throw new Error("API response was not ok");
      }
      return response.json();
    })
    .then((json) => {
      const posts = json && json.data ? json.data : [];

      if (!posts.length) {
        grid.innerHTML = `<p>${t.noPosts}</p>`;
        return;
      }

      // sort by date desc
      posts.sort((a, b) => new Date(b.published_at) - new Date(a.published_at));

      posts.forEach((post) => {
        const slug = post.slug;

        // Per-language keys
        const titleKey = "title_" + lang;
        const summaryKey = "summary_" + lang;
        const coverKey = "cover_image_" + lang;

        // Fallback chain: requested lang → az → en → ""
        const title = post[titleKey] || post.title_az || post.title_en || "";

        const excerpt =
          post[summaryKey] || post.summary_az || post.summary_en || "";

        // Cover: lang-specific → az → en → local fallback
        let cover =
          post[coverKey] && post[coverKey] !== "no"
            ? post[coverKey]
            : post.cover_image_az && post.cover_image_az !== "no"
            ? post.cover_image_az
            : post.cover_image_en && post.cover_image_en !== "no"
            ? post.cover_image_en
            : "assets/images/gallery/gallery1.webp";

        const author = formatAuthor(post.author_name);
        const date = formatDate(post.published_at, t.months);
        const link = "blog.html?slug=" + encodeURIComponent(slug);

        const cardWrapper = document.createElement("div");
        cardWrapper.innerHTML = `
            <div class="news-card">
              <div class="news-card__media">
                <a href="${link}">
                  <img
                    class="news-card__img"
                    src="${cover}"
                    alt="${escapeHtml(title)}"
                  />
                </a>
                <img
                  class="news-card__avatar"
                  src="assets/images/team/mirfariz-blog.png"
                  alt="avatar"
                />
              </div>
              <div class="news-card__intro">
                <div class="news-card__info">
                  <div class="news-card__author">${escapeHtml(author)}</div>
                  <div class="news-card__date">${date}</div>
                </div>
                <div class="news-card__title">
                  <a href="${link}">${escapeHtml(title)}</a>
                </div>
                <div class="news-card__desc">
                  ${escapeHtml(excerpt)}
                </div>
                <div class="news-card__more">
                  <a href="${link}">${t.readMore}</a>
                </div>
              </div>
            </div>
          `;

        grid.appendChild(cardWrapper);
      });
    })
    .catch((err) => {
      console.error("Blogları yükləmək mümkün olmadı:", err);
      grid.innerHTML = `<p>${t.error}</p>`;
    });

  function formatDate(isoString, months) {
    if (!isoString) return "";
    const date = new Date(isoString);
    if (isNaN(date)) return "";
    const day = String(date.getDate()).padStart(2, "0");
    const month = months[date.getMonth()] || "";
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
  }

  function formatAuthor(value) {
    const raw = (value || "").trim();
    if (!raw || raw.toLowerCase() === "admin") {
      return "Qara Geyimli Adam";
    }
    return raw;
  }

  function normalizeApiBase(value) {
    return String(value || "").replace(/\/+$/, "");
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
});
