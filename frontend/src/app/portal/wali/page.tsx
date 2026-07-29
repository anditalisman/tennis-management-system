import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { EmptyState } from "@/components/ui/Feedback";
import { Input } from "@/components/ui/Field";

export const metadata = { title: "Wali & Anak" };

type Guardian = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  relation: string | null;
  participant_count: number;
};

export default async function WaliPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string; search?: string }>;
}) {
  await verifySession();
  const { page = "1", search = "" } = await searchParams;

  const qs = new URLSearchParams({ page, per_page: "15" });
  if (search) qs.set("search", search);

  const guardians = await serverApiPaginated<Guardian>(`/guardians?${qs.toString()}`);

  const makeHref = (p: number) => {
    const next = new URLSearchParams(qs);
    next.set("page", String(p));
    return `/portal/wali?${next.toString()}`;
  };

  return (
    <div>
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Wali & Anak</h1>
      <p className="mt-1 text-sm text-(--color-ink-500)">Daftar akun wali dan jumlah anak yang dipantau.</p>

      <form className="mt-6 flex flex-wrap gap-3">
        <div className="w-64">
          <Input name="search" placeholder="Cari nama / email wali" defaultValue={search} />
        </div>
        <button type="submit" className="rounded-full bg-(--color-court-600) px-5 text-sm font-semibold text-white">
          Cari
        </button>
      </form>

      <Card className="mt-6">
        {guardians.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Tidak ada data wali" />
          </div>
        ) : (
          <>
            <CardHeader title={`${guardians.meta.total} Wali`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Email</Th>
                    <Th>Telepon</Th>
                    <Th>Hubungan</Th>
                    <Th>Jumlah Anak</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {guardians.data.map((g) => (
                    <Tr key={g.id}>
                      <Td className="font-semibold">{g.name}</Td>
                      <Td>{g.email}</Td>
                      <Td>{g.phone ?? "-"}</Td>
                      <Td>{g.relation ?? "-"}</Td>
                      <Td>{g.participant_count}</Td>
                      <Td>
                        <Link href={`/portal/wali/${g.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Lihat Anak
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={guardians.meta.current_page} lastPage={guardians.meta.last_page} makeHref={makeHref} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
