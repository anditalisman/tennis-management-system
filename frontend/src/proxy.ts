import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { SESSION_COOKIE_NAME } from "@/lib/constants";

// Optimistic check only (cookie presence, not validity) — Next.js 16 renamed
// middleware.ts to proxy.ts; see AGENTS.md. Real authorization happens
// server-side per page/action via verifySession() and the backend's own
// RBAC, matching the Next.js auth guide's recommendation not to rely on
// this as the only line of defense.
export default function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const isAuthed = Boolean(request.cookies.get(SESSION_COOKIE_NAME)?.value);

  const isPortalRoute = pathname.startsWith("/portal");
  const isAuthPage = pathname === "/login" || pathname === "/pendaftaran";

  if (isPortalRoute && !isAuthed) {
    const loginUrl = new URL("/login", request.url);
    loginUrl.searchParams.set("next", pathname);
    return NextResponse.redirect(loginUrl);
  }

  if (isAuthPage && isAuthed) {
    return NextResponse.redirect(new URL("/portal/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico|api/).*)"],
};
