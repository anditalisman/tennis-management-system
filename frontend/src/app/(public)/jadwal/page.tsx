import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { EmptyState } from "@/components/ui/Feedback";
import { formatDate, formatTime } from "@/lib/format";

export const metadata = { title: "Jadwal Latihan" };

type Schedule = {
  id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  class_name: string | null;
  program_name: string | null;
  branch_name: string | null;
  coach_name: string | null;
  court_name: string | null;
};

export default async function JadwalPage() {
  const schedules = await serverApi<Schedule[]>("/public/schedules?days=14");

  const grouped = schedules.reduce<Record<string, Schedule[]>>((acc, item) => {
    acc[item.session_date] = acc[item.session_date] ? [...acc[item.session_date], item] : [item];
    return acc;
  }, {});

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Jadwal</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Jadwal Latihan 14 Hari ke Depan
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Jadwal berikut adalah sesi kelas reguler yang terjadwal. Untuk bergabung, silakan daftar terlebih dahulu.
      </p>

      {Object.keys(grouped).length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada jadwal dalam rentang ini" description="Silakan cek kembali dalam beberapa hari." />
        </div>
      ) : (
        <div className="mt-10 flex flex-col gap-8">
          {Object.entries(grouped).map(([date, items]) => (
            <div key={date}>
              <h2 className="font-display text-lg font-bold text-(--color-ink-900)">
                {formatDate(date, { weekday: "long" })}
              </h2>
              <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                {items.map((item) => (
                  <Card key={item.id}>
                    <CardBody className="flex items-start justify-between gap-4">
                      <div>
                        <p className="font-semibold text-(--color-ink-900)">{item.class_name}</p>
                        <p className="mt-1 text-sm text-(--color-ink-500)">
                          {item.program_name} · {item.branch_name}
                        </p>
                        <p className="mt-1 text-sm text-(--color-ink-500)">
                          Pelatih: {item.coach_name ?? "-"} · {item.court_name ?? "-"}
                        </p>
                      </div>
                      <p className="shrink-0 text-sm font-semibold text-(--color-court-700)">
                        {formatTime(item.start_time)}–{formatTime(item.end_time)}
                      </p>
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
