import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { EvaluationForm } from "@/components/portal/EvaluationForm";

export const metadata = { title: "Evaluasi" };

export default async function EvaluasiPage() {
  const session = await verifySession();
  const isCoach = hasRole(session.user.roles, ROLES.COACH);

  const [classes, coaches] = await Promise.all([
    serverApiPaginated<{ id: number; name: string }>("/classes?per_page=100"),
    isCoach ? Promise.resolve(null) : serverApiPaginated<{ id: number; name: string }>("/coaches?per_page=100"),
  ]);

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Evaluasi Peserta</h1>
      <p className="mt-1 text-sm text-(--color-ink-500)">Catat perkembangan teknik dan non-teknik peserta setelah sesi latihan.</p>
      <Card className="mt-6">
        <CardHeader title="Formulir Evaluasi" />
        <CardBody>
          <EvaluationForm classes={classes.data} coaches={coaches?.data ?? null} />
        </CardBody>
      </Card>
    </div>
  );
}
