import { ref, onMounted } from 'vue';

export function useProducts() {
  const products = ref([]);
  const loading = ref(false);
  const error = ref<Error | null>(null);

  // Fetch products from the API
  onMounted(async () => {
    loading.value = true;
    try {
      const response = await fetch('http://localhost:4000/products');
      if (!response.ok) {
        throw new Error(`Failed to fetch products: ${response.status} ${response.statusText}`);
      }
      products.value = await response.json();    } catch (e) {
      console.error('Error fetching products:', e);
      error.value = e instanceof Error ? e : new Error('Unknown error');
    } finally {
      loading.value = false;
    }
  });

  return { products, loading, error };
}
