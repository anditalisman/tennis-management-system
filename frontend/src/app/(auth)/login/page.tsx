import { LoginForm } from "./LoginForm";

export const metadata = { title: "Masuk" };

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ next?: string }>;
}) {
  const { next } = await searchParams;

  return (
    <>
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Masuk ke Portal</h1>
      <p className="mt-1 mb-6 text-sm text-(--color-ink-500)">
        Untuk peserta, orang tua/wali, pelatih, dan staf Zul Tennis Clinic.
      </p>
      <LoginForm next={next} />
    </>
  );
}
