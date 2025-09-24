import axios from 'axios';
import type { CreateDealRequest, CreateDealResponse, CustomerListItem } from '@/zoho/deals/types/ApiTypes';

// Axios instance for Zoho Deals module
export const http = axios.create({
  // baseURL: import.meta.env.VITE_API_BASE_URL ?? '/', // enable if frontend and backend are on different domains
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json',
  },
});

// API endpoints for Zoho Deals
export const API = {
  createDeal: '/api/deals',
  listCustomers: '/api/customers',
} as const;

// Create a new deal in backend and push to Zoho
export async function apiCreateDeal(payload: CreateDealRequest) {
  const { data } = await http.post<CreateDealResponse>(API.createDeal, payload);
  return data;
}

// Get customers from db
export async function apiListCustomers() {
  const { data } = await http.get<{ ok: boolean; data: CustomerListItem[] }>(API.listCustomers);
  return data.data;
}
