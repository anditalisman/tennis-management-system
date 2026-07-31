import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PackageForm } from "@/components/portal/PackageForm";

export const metadata = { title: "Tambah Paket" };

export default async function NewPackagePage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Paket</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Paket" />
        <CardBody>
          <PackageForm />
        </CardBody>
      </Card>
    </div>
  );
}
