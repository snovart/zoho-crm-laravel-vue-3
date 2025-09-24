import { reactive, ref, computed } from 'vue';
import { apiCreateDeal } from '@/zoho/deals/api/Api';
import { DEAL_STRINGS as STR } from '@/zoho/deals/constants/strings';
import { SOURCE_OPTIONS } from '@/zoho/deals/constants/options';
import type { FormState } from '@/zoho/deals/types/Types';

// Composable for managing deal form state and logic
export function useDealForm() {
  const form = reactive<FormState>({
    customer: { first_name: '', last_name: '', email: '' },
    deal: { name: '', source: '' },
  });

  const loading = ref(false);
  const success = ref(false);
  const serverResponse = ref<unknown | null>(null);

  const errors = reactive({
    deal_name: '',
    source: '',
    email: '',
    general: '',
  });

  function validate(): boolean {
    errors.deal_name = '';
    errors.source = '';
    errors.email = '';
    errors.general = '';
    let ok = true;

    if (!form.deal.name.trim()) { errors.deal_name = STR.errRequiredDealName; ok = false; }
    if (!form.deal.source) { errors.source = STR.errRequiredSource; ok = false; }
    if (form.customer.email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(form.customer.email)) { errors.email = STR.errInvalidEmail; ok = false; }
    }
    return ok;
  }

  const canSubmit = computed(() => !loading.value);

  function reset() {
    form.customer.first_name = '';
    form.customer.last_name = '';
    form.customer.email = '';
    form.deal.name = '';
    form.deal.source = '';
  }

  async function submit() {
    success.value = false;
    serverResponse.value = null;
    if (!validate()) return;

    loading.value = true;
    try {
      const resp = await apiCreateDeal({
        deal: { name: form.deal.name, source: form.deal.source },
        customer: {
            id: form.customer.id, 
            first_name: form.customer.first_name,
            last_name: form.customer.last_name,
            email: form.customer.email,
        },
      });
      serverResponse.value = resp;
      success.value = !!resp?.ok;
      reset();
    } catch (e: any) {
      errors.general =
        e?.response?.data?.message ||
        e?.response?.data?.error ||
        e?.message ||
        STR.errGeneral;
    } finally {
      loading.value = false;
    }
  }

  return {
    form,
    loading,
    success,
    serverResponse,
    errors,
    canSubmit,
    sources: SOURCE_OPTIONS,
    submit,
  };
}
