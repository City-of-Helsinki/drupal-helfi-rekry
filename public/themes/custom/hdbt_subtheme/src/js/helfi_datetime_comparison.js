// Turn datetime into text (for example "today"), used on a job page.
((Drupal) => {
  // Look for all time-elements from all metadata-wrappers.
  const timeElements = Array.from(document.getElementsByClassName('job-listing__metadata-wrapper'))
    ?.map(wrapper => wrapper.getElementsByTagName('time'))
    ?.map(timeElementCollection => Array.from(timeElementCollection))
    ?.flat()
  if (!timeElements) return;

  const today = new Date();
  Array.from(timeElements).forEach((element) => {
    const originalDate = new Date(element.getAttribute('datetime'));
    if (originalDate.toDateString() === today.toDateString()) {
      const minutes = originalDate.getUTCMinutes() < 10 ? `0${originalDate.getMinutes()}` : originalDate.getMinutes();
      element.innerText = `${Drupal.t('today')} ${originalDate.getHours()}:${minutes}`
    }
  });
})(Drupal);
