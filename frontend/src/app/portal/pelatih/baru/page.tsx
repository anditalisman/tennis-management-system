import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { CoachForm } from "@/components/portal/CoachForm";

export const metadata = { title: "Tambah Pelatih" };

export default async function NewCoachPage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Pelatih</h1>
      <Card className="mt-6">
        <CardHeader title="Akun & Profil Pelatih" />
        <CardBody>
          <CoachForm />
        </CardBody>
      </Card>
    </div>
  );
}
