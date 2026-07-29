import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { CourtForm } from "@/components/portal/CourtForm";

export const metadata = { title: "Edit Lapangan" };

type Court = {
  id: number;
  branch_id: number;
  name: string;
  surface_type: string | null;
  rental_cost: number | null;
  status: string;
};

export default async function EditCourtPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const court = await serverApiOrNull<Court>(`/courts/${id}`);
  if (!court) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Lapangan</h1>
      <Card className="mt-6">
        <CardHeader title={court.name} />
        <CardBody>
          <CourtForm court={court} />
        </CardBody>
      </Card>
    </div>
  );
}
