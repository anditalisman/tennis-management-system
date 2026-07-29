import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { CoachForm } from "@/components/portal/CoachForm";

export const metadata = { title: "Edit Pelatih" };

type Coach = {
  id: number;
  name: string | null;
  branch_id: number | null;
  bio: string | null;
  employment_status: string;
};

export default async function EditCoachPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const coach = await serverApiOrNull<Coach>(`/coaches/${id}`);
  if (!coach) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Pelatih</h1>
      <Card className="mt-6">
        <CardHeader title={coach.name ?? "Pelatih"} />
        <CardBody>
          <CoachForm coach={coach} />
        </CardBody>
      </Card>
    </div>
  );
}
