import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { getSession } from "@/lib/session";

const API_INTERNAL_URL = process.env.API_INTERNAL_URL ?? "http://nginx/api/v1";

/**
 * Single catch-all proxy forwarding every /api/backend/* call to the Laravel
 * API over the docker-internal network, injecting the Sanctum token
 * server-side. This is what lets Client Components call the backend at all
 * — the httpOnly session cookie is never readable from browser JS, so
 * without this proxy there'd be no way for client code to authenticate.
 * One route handles ~150 backend endpoints instead of hand-writing each.
 */
async function handler(request: NextRequest, context: { params: Promise<{ path: string[] }> }) {
  const { path } = await context.params;
  const session = await getSession();

  const url = new URL(`${API_INTERNAL_URL}/${path.join("/")}`);
  url.search = request.nextUrl.search;

  const headers = new Headers();
  headers.set("Accept", "application/json");
  const contentType = request.headers.get("content-type");
  if (contentType) headers.set("Content-Type", contentType);
  if (session) headers.set("Authorization", `Bearer ${session.token}`);
  const idempotencyKey = request.headers.get("idempotency-key");
  if (idempotencyKey) headers.set("Idempotency-Key", idempotencyKey);

  const init: RequestInit & { duplex?: "half" } = { method: request.method, headers };

  if (!["GET", "HEAD"].includes(request.method)) {
    // Stream the body through as-is (required for multipart file uploads —
    // reading it as text/json here would corrupt binary attachments).
    init.body = request.body;
    init.duplex = "half";
  }

  const upstream = await fetch(url, init);
  const text = await upstream.text();

  const responseHeaders = new Headers({ "Content-Type": upstream.headers.get("content-type") ?? "application/json" });
  const disposition = upstream.headers.get("content-disposition");
  if (disposition) responseHeaders.set("Content-Disposition", disposition);

  return new NextResponse(text, { status: upstream.status, headers: responseHeaders });
}

export { handler as GET, handler as POST, handler as PUT, handler as PATCH, handler as DELETE };
