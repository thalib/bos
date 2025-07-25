interface MenuItem {
	type: 'item' | 'section' | 'divider';
	id?: number;
	name?: string;
	path?: string;
	icon?: string;
	order: number;
	title?: string; // for sections
	items?: MenuItem[]; // for sections
	mode?: 'form' | 'doc'; // optional mode for items
}

interface MenuApiResponse {
	success: boolean;
	data: {
		data?: MenuItem[];
	} | MenuItem[];
	message?: string;
}

class MenuService {
	private authService = useAuthService();
	private apiService = useApiService();
	private notifyService = useNotifyService();
	private CACHE_KEY = 'menuItems';
	private CACHE_EXPIRY = 24 * 60 * 60 * 1000; // 1 day in milliseconds
	private readonly AUTH_TIMEOUT = 5000; // Timeout for auth check in ms
	private readonly AUTH_CHECK_INTERVAL = 100; // Interval for auth check in ms

	/**
	 * Checks if the cache is expired based on the timestamp.
	 */
	private isCacheExpired(timestamp: number): boolean {
		return Date.now() - timestamp > this.CACHE_EXPIRY;
	}

	/**
	 * Utility to get data from localStorage.
	 */
	private cacheRead<T>(key: string): T | null {
		try {
			const item = localStorage.getItem(key);
			return item ? JSON.parse(item) : null;
		} catch (error) {
			this.notifyService.warning('Failed to parse localStorage data', 'Cache Warning');
			return null;
		}
	}

	/**
	 * Utility to save data in localStorage.
	 */
	private cacheSave<T>(key: string, value: T): void {
		try {
			localStorage.setItem(key, JSON.stringify(value));
		} catch (error) {
			this.notifyService.warning('Failed to save data to localStorage', 'Cache Warning');
		}
	}

	/**
	 * Utility to delete data from localStorage.
	 */
	private cacheDel(key: string): void {
		try {
			localStorage.removeItem(key);
		} catch (error) {
			this.notifyService.warning('Failed to remove data from localStorage', 'Cache Warning');
		}
	}

	/**
	 * Retrieves cached menu items from localStorage.
	 */
	private getCachedMenuItems(): MenuItem[] | null {
		if (typeof window === 'undefined') return null;

		const cacheData = this.cacheRead<{ menuItems: MenuItem[]; timestamp: number }>(this.CACHE_KEY);
		if (!cacheData || this.isCacheExpired(cacheData.timestamp)) {
			this.cacheDel(this.CACHE_KEY);
			return null;
		}

		return cacheData.menuItems;
	}

	/**
	 * Fetches menu items from the API.
	 */
	private async fetchMenuItemsFromApi(): Promise<MenuItem[]> {
		if (!this.authService.isAuthenticated.value) {
			throw new Error('User is not authenticated');
		}

		try {
			const response = await this.apiService.request<MenuApiResponse>('/app/menu', { method: 'GET' });

			if (response.success && response.data) {
				const menuData = response.data.data || response.data;
				if (Array.isArray(menuData)) {
					this.cacheSave(this.CACHE_KEY, { menuItems: menuData, timestamp: Date.now() });
					return menuData;
				} else {
					throw new Error(response.message || 'Invalid menu data format');
				}
			} else {
				throw new Error(response.message || 'Failed to fetch menu items');
			}
		} catch (error) {
			this.notifyService.error(`Failed to fetch menu items: ${error.message}`, 'Menu Error');
			throw error;
		}
	}

	/**
	 * Waits for authentication to complete.
	 */
	private async waitForAuth(): Promise<void> {
		const start = Date.now();
		while (!this.authService.isInitialized.value || !this.authService.isAuthenticated.value) {
			if (Date.now() - start > this.AUTH_TIMEOUT) {
				this.notifyService.error('Authentication timeout', 'Auth Error');
				throw new Error('Authentication timeout');
			}
			await new Promise((resolve) => setTimeout(resolve, this.AUTH_CHECK_INTERVAL));
		}
	}

	/**
	 * Retrieves menu items, either from cache or API.
	 */
	public async get(): Promise<MenuItem[]> {
		await this.waitForAuth(); // Ensure authentication is complete before fetching menu items

		const cachedItems = this.getCachedMenuItems();
		if (cachedItems) return cachedItems;

		return await this.fetchMenuItemsFromApi();
	}

	/**
	 * Retrieves a specific menu item by its path.
	 */
	public getMenuDataByPath(path: string): MenuItem | undefined {
		const cachedItems = this.getCachedMenuItems();
		if (!cachedItems) return undefined;

		for (const item of cachedItems) {
			if (item.type === 'item' && item.path === path) {
				return item;
			} else if (item.type === 'section' && item.items) {
				const found = item.items.find(subItem => subItem.path === path);
				if (found) return found;
			}
		}

		return undefined;
	}
}

// Singleton instance
let menuServiceInstance: MenuService | null = null;

export function useMenuService(): MenuService {
	if (!menuServiceInstance) {
		menuServiceInstance = new MenuService();
	}
	return menuServiceInstance;
}
