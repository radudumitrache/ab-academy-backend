import axios, { AxiosInstance } from 'axios';
import * as dotenv from 'dotenv';

dotenv.config();

const BASE_URL = (process.env.LARAVEL_API_URL ?? 'http://127.0.0.1').replace(/\/$/, '');

export function createApiClient(token: string): AxiosInstance {
  return axios.create({
    baseURL: `${BASE_URL}/api/teacher`,
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    timeout: 30000,
  });
}

export function extractToken(authHeader: string | undefined): string {
  if (!authHeader) throw new Error('Missing Authorization header');
  const parts = authHeader.split(' ');
  if (parts.length !== 2 || parts[0].toLowerCase() !== 'bearer') {
    throw new Error('Invalid Authorization header format — expected: Bearer <token>');
  }
  return parts[1];
}
