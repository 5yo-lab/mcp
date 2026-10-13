const baseUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1';

export function manageKey(): string {
  return import.meta.env.VITE_MANAGE_API_KEY ?? '';
}

export async function api<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const key = manageKey();
  const res = await fetch(`${baseUrl}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${key}`,
      'Content-Type': 'application/json',
      ...(options.headers ?? {}),
    },
  });
  const body = await res.json();
  if (!res.ok) {
    throw body;
  }
  return body as T;
}
