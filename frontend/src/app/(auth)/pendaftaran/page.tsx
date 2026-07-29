import { getDefaultBranchId } from "@/lib/branch";
import { RegistrationWizard } from "./RegistrationWizard";

export const metadata = { title: "Daftar Peserta" };

export default async function RegistrationPage() {
  const defaultBranchId = await getDefaultBranchId();

  return (
    <>
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Formulir Pendaftaran</h1>
      <p className="mt-1 mb-6 text-sm text-(--color-ink-500)">
        Daftarkan putra/putri Anda sebagai peserta latihan di Zul Tennis Clinic.
      </p>
      <RegistrationWizard defaultBranchId={defaultBranchId} />
    </>
  );
}
