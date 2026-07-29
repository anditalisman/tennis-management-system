import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { ProgramForm } from "@/components/portal/ProgramForm";

export const metadata = { title: "Edit Program" };

type Program = {
  id: number;
  name: string;
  age_group: string | null;
  skill_level: string | null;
  target_competency: string | null;
  description: string | null;
  status: string;
};

export default async function EditProgramPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const program = await serverApiOrNull<Program>(`/programs/${id}`);
  if (!program) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Program</h1>
      <Card className="mt-6">
        <CardHeader title={program.name} />
        <CardBody>
          <ProgramForm program={program} />
        </CardBody>
      </Card>
    </div>
  );
}
