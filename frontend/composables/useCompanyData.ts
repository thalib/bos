import { ref, onMounted } from 'vue';

export interface Company {
  name: string;
  prefix: string;
  address: string;
}

export interface Bank {
  name: string;
  details: string;
}

export function useCompanyData() {  const companies = ref<Company[]>([]);
  const banks = ref<Bank[]>([]);
  const loadingCompanies = ref(false);
  const loadingBanks = ref(false);
  const error = ref<Error | null>(null);

  // Fetch companies from API
  const fetchCompanies = async () => {
    loadingCompanies.value = true;    try {
      const response = await fetch('http://localhost:4000/companies');
      if (!response.ok) {
        throw new Error(`Failed to fetch companies: ${response.status} ${response.statusText}`);
      }
      companies.value = await response.json();
    } catch (e) {
      console.error('Error fetching companies:', e);
      error.value = e instanceof Error ? e : new Error('Unknown error fetching companies');
    } finally {
      loadingCompanies.value = false;
    }
  };

  // Fetch banks from API
  const fetchBanks = async () => {
    loadingBanks.value = true;    try {
      const response = await fetch('http://localhost:4000/banks');
      if (!response.ok) {
        throw new Error(`Failed to fetch banks: ${response.status} ${response.statusText}`);
      }
      banks.value = await response.json();
    } catch (e) {
      console.error('Error fetching banks:', e);
      error.value = e instanceof Error ? e : new Error('Unknown error fetching banks');
    } finally {
      loadingBanks.value = false;
    }
  };

  // Load data on component mount
  onMounted(() => {
    fetchCompanies();
    fetchBanks();
  });

  return {
    companies,
    banks,
    loadingCompanies,
    loadingBanks,
    error,
    fetchCompanies,
    fetchBanks
  };
}
