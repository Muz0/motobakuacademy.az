(function (global) {
  // Single toggle to flip between local/dev and production behavior.
  // Set DEBUG = true when developing locally; set to false for production.
  const DEBUG = false;
  // Local XAMPP serves this repo from /motobakuacademy.az while production
  // serves from the domain root. This affects JS-built URLs such as API calls;
  // static HTML asset URLs may still 404 visually on localhost if root-relative.
  const USE_LOCAL_PATH = false;

  // Centralized endpoints and feature flags that depend on the environment.
  const LOCAL_PATH = "/motobakuacademy.az";
  const PROD_ORIGIN = "https://motobakuacademy.az";
  const location = global.location;
  const isLocalHost =
    location &&
    /^(localhost|127\.0\.0\.1|\[::1\])$/i.test(location.hostname);
  const localOrigin =
    location && isLocalHost && USE_LOCAL_PATH
      ? location.origin + (location.pathname.startsWith(LOCAL_PATH) ? LOCAL_PATH : "")
      : location
        ? location.origin
        : PROD_ORIGIN;
  const publicBasePath =
    DEBUG && isLocalHost && USE_LOCAL_PATH && location.pathname.startsWith(LOCAL_PATH)
      ? LOCAL_PATH
      : "";

  const apiBase =
    (DEBUG ? localOrigin : PROD_ORIGIN).replace(/\/$/, "") + "/api";
  const assetBase = (DEBUG ? publicBasePath : "").replace(/\/$/, "");
  const assetUrl = function (path) {
    return assetBase + "/" + String(path || "").replace(/^\/+/, "");
  };

  const config = {
    DEBUG,
    USE_LOCAL_PATH,
    localPath: LOCAL_PATH,
    publicBasePath,
    apiBase,
    assetBase,
    assetUrl,
    csrfProtection: !DEBUG,
  };

  global.DEBUG = DEBUG;
  global.APP_CONFIG = Object.freeze(config);
})(typeof window !== "undefined" ? window : this);
