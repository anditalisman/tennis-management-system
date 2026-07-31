import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated, serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PackageForm } from "@/components/portal/PackageForm";

export const metadata = { title: "Edit Paket" };

type Package = {
  id: number;
  program_id: number;
  name: string;
  session_count: number;
  validity_days: number;
  price: number;
  type: string;
  status?: string;
};

export default async function EditPackagePage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const pkg = await serverApiOrNull<Package>(`/packages/${id}`);
  if (!pkg) notFound();

  const programs = await serverApiPaginated<{ id: number; name: string }>("/programs?per_page=100");

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Paket</h1>
      <Card className="mt-6">
        <CardHeader title={pkg.name} />
        <CardBody>
          <PackageForm pkg={pkg} programs={programs.data} />
        </CardBody>
      </Card>
    </div>
  );
}
