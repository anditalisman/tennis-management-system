import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { ScheduleForm } from "@/components/portal/ScheduleForm";

export const metadata = { title: "Tambah Jadwal" };

export default async function NewSchedulePage() {
  await verifySession();

  const [classes, courts, coaches] = await Promise.all([
    serverApiPaginated<{ id: number; name: string }>("/classes?per_page=100"),
    serverApiPaginated<{ id: number; name: string }>("/courts?per_page=100"),
    serverApiPaginated<{ id: number; name: string }>("/coaches?per_page=100"),
  ]);

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Jadwal</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Sesi" />
        <CardBody>
          <ScheduleForm classes={classes.data} courts={courts.data} coaches={coaches.data} />
        </CardBody>
      </Card>
    </div>
  );
}
