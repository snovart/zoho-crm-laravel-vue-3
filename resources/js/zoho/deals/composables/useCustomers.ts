import { ref, onMounted } from 'vue';
import { apiListCustomers } from '@/zoho/deals/api/Api';
import type { CustomerListItem } from '@/zoho/deals/types/ApiTypes';

// Composable to load customers list and expose selection
export function useCustomers() {
  const customers = ref<CustomerListItem[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  onMounted(async () => {
    loading.value = true;
    try {
      customers.value = await apiListCustomers();
    } catch (e: any) {
      error.value = e?.message ?? 'Failed to load customers';
    } finally {
      loading.value = false;
    }
  });

  return { customers, loading, error };
}
