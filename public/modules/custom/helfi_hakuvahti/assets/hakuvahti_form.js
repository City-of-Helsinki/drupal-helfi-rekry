((Drupal) => {
  Drupal.behaviors.formAutosubmit = {
    attach (context) {
      context.querySelectorAll('form.hakuvahti-form').forEach(form => form.submit());
    }
  };
})(Drupal);
