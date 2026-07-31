import Link from "next/link";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { Button } from "@/components/ui/Button";

export const metadata = { title: "Verifikasi Email" };

export default async function VerifyEmailPage({
  searchParams,
}: {
  searchParams: Promise<{ id?: string; expires?: string; signature?: string }>;
}) {
  const { id, expires, signature } = await searchParams;

  let success = false;
  let message = "Link verifikasi tidak lengkap. Buka link dari email Anda apa adanya.";

  if (id && expires && signature) {
    try {
      const result = await serverApi<{ message: string }>("/auth/verify-email", {
        method: "POST",
        body: { id: Number(id), expires: Number(expires), signature },
      });
      success = true;
      message = result.message;
    } catch (error) {
      message = error instanceof ApiError ? error.message : "Tidak dapat terhubung ke server. Coba lagi.";
    }
  }

  return (
    <div className="text-center">
      <div
        className={`mx-auto flex h-14 w-14 items-center justify-center rounded-full ${
          success ? "bg-(--color-good)/12 text-(--color-good)" : "bg-(--color-crit)/12 text-(--color-crit)"
        }`}
      >
        {success ? "✓" : "✕"}
      </div>
      <h1 className="mt-4 font-display text-2xl font-bold text-(--color-ink-900)">
        {success ? "Email Terverifikasi" : "Verifikasi Gagal"}
      </h1>
      <p className="mt-3 text-sm text-(--color-ink-500)">{message}</p>
      <Link href="/login" className="mt-6 inline-block">
        <Button>Masuk ke Portal</Button>
      </Link>
    </div>
  );
}
