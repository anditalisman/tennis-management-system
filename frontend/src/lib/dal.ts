import "server-only";
import { cache } from "react";
import { redirect } from "next/navigation";
import { getSession, type Session } from "./session";

/**
 * Verifies a session exists, redirecting to /login if not. Memoized per
 * request via React's cache() so multiple Server Components calling this
 * during one render don't each re-read the cookie.
 */
export const verifySession = cache(async (): Promise<Session> => {
  const session = await getSession();
  if (!session) {
    redirect("/login");
  }
  return session;
});

/** Like verifySession, but returns null instead of redirecting. */
export const getOptionalSession = cache(async (): Promise<Session | null> => {
  return getSession();
});
