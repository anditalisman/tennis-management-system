import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { isStaff } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatDate, formatTime } from "@/lib/format";

export const metadata = { title: "Jadwal" };

type Schedule = {
  id: number;
  class_id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  type: string;
  status: string;
};

export default async function JadwalListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const staff = isStaff(session.user.roles);

  const schedules = await serverApiPaginated<Schedule>(`/schedules?page=${page}&per_page=20`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Jadwal</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kalender sesi latihan.</p>
        </div>
        {staff && (
          <Link href="/portal/jadwal/baru">
            <Button>Tambah Jadwal</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {schedules.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada jadwal" />
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
                    <Th>Tipe</Th>
                    <Th>Status</Th>
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
                      <Td className="capitalize">{s.type}</Td>
                      <Td>
                        <StatusBadge status={s.status} />
                      </Td>
                      <Td>
                        <Link href={`/portal/jadwal/${s.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Detail
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination
                currentPage={schedules.meta.current_page}
                lastPage={schedules.meta.last_page}
                makeHref={(p) => `/portal/jadwal?page=${p}`}
              />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
