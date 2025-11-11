((Drupal) => {
  Drupal.behaviors.formAutosubmit = {
    attach (context) {
      context.querySelectorAll('.hakuvahti-confirmation form').forEach(form => {
        form.submit()
      })
    }
  };
})(Drupal);
