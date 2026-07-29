import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";

export const metadata = { title: "Pelatih" };

type Coach = {
  id: number;
  name: string | null;
  branch_id: number;
  branch_name: string | null;
  certifications: string[] | null;
  bio: string | null;
};

export default async function PelatihPage() {
  const coaches = await serverApi<Coach[]>("/public/coaches");

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Pelatih</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Tim Pelatih Kami
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Dibimbing oleh pelatih berpengalaman dan bersertifikat, siap mendampingi perkembangan pemain di setiap level.
      </p>

      {coaches.length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada data pelatih" />
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {coaches.map((coach) => (
            <Card key={coach.id}>
              <CardBody>
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-(--color-court-600)/10 font-display text-lg font-bold text-(--color-court-700)">
                  {coach.name?.charAt(0) ?? "?"}
                </div>
                <h3 className="mt-3 font-display text-base font-bold text-(--color-ink-900)">{coach.name}</h3>
                <p className="text-sm text-(--color-ink-500)">{coach.branch_name}</p>
                {coach.bio && <p className="mt-2 text-sm text-(--color-ink-700)">{coach.bio}</p>}
                {coach.certifications && coach.certifications.length > 0 && (
                  <div className="mt-3 flex flex-wrap gap-1.5">
                    {coach.certifications.map((cert) => (
                      <Badge key={cert}>{cert}</Badge>
                    ))}
                  </div>
                )}
              </CardBody>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
