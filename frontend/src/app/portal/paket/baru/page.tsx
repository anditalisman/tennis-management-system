import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PackageForm } from "@/components/portal/PackageForm";

export const metadata = { title: "Tambah Paket" };

export default async function NewPackagePage() {
  await verifySession();

  const programs = await serverApiPaginated<{ id: number; name: string }>("/programs?per_page=100");

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Paket</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Paket" />
        <CardBody>
          <PackageForm programs={programs.data} />
        </CardBody>
      </Card>
    </div>
  );
}
