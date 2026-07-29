import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";

export const metadata = { title: "Lapangan & Lokasi" };

type Court = {
  id: number;
  name: string;
  surface_type: string | null;
  operating_hours: string[] | null;
  branch_id: number;
  branch_name: string | null;
  branch_address: string | null;
};

const SURFACE_LABELS: Record<string, string> = { hard: "Hard Court", clay: "Clay Court", grass: "Grass Court" };

export default async function LapanganPage() {
  const courts = await serverApi<Court[]>("/public/courts");

  const grouped = courts.reduce<Record<string, Court[]>>((acc, court) => {
    const key = court.branch_name ?? "Lainnya";
    acc[key] = acc[key] ? [...acc[key], court] : [court];
    return acc;
  }, {});

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Lapangan & Lokasi</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Fasilitas Lapangan Kami
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Seluruh lapangan dirawat secara berkala untuk menjaga kualitas permukaan dan keselamatan latihan.
      </p>

      {courts.length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada data lapangan" />
        </div>
      ) : (
        <div className="mt-10 flex flex-col gap-10">
          {Object.entries(grouped).map(([branchName, items]) => (
            <div key={branchName}>
              <h2 className="font-display text-xl font-bold text-(--color-ink-900)">{branchName}</h2>
              {items[0]?.branch_address && <p className="mt-1 text-sm text-(--color-ink-500)">{items[0].branch_address}</p>}
              <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {items.map((court) => (
                  <Card key={court.id}>
                    <CardBody>
                      <h3 className="font-display text-base font-bold text-(--color-ink-900)">{court.name}</h3>
                      {court.surface_type && (
                        <div className="mt-2">
                          <Badge tone="court">{SURFACE_LABELS[court.surface_type] ?? court.surface_type}</Badge>
                        </div>
                      )}
                      {court.operating_hours && court.operating_hours.length === 2 && (
                        <p className="mt-2 text-sm text-(--color-ink-500)">
                          Jam operasional: {court.operating_hours[0]}–{court.operating_hours[1]}
                        </p>
                      )}
                    </CardBody>
                  </Card>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
