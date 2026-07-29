import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { ProgramForm } from "@/components/portal/ProgramForm";

export const metadata = { title: "Tambah Program" };

export default async function NewProgramPage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Program</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Program" />
        <CardBody>
          <ProgramForm />
        </CardBody>
      </Card>
    </div>
  );
}
