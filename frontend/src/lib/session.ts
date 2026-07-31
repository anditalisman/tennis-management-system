import "server-only";
import { cookies, headers } from "next/headers";
import { SESSION_COOKIE_NAME } from "./constants";

export type SessionUser = {
  id: string;
  name: string;
  email: string;
  roles: string[];
  branch_id: number | null;
};

export type Session = {
  token: string;
  user: SessionUser;
};

const COOKIE_NAME = SESSION_COOKIE_NAME;
const MAX_AGE = 60 * 60 * 24; // 24 hours — matches Sanctum's token expiration in backend/config/sanctum.php

export async function createSession(session: Session): Promise<void> {
  const store = await cookies();
  const hdrs = await headers();
  // NODE_ENV is always "production" in the built image (local and deployed
  // alike), so it can't tell us whether *this particular request* is over
  // HTTPS. A `secure` cookie set while the browser is on plain HTTP (e.g.
  // testing via http://localhost:8088) is silently dropped by the browser —
  // login "succeeds" server-side but the session never actually sticks.
  // x-forwarded-proto is set by the reverse proxy (Traefik/nginx) in front
  // of real HTTPS deployments; absent locally, where HTTP is expected.
  const secure = hdrs.get("x-forwarded-proto") === "https";
  store.set(COOKIE_NAME, JSON.stringify(session), {
    httpOnly: true,
    secure,
    sameSite: "lax",
    path: "/",
    maxAge: MAX_AGE,
  });
}

export async function getSession(): Promise<Session | null> {
  const store = await cookies();
  const raw = store.get(COOKIE_NAME)?.value;
  if (!raw) return null;

  try {
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export async function deleteSession(): Promise<void> {
  const store = await cookies();
  store.delete(COOKIE_NAME);
}
