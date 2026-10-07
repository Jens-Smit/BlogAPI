(function () {
  'use strict';

  var data = window.GASTRO_OS_HELP;
  if (!data) {
    console.error('Hilfe-Inhalte fehlen: src/data/content.js muss vor app.js geladen werden.');
    return;
  }

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function clear(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
  }

  var state = { categoryId: null, query: '' };

  var dom = {
    categoryGrid: document.getElementById('category-grid'),
    articleGrid: document.getElementById('article-grid'),
    faqList: document.getElementById('faq-list'),
    searchInput: document.getElementById('search-input'),
    searchHint: document.getElementById('search-hint'),
    contactLead: document.getElementById('contact-lead'),
    contactList: document.getElementById('contact-list'),
    contactMail: document.getElementById('contact-mail'),
    year: document.getElementById('year')
  };

  function matchesQuery(text) {
    return text.toLowerCase().indexOf(state.query) !== -1;
  }

  function filterArticles(category) {
    if (!state.query) return category.articles;
    return category.articles.filter(function (article) {
      return matchesQuery(article.title) || article.steps.some(matchesQuery);
    });
  }

  function renderCategories() {
    clear(dom.categoryGrid);
    clear(dom.articleGrid);
    dom.articleGrid.hidden = true;
    dom.categoryGrid.hidden = false;

    data.categories.forEach(function (category) {
      if (state.query) {
        var articles = filterArticles(category);
        if (articles.length === 0) return;
      }

      var card = el('a', 'card');
      card.href = '#';
      card.dataset.categoryId = category.id;
      card.appendChild(el('span', 'card__icon', category.icon));
      card.appendChild(el('h3', 'card__title', category.title));
      card.appendChild(el('p', 'card__description', category.description));
      card.addEventListener('click', function (event) {
        event.preventDefault();
        state.categoryId = category.id;
        renderArticles(category);
      });
      dom.categoryGrid.appendChild(card);
    });
  }

  function renderArticles(category) {
    clear(dom.articleGrid);
    dom.categoryGrid.hidden = true;
    dom.articleGrid.hidden = false;

    var back = el('a', 'card__back', '← Zurück zu allen Themen');
    back.href = '#kategorien';
    back.addEventListener('click', function () {
      state.categoryId = null;
      renderCategories();
    });
    dom.articleGrid.appendChild(back);

    filterArticles(category).forEach(function (article) {
      var card = el('article', 'card card--article');
      card.appendChild(el('h3', 'card__title', article.title));
      var steps = el('ol', 'steps');
      article.steps.forEach(function (step) {
        steps.appendChild(el('li', null, step));
      });
      card.appendChild(steps);
      dom.articleGrid.appendChild(card);
    });
  }

  function renderFaq() {
    clear(dom.faqList);
    data.faq.forEach(function (entry) {
      if (state.query && !matchesQuery(entry.question) && !matchesQuery(entry.answer)) return;

      var item = el('div', 'faq__item');
      var button = el('button', 'faq__question', entry.question);
      button.type = 'button';
      button.setAttribute('aria-expanded', 'false');
      button.appendChild(el('span', 'faq__chevron', '▾'));
      button.addEventListener('click', function () {
        var open = item.classList.toggle('is-open');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      item.appendChild(button);
      item.appendChild(el('div', 'faq__answer', entry.answer));
      dom.faqList.appendChild(item);
    });
  }

  function renderContact() {
    dom.contactLead.textContent = data.contact.responseTime + '.';
    dom.contactList.appendChild(el('li', null, 'E-Mail: ' + data.contact.email));
    dom.contactList.appendChild(el('li', null, 'Telefon: ' + data.contact.phone));
    dom.contactList.appendChild(el('li', null, 'Erreichbarkeit: ' + data.contact.hours));
    dom.contactMail.href = 'mailto:' + data.contact.email;
    dom.year.textContent = String(new Date().getFullYear());
  }

  function render() {
    renderCategories();
    renderFaq();
  }

  dom.searchInput.addEventListener('input', function () {
    state.query = dom.searchInput.value.trim().toLowerCase();
    if (state.categoryId) {
      var category = data.categories.find(function (c) { return c.id === state.categoryId; });
      if (category) {
        renderArticles(category);
        if (state.query) renderFaq();
        return;
      }
    }
    render();
  });

  render();
  renderContact();
})();
