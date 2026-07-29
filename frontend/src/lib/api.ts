import { ApiError } from "./api-error";

export type Paginated<T> = {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
};

type Options = Omit<RequestInit, "body"> & { body?: unknown; idempotencyKey?: string; raw?: boolean };

/**
 * For Client Components: calls the same-origin proxy at /api/backend/*,
 * which forwards to Laravel with the session token attached server-side.
 * The browser never sees the Bearer token.
 *
 * By default unwraps the Laravel `{ data: ... }` envelope. Pass `raw: true`
 * for paginated endpoints so `meta` survives — see `Paginated<T>`.
 */
export async function api<T>(path: string, options: Options = {}): Promise<T> {
  const { body, idempotencyKey, headers, raw, ...rest } = options;
  const isFormData = typeof FormData !== "undefined" && body instanceof FormData;

  const res = await fetch(`/api/backend${path}`, {
    ...rest,
    headers: {
      Accept: "application/json",
      ...(body && !isFormData ? { "Content-Type": "application/json" } : {}),
      ...(idempotencyKey ? { "Idempotency-Key": idempotencyKey } : {}),
      ...headers,
    },
    body: isFormData ? (body as FormData) : body !== undefined ? JSON.stringify(body) : undefined,
  });

  const isJson = res.headers.get("content-type")?.includes("application/json");
  const payload = isJson ? await res.json() : undefined;

  if (!res.ok) {
    throw new ApiError(payload?.message ?? `Permintaan gagal (${res.status})`, res.status, payload?.errors);
  }

  return (raw ? payload : (payload?.data ?? payload)) as T;
}

export { ApiError };
