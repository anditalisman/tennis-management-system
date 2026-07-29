import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { EditParticipantForm } from "./EditParticipantForm";

export const metadata = { title: "Edit Peserta" };

type Participant = {
  id: string;
  full_name: string;
  birth_date: string | null;
  gender: string | null;
  skill_level: string | null;
  phone: string | null;
  address: string | null;
};

export default async function EditPesertaPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const participant = await serverApiOrNull<Participant>(`/participants/${id}`);
  if (!participant) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Data Peserta</h1>
      <Card className="mt-6">
        <CardHeader title={participant.full_name} />
        <CardBody>
          <EditParticipantForm participant={participant} />
        </CardBody>
      </Card>
    </div>
  );
}
