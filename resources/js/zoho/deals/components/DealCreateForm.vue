<script setup lang="ts">
import { DEAL_STRINGS as STR } from '@/zoho/deals/constants/strings'
import { useDealForm } from '@/zoho/deals/composables/useDealForm'
import { useCustomers } from '@/zoho/deals/composables/useCustomers'
import { ref, watch } from 'vue'

// use form composable
const {
  form,
  loading,
  success,
  serverResponse,
  errors,
  canSubmit,
  sources,
  submit,
} = useDealForm()

// load customers list
const { customers, loading: loadingCustomers } = useCustomers()

// selected existing customer
const selectedCustomerId = ref<number | ''>('')

function clearCustomerId() {
  form.customer.id = undefined;
  selectedCustomerId.value = '';
}

function clearCustomerSelect() {
  selectedCustomerId.value = '';
}

// when user selects an existing customer, populate form fields
watch(selectedCustomerId, (id) => {
  if (!id) {
    // clear only if user explicitly deselects
    return
  }
  const c = customers.value.find(x => x.id === id)
  if (c) {
    form.customer.id = c.id
    form.customer.first_name = c.first_name ?? ''
    form.customer.last_name  = c.last_name  ?? ''
    form.customer.email      = c.email      ?? ''
  }
})

watch(success, (val) => {
  if (val) {
    selectedCustomerId.value = ''
  }
})
</script>

<template>
  <div class="max-w-2xl mx-auto mt-10 bg-white shadow rounded-xl p-6">
    <h1 class="text-2xl font-bold mb-6">
      {{ STR.pageTitle }}
    </h1>

    <div
      v-if="success"
      class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-green-800 text-sm"
    >
      {{ STR.success }}
    </div>

    <div
      v-if="errors.general"
      class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-red-800 text-sm"
    >
      {{ errors.general }}
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Deal -->
      <div>
        <h2 class="text-lg font-semibold mb-3">{{ STR.dealSection }}</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ STR.dealName }} *
            </label>
            <input
                v-model="form.deal.name"
                type="text"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="errors.deal_name ? 'border-red-400' : ''"
                :placeholder="STR.placeholderDealName"
                required
            />
            <p v-if="errors.deal_name" class="mt-1 text-xs text-red-600">
              {{ errors.deal_name }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ STR.source }} *
            </label>
            <select
              v-model="form.deal.source"
              class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="errors.source ? 'border-red-400' : ''"
              required
            >
              <option value="">{{ STR.selectSource }}</option>
              <option v-for="s in sources" :key="s" :value="s">{{ s }}</option>
            </select>
            <p v-if="errors.source" class="mt-1 text-xs text-red-600">
              {{ errors.source }}
            </p>
          </div>
        </div>
      </div>

      <!-- Customer -->
      <div>
        <h2 class="text-lg font-semibold mb-3">{{ STR.customerSection }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ STR.firstName }}
            </label>
            <input
              v-model="form.customer.first_name"
              @input="clearCustomerId"
              type="text"
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ STR.lastName }}
            </label>
            <input
              v-model="form.customer.last_name"
              @input="clearCustomerId"
              type="text"
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ STR.email }}
            </label>
            <input
              v-model="form.customer.email"
              @input="clearCustomerId"
              type="email"
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="errors.email ? 'border-red-400' : ''"
              :placeholder="STR.placeholderEmail"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-red-600">
              {{ errors.email }}
            </p>
          </div>
        </div>
      </div>

      <!-- Existing customer select -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ STR.existingCustomer }}
        </label>
        <select
          v-model="selectedCustomerId"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">{{ STR.existingCustomerPlaceholder }}</option>
          <option
            v-for="c in customers"
            :key="c.id"
            :value="c.id"
          >
            {{ (c.first_name ?? '') + (c.last_name ? ' ' + c.last_name : '') }} — {{ c.email ?? 'no email' }}
          </option>
        </select>
        <p v-if="loadingCustomers" class="mt-1 text-xs text-gray-500">Loading customers…</p>
      </div>

      <!-- Submit -->
      <div class="pt-2">
        <button
          type="submit"
          @click="clearCustomerSelect"
          class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 disabled:opacity-60"
          :disabled="!canSubmit || loading"
        >
          <span v-if="!loading">{{ STR.submit }}</span>
          <span v-else>...</span>
        </button>
      </div>

      <pre v-if="serverResponse" class="mt-6 text-xs bg-gray-50 p-3 rounded border overflow-auto">
        {{ serverResponse }}
      </pre>
    </form>
  </div>
</template>

<script lang="ts">
export default { name: 'DealCreateForm' }
</script>
