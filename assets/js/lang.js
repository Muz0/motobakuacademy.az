// Read the lang attribute from <html>
const htmlLang = document.documentElement.lang;

// Select the language link by class
const languageLink = document.querySelector(".language"); // selects the first element with class "language"

// Update the text based on the lang value
if (languageLink) {
  switch (htmlLang) {
    case "az":
      languageLink.textContent = "AZ";
      break;
    case "en":
      languageLink.textContent = "EN";
      break;
    case "ru":
      languageLink.textContent = "RU";
      break;
    default:
      languageLink.textContent = "Languages";
  }
}
