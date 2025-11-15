document.addEventListener("DOMContentLoaded", () => {
  const htmlLang = (document.documentElement.lang || "az").toLowerCase();
  const supportedLangs = ["az", "ru", "en"];
  const lang = supportedLangs.includes(htmlLang) ? htmlLang : "az";

  // TODO: move localhost base into environment config once deployment path is finalized.
  const API_BASE = normalizeApiBase(
    window.blogApiBase || "http://localhost/motobakuacademy.az/api"
  );
  const state = {
    slug: new URLSearchParams(window.location.search).get("slug") || "",
    lang,
    acceptsComments: true,
    comments: [],
    commentIds: new Set(),
    page: 1,
    lastPage: 1,
    isLoadingComments: false,
    replyingTo: null,
  };

  const elements = collectElements();
  const translations = buildTranslations(lang);

  applyStaticTranslations(elements, translations);

  if (!state.slug) {
    handleMissingSlug(elements, translations);
    loadLatestPosts(state, elements, translations, API_BASE);
    return;
  }

  if (elements.commentSlugInput) {
    elements.commentSlugInput.value = state.slug;
  }

  fetchPostDetails(state, elements, translations, API_BASE)
    .catch(() => {
      // Post fetch already handled its own error feedback.
    })
    .finally(() => {
      initComments(state, elements, translations, API_BASE);
      loadLatestPosts(state, elements, translations, API_BASE);
    });
});

function normalizeApiBase(raw) {
  const trimmed = String(raw || "").trim();
  if (!trimmed) {
    return "";
  }
  if (trimmed.endsWith("/")) {
    return trimmed.slice(0, -1);
  }
  return trimmed;
}

function buildTranslations(lang) {
  const map = {
    az: {
      commentsTitle: "Şərhlər",
      noComments: "Hazırda şərh yoxdur. İlk siz yazın!",
      loadMore: "Daha çox yüklə",
      formTitle: "Şərhinizi yazın",
      commentsClosed: "Bu məqalə üçün şərhlər müvəqqəti olaraq deaktiv edilib.",
      authorLabel: "Adınız",
      authorPlaceholder: "Adınızı daxil edin",
      messageLabel: "Mesaj",
      messagePlaceholder: "Fikrinizi paylaşın",
      submit: "Şərh göndər",
      posting: "Göndərilir...",
      reply: "Cavab yaz",
      cancelReply: "Cavabı ləğv et",
      success: "Şərhiniz göndərildi!",
      fetchError: "Məlumatı yükləmək mümkün olmadı.",
      commentsFetchError: "Şərhləri yükləmək mümkün olmadı.",
      submitError: "Şərhinizi göndərərkən xəta baş verdi.",
      commentsClosedShort: "Şərhlər deaktiv edilib.",
      missingSlugTitle: "Məqalə tapılmadı",
      missingSlugBody:
        "Məqalə üçün tələb olunan parametr tapılmadı. Zəhmət olmasa bloq siyahısına qayıdın.",
      latestEmpty: "Hazırda xəbər siyahısı mövcud deyil.",
    },
    ru: {
      commentsTitle: "Комментарии",
      noComments: "Пока нет комментариев. Будьте первым!",
      loadMore: "Загрузить ещё",
      formTitle: "Оставьте комментарий",
      commentsClosed: "Комментарии к этой записи отключены.",
      authorLabel: "Имя",
      authorPlaceholder: "Введите ваше имя",
      messageLabel: "Сообщение",
      messagePlaceholder: "Поделитесь своим мнением",
      submit: "Отправить",
      posting: "Отправляется...",
      reply: "Ответить",
      cancelReply: "Отменить",
      success: "Комментарий отправлен!",
      fetchError: "Не удалось загрузить данные статьи.",
      commentsFetchError: "Не удалось загрузить комментарии.",
      submitError: "Не удалось отправить комментарий.",
      commentsClosedShort: "Комментарии отключены.",
      missingSlugTitle: "Запись не найдена",
      missingSlugBody:
        "Параметр записи отсутствует. Пожалуйста, вернитесь к списку блогов.",
      latestEmpty: "Последние новости временно недоступны.",
    },
    en: {
      commentsTitle: "Comments",
      noComments: "No comments yet. Be the first to share your thoughts!",
      loadMore: "Load more",
      formTitle: "Leave a comment",
      commentsClosed: "Comments are disabled for this article.",
      authorLabel: "Name",
      authorPlaceholder: "Enter your name",
      messageLabel: "Message",
      messagePlaceholder: "Share your thoughts",
      submit: "Post comment",
      posting: "Posting...",
      reply: "Reply",
      cancelReply: "Cancel reply",
      success: "Your comment has been published!",
      fetchError: "Unable to load this article.",
      commentsFetchError: "Unable to load comments.",
      submitError: "We couldn't submit your comment. Please try again.",
      commentsClosedShort: "Comments are closed.",
      missingSlugTitle: "Article not found",
      missingSlugBody:
        "The required slug parameter is missing. Please return to the blog listing.",
      latestEmpty: "Latest news are unavailable right now.",
    },
  };

  return map[lang] || map.az;
}

function collectElements() {
  return {
    articleImg: document.querySelector(".article__img"),
    articleTitle: document.querySelector(".article__title"),
    articleContent: document.querySelector(".article__content"),
    articleDate: document.querySelector(".article__date span"),
    articleAuthor: document.querySelector(".article__author span"),
    heroTitle: document.querySelector(".section-hero__title .uk-h1"),
    canonicalLink: document.querySelector('link[rel="canonical"]'),
    commentsTitle: document.querySelector("#comments-section .uk-h2 span"),
    commentsCount: document.getElementById("comments-count"),
    commentsList: document.getElementById("comments-list"),
    commentsEmpty: document.getElementById("comments-empty"),
    commentsStatus: document.getElementById("comments-status"),
    loadMoreWrapper: document.getElementById("comments-load-more-wrapper"),
    loadMoreBtn: document.getElementById("comments-load-more"),
    latestPostsList: document.getElementById("latest-posts-list"),
    latestPostsEmpty: document.getElementById("latest-posts-empty"),
    commentFormBlock: document.getElementById("comment-form-block"),
    commentsClosed: document.getElementById("comments-closed"),
    commentForm: document.getElementById("comment-form"),
    commentAuthorInput: document.getElementById("comment-author-input"),
    commentMessageInput: document.getElementById("comment-message-input"),
    commentParentInput: document.getElementById("comment-parent-input"),
    commentSlugInput: document.getElementById("comment-slug-input"),
    commentSubmitBtn: document.getElementById("comment-submit-btn"),
    commentReplyIndicator: document.getElementById("comment-reply-indicator"),
    commentReplyText: document.getElementById("comment-reply-text"),
    commentReplyCancel: document.getElementById("comment-reply-cancel"),
    commentFormFeedback: document.getElementById("comment-form-feedback"),
  };
}

function applyStaticTranslations(elements, t) {
  if (elements.commentsTitle) {
    elements.commentsTitle.textContent = t.commentsTitle;
  }
  if (elements.commentsEmpty) {
    elements.commentsEmpty.textContent = t.noComments;
  }
  if (elements.latestPostsEmpty) {
    elements.latestPostsEmpty.textContent = t.latestEmpty;
  }
  if (elements.loadMoreBtn) {
    elements.loadMoreBtn.textContent = t.loadMore;
  }
  if (elements.commentFormBlock) {
    const heading = elements.commentFormBlock.querySelector(".uk-h2");
    if (heading) {
      heading.textContent = t.formTitle;
    }
  }
  if (elements.commentsClosed) {
    elements.commentsClosed.textContent = t.commentsClosed;
  }
  if (elements.commentReplyCancel) {
    elements.commentReplyCancel.textContent = t.cancelReply;
  }
  if (elements.commentAuthorInput) {
    elements.commentAuthorInput.placeholder = t.authorPlaceholder;
    const label = elements.commentFormBlock?.querySelector(
      'label[for="comment-author-input"]'
    );
    if (label) {
      label.textContent = t.authorLabel;
    }
  }
  if (elements.commentMessageInput) {
    elements.commentMessageInput.placeholder = t.messagePlaceholder;
    const label = elements.commentFormBlock?.querySelector(
      'label[for="comment-message-input"]'
    );
    if (label) {
      label.textContent = t.messageLabel;
    }
  }
  if (elements.commentSubmitBtn) {
    elements.commentSubmitBtn.textContent = t.submit;
  }
}

function handleMissingSlug(elements, t) {
  if (elements.articleTitle) {
    elements.articleTitle.textContent = t.missingSlugTitle;
  }
  if (elements.articleContent) {
    elements.articleContent.innerHTML = `<p>${escapeHtml(
      t.missingSlugBody
    )}</p>`;
  }
  if (elements.commentsStatus) {
    elements.commentsStatus.style.display = "block";
    elements.commentsStatus.textContent = t.commentsFetchError;
  }
  if (elements.commentFormBlock) {
    elements.commentFormBlock.style.display = "none";
  }
}

function fetchPostDetails(state, elements, t, apiBase) {
  const url = buildApiUrl(
    apiBase,
    `blog.php?slug=${encodeURIComponent(state.slug)}`
  );
  return fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Post request failed");
      }
      return response.json();
    })
    .then((data) => {
      if (!data || !data.slug) {
        throw new Error("Post not found");
      }
      populateArticle(data, state, elements);
      state.acceptsComments = (data.accepts_comments ?? true) === true;
      updateCommentFormState(state, elements, t);
    })
    .catch((error) => {
      console.error("Unable to load blog:", error);
      if (elements.articleTitle) {
        elements.articleTitle.textContent = t.fetchError;
      }
      if (elements.articleContent) {
        elements.articleContent.innerHTML = `<p>${escapeHtml(
          t.fetchError
        )}</p>`;
      }
      throw error;
    });
}

function populateArticle(data, state, elements) {
  const lang = state.lang;
  const title =
    data[`title_${lang}`] || data.title || data.title_az || data.title_en || "";
  const contentHtml =
    data[`content_${lang}`] ||
    data.content ||
    data.content_az ||
    data.content_en ||
    "";
  const cover =
    resolveCoverImage(data[`cover_image_${lang}`]) ||
    resolveCoverImage(data.cover_image) ||
    resolveCoverImage(data.cover_image_az) ||
    resolveCoverImage(data.cover_image_en) ||
    "assets/images/gallery/gallery1.webp";
  const publishedAt = data.published_at || data.created_at || null;
  const authorName = formatAuthorName(data.author_name);

  if (elements.heroTitle && title) {
    elements.heroTitle.textContent = title;
  }
  if (elements.articleTitle && title) {
    elements.articleTitle.textContent = title;
  }
  if (elements.articleImg) {
    elements.articleImg.src = cover;
    elements.articleImg.alt = title || "Blog cover";
  }
  if (elements.articleAuthor) {
    elements.articleAuthor.textContent = authorName;
  }
  if (elements.articleDate && publishedAt) {
    elements.articleDate.textContent = formatDate(publishedAt, state.lang);
  }
  if (elements.articleContent) {
    elements.articleContent.innerHTML = contentHtml;
  }
  document.title = title ? `Moto Baku Academy – ${title}` : document.title;
  if (elements.canonicalLink) {
    try {
      const canonicalUrl = new URL(window.location.href);
      canonicalUrl.search = `?slug=${encodeURIComponent(state.slug)}`;
      elements.canonicalLink.href = canonicalUrl.toString();
    } catch (error) {
      const base = window.location.origin || "https://www.motobakuacademy.az";
      elements.canonicalLink.href =
        base +
        `${window.location.pathname || "/blog.html"}?slug=${encodeURIComponent(
          state.slug
        )}`;
    }
  }
}

function resolveCoverImage(value) {
  if (!value || value === "no") {
    return null;
  }
  return value;
}

function formatDate(isoString, lang) {
  if (!isoString) {
    return "";
  }
  const date = new Date(isoString);
  if (Number.isNaN(date.getTime())) {
    return "";
  }
  const months = {
    az: [
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
    ru: [
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
    en: [
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
  };
  const list = months[lang] || months.az;
  const day = String(date.getDate()).padStart(2, "0");
  const month = list[date.getMonth()] || "";
  const year = date.getFullYear();
  return `${day} ${month} ${year}`;
}

function formatAuthorName(name) {
  if (!name || typeof name !== "string") {
    return "Qara Geyimli Adam";
  }
  const trimmed = name.trim();
  if (!trimmed || trimmed.toLowerCase() === "admin") {
    return "Qara Geyimli Adam";
  }
  return trimmed;
}

function initComments(state, elements, t, apiBase) {
  if (elements.commentForm) {
    elements.commentForm.addEventListener("submit", (event) =>
      handleCommentSubmit(event, state, elements, t, apiBase)
    );
  }

  if (elements.commentReplyCancel) {
    elements.commentReplyCancel.addEventListener("click", () =>
      clearReplyTarget(state, elements)
    );
  }

  if (elements.loadMoreBtn) {
    elements.loadMoreBtn.addEventListener("click", () => {
      if (state.isLoadingComments || state.page >= state.lastPage) {
        return;
      }
      loadComments(state.page + 1, state, elements, t, apiBase, {
        append: true,
      });
    });
  }

  loadComments(1, state, elements, t, apiBase, { append: false });
}

function loadLatestPosts(state, elements, t, apiBase) {
  if (!elements.latestPostsList) {
    return;
  }

  const url = buildApiUrl(apiBase, `blogs.php?page=1&per_page=4`);

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Latest posts request failed");
      }
      return response.json();
    })
    .then((json) => {
      const posts = Array.isArray(json.data) ? json.data : [];
      renderLatestPosts(posts, state, elements, t);
    })
    .catch((error) => {
      console.error("Unable to load latest posts:", error);
      if (elements.latestPostsEmpty) {
        elements.latestPostsEmpty.style.display = "block";
      }
    });
}

function buildApiUrl(base, path) {
  const cleanPath = path.replace(/^\/+/, "");
  if (!base || base === ".") {
    return cleanPath;
  }
  return `${base}/${cleanPath}`;
}

function loadComments(page, state, elements, t, apiBase, options = {}) {
  if (state.isLoadingComments) {
    return;
  }
  state.isLoadingComments = true;
  toggleCommentsStatus(elements, "", false);
  const perPage = 20;
  const url = buildApiUrl(
    apiBase,
    `comments/get.php?slug=${encodeURIComponent(
      state.slug
    )}&page=${page}&per_page=${perPage}`
  );
  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Comments request failed");
      }
      return response.json();
    })
    .then((json) => {
      const items = Array.isArray(json.data) ? json.data : [];
      const accepts = json.post?.accepts_comments;
      if (typeof accepts === "boolean" || accepts === 0 || accepts === 1) {
        state.acceptsComments = Boolean(accepts);
        updateCommentFormState(state, elements, t);
      }
      mergeComments(state, items);
      state.page = json.pagination?.page || page;
      state.lastPage = json.pagination?.last_page || state.page;
      renderComments(state, elements, t);
      updateLoadMore(state, elements);
    })
    .catch((error) => {
      console.error("Unable to load comments:", error);
      toggleCommentsStatus(elements, t.commentsFetchError, true);
    })
    .finally(() => {
      state.isLoadingComments = false;
    });
}

function mergeComments(state, items) {
  let changed = false;
  items.forEach((raw) => {
    const id = Number(raw.id);
    if (!state.commentIds.has(id)) {
      state.commentIds.add(id);
      state.comments.push({
        id,
        author_name: raw.author_name || "Guest",
        message: raw.message || "",
        created_at: raw.created_at || null,
        parent_comment_id:
          raw.parent_comment_id !== null ? Number(raw.parent_comment_id) : null,
      });
      changed = true;
    }
  });
  if (changed) {
    state.comments.sort((a, b) => {
      const dateA = new Date(a.created_at || 0).getTime();
      const dateB = new Date(b.created_at || 0).getTime();
      return dateA - dateB;
    });
  }
}

function renderComments(state, elements, t) {
  if (!elements.commentsList) {
    return;
  }
  const roots = buildCommentTree(state.comments);
  elements.commentsList.innerHTML = "";
  if (!roots.length) {
    if (elements.commentsEmpty) {
      elements.commentsEmpty.style.display = "block";
    }
  } else if (elements.commentsEmpty) {
    elements.commentsEmpty.style.display = "none";
  }
  const fragment = document.createDocumentFragment();
  roots.forEach((comment) => {
    fragment.appendChild(renderCommentNode(comment, state, elements, t, 0));
  });
  elements.commentsList.appendChild(fragment);
  if (elements.commentsCount) {
    elements.commentsCount.textContent = `(${state.comments.length})`;
  }
}

function renderLatestPosts(posts, state, elements, t) {
  const list = elements.latestPostsList;
  if (!list) {
    return;
  }

  list.innerHTML = "";

  if (!posts.length) {
    if (elements.latestPostsEmpty) {
      elements.latestPostsEmpty.style.display = "block";
    }
    return;
  }

  if (elements.latestPostsEmpty) {
    elements.latestPostsEmpty.style.display = "none";
  }

  const fragment = document.createDocumentFragment();
  posts.slice(0, 4).forEach((post) => {
    const lang = state.lang;
    const title =
      post[`title_${lang}`] ||
      post.title ||
      post.title_az ||
      post.title_en ||
      "";
    const slug = post.slug;
    const link = `blog.html?slug=${encodeURIComponent(slug)}`;
    const cover =
      resolveCoverImage(post[`cover_image_${lang}`]) ||
      resolveCoverImage(post.cover_image_az) ||
      resolveCoverImage(post.cover_image_en) ||
      "assets/images/gallery/gallery1.webp";
    const date = formatDate(post.published_at, state.lang);

    const listItem = document.createElement("li");
    const wrapper = document.createElement("div");
    wrapper.className = "latest-news-item";

    const thumb = document.createElement("div");
    thumb.className = "latest-news-item__thumb";
    const img = document.createElement("img");
    img.src = cover;
    img.alt = "latest-post";
    thumb.appendChild(img);

    const info = document.createElement("div");
    info.className = "latest-news-item__info";
    const titleLink = document.createElement("a");
    titleLink.className = "latest-news-item__title";
    titleLink.href = link;
    titleLink.textContent = title;
    const dateWrap = document.createElement("div");
    dateWrap.className = "latest-news-item__date";
    const calendarIcon = document.createElement("img");
    calendarIcon.src = "assets/img/icons/calendar.svg";
    calendarIcon.alt = "icon";
    dateWrap.appendChild(calendarIcon);
    dateWrap.appendChild(document.createTextNode(date));

    info.appendChild(titleLink);
    info.appendChild(dateWrap);

    wrapper.appendChild(thumb);
    wrapper.appendChild(info);
    listItem.appendChild(wrapper);
    fragment.appendChild(listItem);
  });

  list.appendChild(fragment);
}

function buildCommentTree(comments) {
  const map = new Map();
  const roots = [];
  comments.forEach((comment) => {
    map.set(comment.id, { ...comment, children: [] });
  });
  map.forEach((comment) => {
    if (comment.parent_comment_id && map.has(comment.parent_comment_id)) {
      map.get(comment.parent_comment_id).children.push(comment);
    } else {
      roots.push(comment);
    }
  });
  return roots;
}

function renderCommentNode(comment, state, elements, t, depth) {
  const article = document.createElement("article");
  article.className = "uk-comment";
  article.dataset.commentId = String(comment.id);

  const header = document.createElement("header");
  header.className = "uk-comment-header";
  const grid = document.createElement("div");
  grid.className = "uk-grid-medium";
  grid.setAttribute("data-uk-grid", "");

  const avatarWrap = document.createElement("div");
  avatarWrap.className = "uk-width-auto@s";
  const avatar = document.createElement("img");
  avatar.className = "uk-comment-avatar";
  avatar.width = 90;
  avatar.height = 90;
  const normalizedAuthor = (comment.author_name || "").trim().toLowerCase();
  const isAdminAuthor = ["qara geyimli adam", "admin", "mirfariz"].includes(
    normalizedAuthor
  );
  avatar.src = isAdminAuthor
    ? "/assets/images/team/mirfariz-blog.png"
    : "/assets/img/icons/default_user.jpg";
  avatar.alt = "avatar";
  avatar.loading = "lazy";
  avatarWrap.appendChild(avatar);

  const bodyWrap = document.createElement("div");
  bodyWrap.className = "uk-width-expand@s";

  const titleWrap = document.createElement("div");
  const title = document.createElement("h4");
  title.className = "uk-comment-title uk-margin-remove";
  title.textContent = comment.author_name || "Guest";
  titleWrap.appendChild(title);

  const meta = document.createElement("span");
  meta.className = "uk-text-meta";
  meta.textContent = formatDate(comment.created_at, state.lang);
  titleWrap.appendChild(meta);

  const messageWrap = document.createElement("div");
  messageWrap.className = "uk-comment-body";
  const paragraph = document.createElement("p");
  appendMessageWithBreaks(paragraph, comment.message || "");
  messageWrap.appendChild(paragraph);

  const replyWrap = document.createElement("div");
  if (state.acceptsComments) {
    const replyButton = document.createElement("button");
    replyButton.type = "button";
    replyButton.className =
      "uk-button uk-button-danger uk-button-large comment-reply";
    replyButton.textContent = t.reply;
    replyButton.addEventListener("click", () =>
      setReplyTarget(comment, state, elements, t)
    );
    replyWrap.appendChild(replyButton);
  }

  bodyWrap.appendChild(titleWrap);
  bodyWrap.appendChild(messageWrap);
  if (replyWrap.childNodes.length) {
    bodyWrap.appendChild(replyWrap);
  }

  grid.appendChild(avatarWrap);
  grid.appendChild(bodyWrap);
  header.appendChild(grid);
  article.appendChild(header);

  if (comment.children && comment.children.length) {
    const childrenWrap = document.createElement("div");
    childrenWrap.className = "comment-children";
    childrenWrap.style.marginLeft = depth > 0 ? "30px" : "20px";
    comment.children.forEach((child) => {
      childrenWrap.appendChild(
        renderCommentNode(child, state, elements, t, depth + 1)
      );
    });
    article.appendChild(childrenWrap);
  }

  return article;
}

function appendMessageWithBreaks(container, text) {
  const lines = String(text).split(/\r?\n/);
  lines.forEach((line, index) => {
    if (index > 0) {
      container.appendChild(document.createElement("br"));
    }
    container.appendChild(document.createTextNode(line));
  });
}

function updateLoadMore(state, elements) {
  if (!elements.loadMoreWrapper || !elements.loadMoreBtn) {
    return;
  }
  if (state.page < state.lastPage) {
    elements.loadMoreWrapper.style.display = "block";
    elements.loadMoreBtn.disabled = false;
  } else {
    elements.loadMoreWrapper.style.display = "none";
  }
}

function updateCommentFormState(state, elements, t) {
  if (!elements.commentFormBlock) {
    return;
  }
  if (!state.acceptsComments) {
    if (elements.commentsClosed) {
      elements.commentsClosed.style.display = "block";
    }
    if (elements.commentForm) {
      elements.commentForm.style.display = "none";
    }
    if (elements.commentFormFeedback) {
      elements.commentFormFeedback.textContent = t.commentsClosedShort;
    }
  } else {
    if (elements.commentsClosed) {
      elements.commentsClosed.style.display = "none";
    }
    if (elements.commentForm) {
      elements.commentForm.style.display = "block";
    }
  }
}

function toggleCommentsStatus(elements, message, isError) {
  if (!elements.commentsStatus) {
    return;
  }
  if (!message) {
    elements.commentsStatus.style.display = "none";
    elements.commentsStatus.textContent = "";
    return;
  }
  elements.commentsStatus.style.display = "block";
  elements.commentsStatus.textContent = message;
  elements.commentsStatus.classList.toggle("uk-alert-danger", Boolean(isError));
}

function handleCommentSubmit(event, state, elements, t, apiBase) {
  event.preventDefault();
  if (!elements.commentForm || !state.acceptsComments) {
    return;
  }

  const author = (elements.commentAuthorInput?.value || "").trim();
  const message = (elements.commentMessageInput?.value || "").trim();
  const parentId = elements.commentParentInput?.value
    ? Number(elements.commentParentInput.value)
    : null;

  const payload = {
    slug: state.slug,
    author_name: author,
    message,
    parent_comment_id: parentId,
  };

  clearFormErrors(elements);
  setFormLoading(elements, t, true);
  toggleFormFeedback(elements, "");

  fetch(buildApiUrl(apiBase, "comments/push.php"), {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  })
    .then(async (response) => {
      if (response.status === 422) {
        const data = await response.json();
        throw { type: "validation", data };
      }
      if (!response.ok) {
        throw new Error("Comment submission failed");
      }
      return response.json();
    })
    .then((json) => {
      if (json?.comment) {
        mergeComments(state, [json.comment]);
        renderComments(state, elements, t);
        updateLoadMore(state, elements);
      }
      toggleFormFeedback(elements, t.success, false);
      resetCommentForm(state, elements, t);
    })
    .catch((error) => {
      if (error.type === "validation") {
        displayValidationErrors(error.data?.errors || {}, elements);
      } else {
        console.error("Comment submission error:", error);
        toggleFormFeedback(
          elements,
          t.submitError || t.commentsFetchError || "Unable to submit comment.",
          true
        );
      }
    })
    .finally(() => {
      setFormLoading(elements, t, false);
    });
}

function clearFormErrors(elements) {
  const errorElements = document.querySelectorAll("#comment-form .form-error");
  errorElements.forEach((el) => {
    el.style.display = "none";
    el.textContent = "";
  });
}

function displayValidationErrors(errors, elements) {
  Object.entries(errors).forEach(([field, messages]) => {
    const target = document.querySelector(
      `.form-error[data-error-for=\"${field}\"]`
    );
    if (target) {
      target.style.display = "block";
      target.textContent = Array.isArray(messages)
        ? messages.join(" ")
        : String(messages);
    }
  });
}

function setFormLoading(elements, t, isLoading) {
  if (!elements.commentSubmitBtn) {
    return;
  }
  if (isLoading) {
    elements.commentSubmitBtn.dataset.originalText =
      elements.commentSubmitBtn.textContent;
    elements.commentSubmitBtn.disabled = true;
    elements.commentSubmitBtn.textContent = t.posting;
  } else {
    elements.commentSubmitBtn.disabled = false;
    const original = elements.commentSubmitBtn.dataset.originalText;
    if (original) {
      elements.commentSubmitBtn.textContent = original;
    } else {
      elements.commentSubmitBtn.textContent = t.submit;
    }
  }
}

function toggleFormFeedback(elements, message, isError = false) {
  if (!elements.commentFormFeedback) {
    return;
  }
  elements.commentFormFeedback.textContent = message;
  elements.commentFormFeedback.style.color = isError ? "#dc2626" : "#16a34a";
}

function resetCommentForm(state, elements, t) {
  if (elements.commentForm) {
    elements.commentForm.reset();
  }
  if (elements.commentSlugInput) {
    elements.commentSlugInput.value = state.slug;
  }
  if (elements.commentParentInput) {
    elements.commentParentInput.value = "";
  }
  clearReplyTarget(state, elements);
  if (elements.commentAuthorInput) {
    elements.commentAuthorInput.focus();
  }
}

function setReplyTarget(comment, state, elements, t) {
  if (!elements.commentParentInput) {
    return;
  }
  elements.commentParentInput.value = comment.id;
  state.replyingTo = comment;
  if (elements.commentReplyIndicator && elements.commentReplyText) {
    elements.commentReplyText.textContent = `${t.reply}: ${comment.author_name}`;
    elements.commentReplyIndicator.style.display = "flex";
  }
  if (elements.commentMessageInput) {
    elements.commentMessageInput.focus();
  }
}

function clearReplyTarget(state, elements) {
  state.replyingTo = null;
  if (elements.commentParentInput) {
    elements.commentParentInput.value = "";
  }
  if (elements.commentReplyIndicator) {
    elements.commentReplyIndicator.style.display = "none";
  }
  if (elements.commentReplyText) {
    elements.commentReplyText.textContent = "";
  }
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
