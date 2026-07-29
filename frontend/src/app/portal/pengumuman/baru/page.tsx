import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PengumumanForm } from "./PengumumanForm";

export const metadata = { title: "Buat Pengumuman" };

export default async function NewAnnouncementPage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Buat Pengumuman</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Pengumuman" />
        <CardBody>
          <PengumumanForm />
        </CardBody>
      </Card>
    </div>
  );
}
