function initTeamGrid() {
  const grid = document.querySelector(".team-grid");
  if (!grid) return;

  const emptyMessage =
    grid.dataset.emptyMessage ||
    "Our team will appear here once members are added.";

  const origin =
    (window.location && window.location.origin) || "https://motobakuacademy.az";
  const defaultApiBase = `${origin.replace(/\/$/, "")}/api`;
  const envConfig = window.APP_CONFIG || {};
  const apiBase = normalizeApiBase(envConfig.apiBase || defaultApiBase);

  fetch(`${apiBase}/team.php`)
    .then(parseJsonResponse)
    .then((payload) => {
      const members = Array.isArray(payload.data) ? payload.data : [];
      renderTeam(grid, members);
      renderTeamDescription(payload.description);
    })
    .catch((err) => {
      console.error("Unable to load team:", err);
      grid.innerHTML = `<div class="uk-width-1-1"><p>${emptyMessage}</p></div>`;
    });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initTeamGrid);
} else {
  initTeamGrid();
}

function normalizeApiBase(raw) {
  const trimmed = String(raw || "").trim();
  if (!trimmed) return "";
  return trimmed.endsWith("/") ? trimmed.slice(0, -1) : trimmed;
}

async function parseJsonResponse(response) {
  const contentType = response.headers.get("content-type") || "";
  if (!response.ok) {
    throw new Error(`Failed to load team: HTTP ${response.status}`);
  }
  if (!contentType.includes("application/json")) {
    const body = await response.text();
    const excerpt = body.replace(/\s+/g, " ").trim().slice(0, 160);
    throw new Error(`Failed to load team: expected JSON, received ${contentType || "unknown content type"}${excerpt ? ` (${excerpt})` : ""}`);
  }
  return response.json();
}

function renderTeam(grid, members) {
  grid.innerHTML = "";

  if (!members.length) {
    const empty = document.createElement("div");
    empty.className = "uk-width-1-1";
    empty.innerHTML = "<p>Hazırda komanda üzvləri əlavə edilməyib.</p>";
    grid.appendChild(empty);
    return;
  }

  adjustGridClasses(grid, members.length);

  members
    .slice(0, 7) // show up to 7 members as requested
    .forEach((member) => {
      const item = document.createElement("div");
      const clip = document.createElement("div");
      clip.className = "uk-inline-clip uk-transition-toggle";
      clip.tabIndex = 0;

      const img = document.createElement("img");
      img.src = member.photo_url || "assets/images/team/mirfariz.png";
      img.alt = member.name || "Team member";
      img.width = 360;
      img.height = 480;
      img.loading = "lazy";
      img.decoding = "async";

      const caption = document.createElement("div");
      caption.className =
        "uk-position-bottom uk-position-small uk-light uk-background-muted uk-text-center";

      const strong = document.createElement("strong");
      const role = member.role ? `<br />${escapeHtml(member.role)}` : "";
      strong.innerHTML = `${escapeHtml(member.name || "")}${role}`;

      caption.appendChild(strong);
      clip.appendChild(img);
      clip.appendChild(caption);
      item.appendChild(clip);

      grid.appendChild(item);
    });
}

function renderTeamDescription(text) {
  const target = document.getElementById("team-description");
  if (!target) return;
  const safe = escapeHtml(text || "");
  target.innerHTML = safe ? `<p>${safe}</p>` : "";
}

function escapeHtml(str) {
  return String(str || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function adjustGridClasses(grid, count) {
  const sClass = count <= 1 ? "uk-child-width-1-1@s" : "uk-child-width-1-2@s";
  const mCols = Math.min(Math.max(count, 1), 6);
  const mClass = `uk-child-width-1-${mCols}@m`;

  grid.className = grid.className
    .split(" ")
    .filter(
      (cls) =>
        !cls.startsWith("uk-child-width-1-") &&
        cls.trim() !== "" &&
        cls !== "uk-grid-small"
    )
    .join(" ")
    .trim();

  grid.classList.add("uk-grid-small", sClass, mClass);
}
