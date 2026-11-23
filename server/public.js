(function () {
  "use strict";

  document.addEventListener("submit", async function (event) {
    var form = event.target.closest(".swarmbyte-finder-form");
    if (!form) return;
    event.preventDefault();

    var section = form.closest(".swarmbyte-article-finder");
    var status = section.querySelector(".swarmbyte-finder-status");
    var results = section.querySelector(".swarmbyte-finder-results");
    var button = form.querySelector("button");
    var query = new FormData(form).get("q").trim();
    status.textContent = "Searching…";
    results.replaceChildren();
    button.disabled = true;

    try {
      var response = await fetch(form.dataset.endpoint + "?q=" + encodeURIComponent(query), { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error();
      var articles = await response.json();
      status.textContent = articles.length ? articles.length + (articles.length === 1 ? " article found." : " articles found.") : "No matching articles. Try a broader topic.";
      articles.forEach(function (article) {
        var item = document.createElement("article");
        var title = document.createElement("h3");
        var excerpt = document.createElement("p");
        var link = document.createElement("a");
        title.textContent = article.title;
        excerpt.textContent = article.excerpt;
        link.href = article.url;
        link.textContent = "Read article";
        item.append(title, excerpt, link);
        results.appendChild(item);
      });
    } catch (_error) {
      status.textContent = "Search is unavailable right now. Please try again.";
    } finally {
      button.disabled = false;
    }
  });
})();
