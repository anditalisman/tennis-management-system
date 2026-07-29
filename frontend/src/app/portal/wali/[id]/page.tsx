import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { formatDate } from "@/lib/format";

export const metadata = { title: "Anak yang Dipantau" };

type Participant = {
  id: string;
  registration_no: string;
  full_name: string;
  birth_date: string | null;
  status: string;
};

export default async function WaliDetailPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const participants = (await serverApiOrNull<Participant[]>(`/guardians/${id}/participants`)) ?? [];

  return (
    <div className="max-w-4xl">
      <Link href="/portal/wali" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke daftar wali
      </Link>
      <h1 className="mt-4 font-display text-2xl font-bold text-(--color-ink-900)">Anak yang Dipantau</h1>

      <Card className="mt-6">
        {participants.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada anak terhubung" />
          </div>
        ) : (
          <>
            <CardHeader title={`${participants.length} Peserta`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>No. Registrasi</Th>
                    <Th>Nama</Th>
                    <Th>Tgl Lahir</Th>
                    <Th>Status</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {participants.map((p) => (
                    <Tr key={p.id}>
                      <Td className="font-mono text-xs">{p.registration_no}</Td>
                      <Td className="font-semibold">{p.full_name}</Td>
                      <Td>{formatDate(p.birth_date)}</Td>
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
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
