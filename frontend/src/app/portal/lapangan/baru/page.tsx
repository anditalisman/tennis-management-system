import { verifySession } from "@/lib/dal";
import { getDefaultBranchId } from "@/lib/branch";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { CourtForm } from "@/components/portal/CourtForm";

export const metadata = { title: "Tambah Lapangan" };

export default async function NewCourtPage() {
  await verifySession();
  const defaultBranchId = await getDefaultBranchId();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Lapangan</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Lapangan" />
        <CardBody>
          <CourtForm defaultBranchId={defaultBranchId} />
        </CardBody>
      </Card>
    </div>
  );
}
