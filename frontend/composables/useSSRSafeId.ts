import { ref, onMounted } from 'vue'

/**
 * Composable for generating SSR-safe unique IDs
 * 
 * This avoids hydration mismatches by:
 * 1. Providing a stable fallback ID during SSR
 * 2. Generating a unique ID on the client side after hydration
 * 
 * @param prefix - The prefix for the ID (default: 'id')
 * @returns A reactive ref containing the ID
 */
export function useSSRSafeId(prefix: string = 'id') {
  const id = ref<string>(prefix)
  
  onMounted(() => {
    // Generate unique ID on client side only
    id.value = `${prefix}-${Math.random().toString(36).substr(2, 9)}`
  })
  
  return id
}
