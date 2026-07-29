import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApi, serverApiOrNull, serverApiPaginated } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { StatTile, EmptyState } from "@/components/ui/Feedback";
import { formatCurrency } from "@/lib/format";

export const metadata = { title: "Laporan" };

type AttendanceReport = {
  by_status: Record<string, number>;
  by_class: { class_id: number; class_name: string; by_status: Record<string, number> }[];
};

type RevenueReport = {
  total: number;
  by_period: Record<string, number>;
  by_branch: Record<string, number>;
};

type Filters = { type?: string; from?: string; to?: string; program_id?: string; class_id?: string };

export default async function LaporanPage({ searchParams }: { searchParams: Promise<Filters> }) {
  await verifySession();
  const filters = await searchParams;
  const type = filters.type === "revenue" ? "revenue" : "attendance";

  const query = new URLSearchParams();
  if (filters.from) query.set("from", filters.from);
  if (filters.to) query.set("to", filters.to);
  if (type === "attendance") {
    if (filters.program_id) query.set("program_id", filters.program_id);
    if (filters.class_id) query.set("class_id", filters.class_id);
  }
  const queryString = query.toString();

  const [programs, classes] = await Promise.all([
    type === "attendance" ? serverApiPaginated<{ id: number; name: string }>("/programs?per_page=100") : null,
    type === "attendance" ? serverApiPaginated<{ id: number; name: string }>("/classes?per_page=100") : null,
  ]);

  const attendance = type === "attendance" ? await serverApi<AttendanceReport>(`/reports/attendance${queryString ? `?${queryString}` : ""}`) : null;
  const revenue = type === "revenue" ? await serverApiOrNull<RevenueReport>(`/reports/revenue${queryString ? `?${queryString}` : ""}`) : null;

  const exportHref = `/api/backend/reports/${type}/export?format=csv${queryString ? `&${queryString}` : ""}`;

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Laporan</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Ringkasan absensi dan pendapatan.</p>
        </div>
        <a href={exportHref}>
          <Button variant="outline">Export CSV</Button>
        </a>
      </div>

      <div className="mt-4 flex gap-2">
        <Link href="/portal/laporan?type=attendance">
          <Button variant={type === "attendance" ? "primary" : "ghost"} size="sm">
            Absensi
          </Button>
        </Link>
        <Link href="/portal/laporan?type=revenue">
          <Button variant={type === "revenue" ? "primary" : "ghost"} size="sm">
            Pendapatan
          </Button>
        </Link>
      </div>

      <Card className="mt-4">
        <CardBody>
          <form className="flex flex-wrap items-end gap-3" method="get">
            <input type="hidden" name="type" value={type} />
            <Input label="Dari tanggal" name="from" type="date" defaultValue={filters.from} />
            <Input label="Sampai tanggal" name="to" type="date" defaultValue={filters.to} />
            {type === "attendance" && (
              <>
                <Select label="Program" name="program_id" defaultValue={filters.program_id ?? ""} className="min-w-[10rem]">
                  <option value="">Semua program</option>
                  {(programs?.data ?? []).map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name}
                    </option>
                  ))}
                </Select>
                <Select label="Kelas" name="class_id" defaultValue={filters.class_id ?? ""} className="min-w-[10rem]">
                  <option value="">Semua kelas</option>
                  {(classes?.data ?? []).map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </Select>
              </>
            )}
            <Button type="submit" variant="secondary">
              Terapkan
            </Button>
          </form>
        </CardBody>
      </Card>

      {type === "attendance" && attendance && (
        <>
          <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            {Object.entries(attendance.by_status).map(([status, total]) => (
              <StatTile key={status} label={status.replace(/_/g, " ")} value={total} />
            ))}
            {Object.keys(attendance.by_status).length === 0 && (
              <div className="col-span-full">
                <EmptyState title="Belum ada data absensi" />
              </div>
            )}
          </div>

          {attendance.by_class.length > 0 && (
            <Card className="mt-6">
              <CardHeader title="Per Kelas" />
              <div className="p-4">
                <Table>
                  <Thead>
                    <Tr>
                      <Th>Kelas</Th>
                      <Th>Rincian</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {attendance.by_class.map((row) => (
                      <Tr key={row.class_id}>
                        <Td className="font-semibold">{row.class_name}</Td>
                        <Td>
                          {Object.entries(row.by_status)
                            .map(([status, total]) => `${status.replace(/_/g, " ")}: ${total}`)
                            .join(" · ")}
                        </Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              </div>
            </Card>
          )}
        </>
      )}

      {type === "revenue" && revenue && (
        <>
          <div className="mt-6">
            <StatTile label="Total Pendapatan Terverifikasi" value={formatCurrency(revenue.total)} />
          </div>

          <Card className="mt-6">
            <CardHeader title="Per Periode" />
            <div className="p-4">
              {Object.keys(revenue.by_period).length === 0 ? (
                <EmptyState title="Belum ada data pendapatan" />
              ) : (
                <Table>
                  <Thead>
                    <Tr>
                      <Th>Periode</Th>
                      <Th>Total</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {Object.entries(revenue.by_period).map(([period, total]) => (
                      <Tr key={period}>
                        <Td>{period}</Td>
                        <Td>{formatCurrency(total)}</Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              )}
            </div>
          </Card>
        </>
      )}

      {type === "revenue" && !revenue && (
        <div className="mt-6">
          <EmptyState title="Anda tidak memiliki akses ke laporan pendapatan" />
        </div>
      )}
    </div>
  );
}
