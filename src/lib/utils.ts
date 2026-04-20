import axios from "axios";
import i18n from "./i18n";
import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'https://mami.dinhbarista.com/api';

export async function fetchApi<T>(endpoint: string, options: any = {}, withAuth: boolean | 'admin' = false): Promise<T> {
  const locale = i18n.language || 'de';
  const defaultHeaders: Record<string, string> = {
    Accept: 'application/json',
    'X-Locale': locale,
  };
  let headers = { ...defaultHeaders, ...(options.headers || {}) };
  if (options.body instanceof FormData) {
    // Để axios tự set Content-Type multipart
  } else {
    headers['Content-Type'] = 'application/json';
  }
  if (withAuth) {
    let token = null;
    if (withAuth === 'admin') {
      token = localStorage.getItem('admin_token');
    } else {
      token = localStorage.getItem('user_token');
    }
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
  }
  const method = options.method || 'GET';
  try {
    const res = await axios({
      url: `${API_BASE}${endpoint}`,
      method,
      headers,
      data: options.body,
      params: options.params,
    });
    return res.data;
  } catch (err: any) {
    // Handle 401 Unauthorized - Auto logout
    if (err.response && err.response.status === 401) {
      // Clear tokens from localStorage
      localStorage.removeItem('user_token');
      localStorage.removeItem('admin_token');

      // Redirect to home page and reload to reset app state
      if (typeof window !== 'undefined') {
        window.location.href = '/';
      }

      throw new Error('Unauthorized access. Please login again.');
    }

    if (err.response && err.response.data) {
      throw err.response.data;
    }
    throw err;
  }
}
