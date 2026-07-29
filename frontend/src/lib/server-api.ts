import "server-only";
import { getSession } from "./session";
import { ApiError } from "./api-error";
import type { Paginated } from "./api";

const API_INTERNAL_URL = process.env.API_INTERNAL_URL ?? "http://nginx/api/v1";

type Options = Omit<RequestInit, "body"> & { body?: unknown; raw?: boolean };

/**
 * For Server Components / Server Actions: calls the Laravel API directly
 * over the docker-internal network, injecting the session token. Client
 * Components must use `lib/api.ts` instead (same-origin proxy) since they
 * have no access to the httpOnly session cookie or this internal URL.
 *
 * By default unwraps the Laravel `{ data: ... }` envelope. Pass `raw: true`
 * for paginated endpoints so `meta` survives — see `Paginated<T>`.
 */
export async function serverApi<T>(path: string, options: Options = {}): Promise<T> {
  const session = await getSession();
  const { body, headers, raw, ...rest } = options;

  const res = await fetch(`${API_INTERNAL_URL}${path}`, {
    ...rest,
    headers: {
      Accept: "application/json",
      ...(body ? { "Content-Type": "application/json" } : {}),
      ...(session ? { Authorization: `Bearer ${session.token}` } : {}),
      ...headers,
    },
    body: body ? JSON.stringify(body) : undefined,
    cache: "no-store",
  });

  const isJson = res.headers.get("content-type")?.includes("application/json");
  const payload = isJson ? await res.json() : undefined;

  if (!res.ok) {
    throw new ApiError(payload?.message ?? `Permintaan gagal (${res.status})`, res.status, payload?.errors);
  }

  return (raw ? payload : (payload?.data ?? payload)) as T;
}

/** For paginated list endpoints — preserves `meta` alongside `data`. */
export async function serverApiPaginated<T>(path: string, options: Omit<Options, "raw"> = {}): Promise<Paginated<T>> {
  return serverApi<Paginated<T>>(path, { ...options, raw: true });
}

/** Like serverApi, but returns null on 403/404 instead of throwing — handy for optional lookups. */
export async function serverApiOrNull<T>(path: string, options: Options = {}): Promise<T | null> {
  try {
    return await serverApi<T>(path, options);
  } catch (error) {
    if (error instanceof ApiError && (error.status === 403 || error.status === 404)) {
      return null;
    }
    throw error;
  }
}
