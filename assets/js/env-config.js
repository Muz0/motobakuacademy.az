(function (global) {
  // Single toggle to flip between local/dev and production behavior.
  // Set DEBUG = true when developing locally; set to false for production.
  const DEBUG = true;

  // Centralized endpoints and feature flags that depend on the environment.
  const LOCAL_ORIGIN = "http://localhost/motobakuacademy.az";
  const PROD_ORIGIN = "https://motobakuacademy.az";

  const apiBase =
    (DEBUG ? LOCAL_ORIGIN : PROD_ORIGIN).replace(/\/$/, "") + "/api";

  const config = {
    DEBUG,
    apiBase,
    csrfProtection: !DEBUG,
  };

  global.DEBUG = DEBUG;
  global.APP_CONFIG = Object.freeze(config);
})(typeof window !== "undefined" ? window : this);
