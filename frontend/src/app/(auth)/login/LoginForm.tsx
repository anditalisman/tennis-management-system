"use client";

import { useActionState } from "react";
import Link from "next/link";
import { loginAction } from "@/lib/actions/auth";
import { Input } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function LoginForm({ next }: { next?: string }) {
  const [state, formAction, pending] = useActionState(loginAction, undefined);

  return (
    <form action={formAction} className="flex flex-col gap-4">
      <input type="hidden" name="next" value={next ?? ""} />
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Email" name="email" type="email" autoComplete="email" required error={state?.fieldErrors?.email} />
      <Input
        label="Kata sandi"
        name="password"
        type="password"
        autoComplete="current-password"
        required
        error={state?.fieldErrors?.password}
      />
      <Button type="submit" loading={pending} className="mt-2 w-full">
        Masuk
      </Button>
      <p className="text-center text-sm text-(--color-ink-500)">
        Belum punya akun?{" "}
        <Link href="/pendaftaran" className="font-semibold text-(--color-court-600) hover:underline">
          Daftar sekarang
        </Link>
      </p>
    </form>
  );
}
