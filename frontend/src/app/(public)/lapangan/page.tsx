import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { PageHeader } from "../PageHeader";

export const metadata = { title: "Lapangan & Lokasi" };

type Court = {
  id: number;
  name: string;
  surface_type: string | null;
  operating_hours: string[] | null;
  branch_address: string | null;
};

const SURFACE_LABELS: Record<string, string> = { hard: "Hard Court", clay: "Clay Court", grass: "Grass Court" };

export default async function LapanganPage() {
  const courts = await serverApi<Court[]>("/public/courts");
  const address = courts.find((c) => c.branch_address)?.branch_address;

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <PageHeader
        eyebrow="Lapangan & Lokasi"
        title="Fasilitas Lapangan Kami"
        description="Seluruh lapangan dirawat secara berkala untuk menjaga kualitas permukaan dan keselamatan latihan."
      />
      {address && <p className="mt-2 text-sm font-medium text-(--color-ink-500)">📍 {address}</p>}

      {courts.length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada data lapangan" />
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {courts.map((court) => (
            <Card key={court.id} className="transition-shadow hover:shadow-md">
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
      )}
    </div>
  );
}
