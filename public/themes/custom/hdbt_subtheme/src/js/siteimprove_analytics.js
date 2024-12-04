// eslint-disable-next-line func-names
(function (Drupal) {
  const loadSiteimproveAnalytics = () => {

    // Load Siteimprove analytics only if statistics cookies are allowed.
    if (Drupal.cookieConsent.getConsentStatus(['statistics'])) {
      // eslint-disable-next-line no-multi-assign
      const d = document;
      const b = d.body;
      const g = d.createElement('script');
      g.type = 'text/javascript';
      g.async = true;
      g.src = '//siteimproveanalytics.com/js/siteanalyze_6047173.js';
      b.appendChild(g);
    }
  };

  // Initialize Siteimprove analytics.
  if (Drupal.cookieConsent.initialized()) {
    loadSiteimproveAnalytics();
  } else {
    Drupal.cookieConsent.loadFunction(loadSiteimproveAnalytics);
  }
})(Drupal);
