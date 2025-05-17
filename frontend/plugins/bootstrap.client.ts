import * as bootstrap from 'bootstrap';

export default defineNuxtPlugin(() => {
  // Make Bootstrap available globally (optional, usually not needed if using data attributes)
  // return {
  //   provide: {
  //     bootstrap
  //   }
  // };

  // You might initialize specific components here if needed, e.g.:
  // const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  // tooltipTriggerList.map(function (tooltipTriggerEl) {
  //   return new bootstrap.Tooltip(tooltipTriggerEl)
  // })

  // For most components like Dropdown, Collapse, etc., simply importing is enough
  // as they initialize automatically via data attributes.
});