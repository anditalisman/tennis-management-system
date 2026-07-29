import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated, serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { ClassForm } from "@/components/portal/ClassForm";

export const metadata = { title: "Edit Kelas" };

type TrainingClass = {
  id: number;
  program_id: number;
  branch_id: number;
  coach_id: number | null;
  court_id: number | null;
  name: string;
  capacity_min: number;
  capacity_max: number;
  session_duration: number;
  status: string;
};

export default async function EditClassPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const trainingClass = await serverApiOrNull<TrainingClass>(`/classes/${id}`);
  if (!trainingClass) notFound();

  const [programs, coaches, courts] = await Promise.all([
    serverApiPaginated<{ id: number; name: string }>("/programs?per_page=100"),
    serverApiPaginated<{ id: number; name: string }>("/coaches?per_page=100"),
    serverApiPaginated<{ id: number; name: string }>("/courts?per_page=100"),
  ]);

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Kelas</h1>
      <Card className="mt-6">
        <CardHeader title={trainingClass.name} />
        <CardBody>
          <ClassForm trainingClass={trainingClass} programs={programs.data} coaches={coaches.data} courts={courts.data} />
        </CardBody>
      </Card>
    </div>
  );
}
