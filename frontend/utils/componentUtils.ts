/**
 * Component utilities for Vue 3
 * Provides helper functions for safe component handling
 */
import { markRaw, shallowRef, type Component } from 'vue'

/**
 * Safely wraps a component with markRaw to prevent reactivity
 * @param component The component to wrap
 * @returns The wrapped component
 */
export function safeComponent<T extends Component>(component: T): T {
  return markRaw(component)
}

/**
 * Creates a shallow ref specifically for components
 * This prevents Vue from making components reactive
 * @param initialValue Optional initial component value
 * @returns A shallow ref for the component
 */
export function componentRef<T extends Component | null = null>(initialValue: T = null as T) {
  return shallowRef<T>(initialValue)
}

/**
 * Safely loads a dynamic component
 * @param loader Function that returns a Promise with the component
 * @returns The loaded component wrapped with markRaw
 */
export async function loadDynamicComponent<T extends Component>(
  loader: () => Promise<any>
): Promise<T> {
  try {
    const loaded = await loader()
    const component = 'default' in loaded ? loaded.default : loaded
    return safeComponent(component as T)
  } catch (err) {
    console.error('Failed to load component:', err)
    throw err
  }
}

/**
 * Registers a component and ensures it's wrapped with markRaw
 * Useful when working with component libraries
 * @param components Object containing components to register
 * @returns Object with all components wrapped with markRaw
 */
export function registerSafeComponents<T extends Record<string, Component>>(components: T): T {
  const safeComponents = { ...components }

  for (const key in safeComponents) {
    safeComponents[key] = safeComponent(safeComponents[key])
  }

  return safeComponents
}

/**
 * Creates a component registry with all components marked as non-reactive
 * Useful for dynamic component loading
 * @param registry Object containing component loaders
 * @returns Safe component registry with loaders wrapped to use markRaw
 */
export function createSafeComponentRegistry<T extends Record<string, () => Promise<any>>>(registry: T): T {
  const safeRegistry = { ...registry } as T

  for (const key in safeRegistry) {
    const originalLoader = safeRegistry[key]

    // Using proper TypeScript type assertion
    safeRegistry[key] = (async () => {
      const loaded = await originalLoader()
      const component = 'default' in loaded ? loaded.default : loaded
      return safeComponent(component)
    }) as T[typeof key]
  }

  return safeRegistry
}

/**
 * Function to safely initialize Vue components that might trigger reactive warnings
 * This is especially useful for component libraries or third-party plugins
 * @param setupFn Function that sets up the components
 * @returns Result from the setup function, with any components wrapped with markRaw
 */
export function safeComponentSetup<T>(setupFn: () => T): T {
  // Run the setup function to get components or other values
  const result = setupFn()

  // If the result is an object, process any component properties
  if (result && typeof result === 'object') {
    // Process each property that might be a component
    for (const key in result) {
      const value = (result as any)[key]

      // Check if the property might be a Vue component
      if (
        value &&
        typeof value === 'object' &&
        ('render' in value || 'setup' in value || '__file' in value)
      ) {
        // Wrap suspected component with markRaw
        (result as any)[key] = safeComponent(value)
      }
    }
  }

  return result
}
