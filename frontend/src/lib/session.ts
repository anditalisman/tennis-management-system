import "server-only";
import { cookies } from "next/headers";
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
const MAX_AGE = 60 * 60 * 24 * 7; // 7 days — matches Sanctum tokens having no fixed expiry by default

export async function createSession(session: Session): Promise<void> {
  const store = await cookies();
  store.set(COOKIE_NAME, JSON.stringify(session), {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
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
