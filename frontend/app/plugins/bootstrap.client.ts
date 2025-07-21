// Bootstrap JavaScript integration for client-side only
export default defineNuxtPlugin(() => {
  if (import.meta.client) {
    // Import Bootstrap JavaScript bundle
    import('bootstrap/dist/js/bootstrap.bundle.min.js')
  }
})