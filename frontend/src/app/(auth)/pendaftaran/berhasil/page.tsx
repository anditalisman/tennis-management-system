import Link from "next/link";
import { Button } from "@/components/ui/Button";

export const metadata = { title: "Pendaftaran Berhasil" };

export default async function RegistrationSuccessPage({
  searchParams,
}: {
  searchParams: Promise<{ no?: string }>;
}) {
  const { no } = await searchParams;

  return (
    <div className="text-center">
      <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-(--color-good)/12 text-(--color-good)">
        ✓
      </div>
      <h1 className="mt-4 font-display text-2xl font-bold text-(--color-ink-900)">Pendaftaran Berhasil</h1>
      <p className="mt-2 text-sm text-(--color-ink-500)">
        Nomor registrasi peserta Anda: <span className="font-semibold text-(--color-ink-900)">{no ?? "-"}</span>
      </p>
      <p className="mt-3 text-sm text-(--color-ink-500)">
        Tim kami akan meninjau pendaftaran ini. Anda akan menerima notifikasi setelah pendaftaran diverifikasi.
      </p>
      <p className="mt-3 text-sm text-(--color-ink-500)">
        Kami juga mengirim email verifikasi ke akun login Anda — klik link di email tersebut dulu sebelum bisa masuk
        ke portal.
      </p>
      <Link href="/login" className="mt-6 inline-block">
        <Button>Masuk ke Portal</Button>
      </Link>
    </div>
  );
}
