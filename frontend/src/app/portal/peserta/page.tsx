import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Input, Select } from "@/components/ui/Field";
import { formatDate } from "@/lib/format";

export const metadata = { title: "Peserta" };

type Participant = {
  id: string;
  registration_no: string;
  full_name: string;
  birth_date: string | null;
  skill_level: string | null;
  status: string;
  created_at: string;
};

const STATUS_OPTIONS = [
  "pending_verification",
  "pending_payment",
  "active",
  "inactive",
  "rejected",
  "graduated",
  "suspended",
];

export default async function PesertaPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string; search?: string; status?: string }>;
}) {
  await verifySession();
  const { page = "1", search = "", status = "" } = await searchParams;

  const qs = new URLSearchParams({ page, per_page: "15" });
  if (search) qs.set("search", search);
  if (status) qs.set("status", status);

  const participants = await serverApiPaginated<Participant>(`/participants?${qs.toString()}`);

  const makeHref = (p: number) => {
    const next = new URLSearchParams(qs);
    next.set("page", String(p));
    return `/portal/peserta?${next.toString()}`;
  };

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Peserta</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola data dan verifikasi peserta latihan.</p>
        </div>
      </div>

      <form className="mt-6 flex flex-wrap gap-3">
        <div className="w-56">
          <Input name="search" placeholder="Cari nama / no. registrasi" defaultValue={search} />
        </div>
        <div className="w-48">
          <Select name="status" defaultValue={status}>
            <option value="">Semua status</option>
            {STATUS_OPTIONS.map((s) => (
              <option key={s} value={s}>
                {s.replace(/_/g, " ")}
              </option>
            ))}
          </Select>
        </div>
        <button type="submit" className="rounded-full bg-(--color-court-600) px-5 text-sm font-semibold text-white">
          Filter
        </button>
      </form>

      <Card className="mt-6">
        {participants.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Tidak ada data peserta" description="Coba ubah filter pencarian." />
          </div>
        ) : (
          <>
            <CardHeader title={`${participants.meta.total} Peserta`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>No. Registrasi</Th>
                    <Th>Nama</Th>
                    <Th>Tgl Lahir</Th>
                    <Th>Level</Th>
                    <Th>Status</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {participants.data.map((p) => (
                    <Tr key={p.id}>
                      <Td className="font-mono text-xs">{p.registration_no}</Td>
                      <Td className="font-semibold">{p.full_name}</Td>
                      <Td>{formatDate(p.birth_date)}</Td>
                      <Td className="capitalize">{p.skill_level ?? "-"}</Td>
                      <Td>
                        <StatusBadge status={p.status} />
                      </Td>
                      <Td>
                        <Link href={`/portal/peserta/${p.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Detail
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={participants.meta.current_page} lastPage={participants.meta.last_page} makeHref={makeHref} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
