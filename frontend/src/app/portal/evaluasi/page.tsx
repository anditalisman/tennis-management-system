import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { EmptyState } from "@/components/ui/Feedback";
import { Pagination } from "@/components/ui/Pagination";
import { EvaluationForm } from "@/components/portal/EvaluationForm";
import { ASPECT_LABELS } from "@/lib/evaluation-aspects";
import { formatDate } from "@/lib/format";

export const metadata = { title: "Evaluasi" };

type EvaluationDetail = { aspect: string; score: number; note: string | null };
type Evaluation = {
  id: number;
  coach_name: string | null;
  evaluation_date: string | null;
  next_target: string | null;
  details: EvaluationDetail[];
};

export default async function EvaluasiPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const canCreate = hasRole(session.user.roles, ROLES.COACH, ROLES.SUPER_ADMIN, ROLES.MANAGEMENT, ROLES.ADMINISTRATOR);

  if (!canCreate) {
    const { page = "1" } = await searchParams;
    const evaluations = await serverApiPaginated<Evaluation>(`/evaluations?page=${page}&per_page=20`);

    return (
      <div className="max-w-2xl">
        <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Evaluasi</h1>
        <p className="mt-1 text-sm text-(--color-ink-500)">Hasil evaluasi yang dikeluarkan pelatih setelah sesi latihan.</p>
        <Card className="mt-6">
          {evaluations.data.length === 0 ? (
            <CardBody>
              <EmptyState title="Belum ada evaluasi" />
            </CardBody>
          ) : (
            <>
              <CardHeader title={`${evaluations.meta.total} Evaluasi`} />
              <CardBody>
                <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
                  {evaluations.data.map((ev) => (
                    <li key={ev.id} className="py-4 first:pt-0 last:pb-0">
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm font-semibold text-(--color-ink-900)">{formatDate(ev.evaluation_date)}</p>
                        <p className="text-xs text-(--color-ink-500)">Pelatih: {ev.coach_name ?? "-"}</p>
                      </div>
                      <div className="mt-2 flex flex-wrap gap-1.5">
                        {ev.details.map((d) => (
                          <span
                            key={d.aspect}
                            className="rounded-full bg-(--color-ink-900)/6 px-2.5 py-1 text-xs font-medium text-(--color-ink-700)"
                          >
                            {ASPECT_LABELS[d.aspect] ?? d.aspect}: {d.score}/5
                          </span>
                        ))}
                      </div>
                      {ev.next_target && <p className="mt-2 text-sm text-(--color-ink-500)">Target berikutnya: {ev.next_target}</p>}
                    </li>
                  ))}
                </ul>
                <Pagination
                  currentPage={evaluations.meta.current_page}
                  lastPage={evaluations.meta.last_page}
                  makeHref={(p) => `/portal/evaluasi?page=${p}`}
                />
              </CardBody>
            </>
          )}
        </Card>
      </div>
    );
  }

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
