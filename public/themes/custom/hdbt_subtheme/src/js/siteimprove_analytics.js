// eslint-disable-next-line func-names
(function (Drupal) {
  function loadSiteimproveAnalytics() {
    if (typeof Drupal.eu_cookie_compliance === 'undefined') {
      return;
    }

    // Load Siteimprove analytics only if statistics cookies are allowed.
    if (Drupal.eu_cookie_compliance.hasAgreed('statistics')) {
      // eslint-disable-next-line no-multi-assign
      const d = document;
      const b = d.body;
      const g = d.createElement('script');
      g.type = 'text/javascript';
      g.async = true;
      g.src = '//siteimproveanalytics.com/js/siteanalyze_6047173.js';
      b.appendChild(g);
    }
  }

  // Load when cookie settings are changed.
  document.addEventListener('eu_cookie_compliance.changeStatus', loadSiteimproveAnalytics, false);

  // Load on page load.
  document.addEventListener('DOMContentLoaded', loadSiteimproveAnalytics, false);
})(Drupal);
