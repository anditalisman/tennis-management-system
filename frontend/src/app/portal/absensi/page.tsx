import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { formatDate, formatTime } from "@/lib/format";

export const metadata = { title: "Absensi" };

type Schedule = {
  id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  status: string;
};

export default async function AbsensiPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  await verifySession();
  const { page = "1" } = await searchParams;

  const today = new Date().toISOString().slice(0, 10);
  const schedules = await serverApiPaginated<Schedule>(`/schedules?to=${today}&page=${page}&per_page=20`);

  return (
    <div>
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Absensi</h1>
      <p className="mt-1 text-sm text-(--color-ink-500)">Pilih sesi untuk mencatat atau memverifikasi kehadiran peserta.</p>

      <Card className="mt-6">
        {schedules.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada sesi yang lewat untuk diverifikasi" />
          </div>
        ) : (
          <>
            <CardHeader title={`${schedules.meta.total} Sesi`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Tanggal</Th>
                    <Th>Waktu</Th>
                    <Th>Status Sesi</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {schedules.data.map((s) => (
                    <Tr key={s.id}>
                      <Td>{formatDate(s.session_date, { weekday: "short" })}</Td>
                      <Td>
                        {formatTime(s.start_time)}–{formatTime(s.end_time)}
                      </Td>
                      <Td>
                        <StatusBadge status={s.status} />
                      </Td>
                      <Td>
                        <Link href={`/portal/jadwal/${s.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Kelola Absensi
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination
                currentPage={schedules.meta.current_page}
                lastPage={schedules.meta.last_page}
                makeHref={(p) => `/portal/absensi?page=${p}`}
              />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
