import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatCurrency, formatDate } from "@/lib/format";

export const metadata = { title: "Voucher" };

type Voucher = {
  id: number;
  code: string;
  discount_type: string;
  discount_value: number;
  usage_limit: number | null;
  used_count: number;
  valid_from: string | null;
  valid_until: string | null;
  status: string;
};

export default async function VoucherListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const canCreate = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.FINANCE);

  const vouchers = await serverApiPaginated<Voucher>(`/vouchers?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Voucher</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kode diskon untuk pembuatan tagihan.</p>
        </div>
        {canCreate && (
          <Link href="/portal/voucher/baru">
            <Button>Buat Voucher</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {vouchers.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada voucher" />
          </div>
        ) : (
          <>
            <CardHeader title={`${vouchers.meta.total} Voucher`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Kode</Th>
                    <Th>Diskon</Th>
                    <Th>Pemakaian</Th>
                    <Th>Berlaku</Th>
                    <Th>Status</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {vouchers.data.map((v) => (
                    <Tr key={v.id}>
                      <Td className="font-mono font-semibold">{v.code}</Td>
                      <Td>{v.discount_type === "percentage" ? `${v.discount_value}%` : formatCurrency(v.discount_value)}</Td>
                      <Td>
                        {v.used_count}
                        {v.usage_limit ? ` / ${v.usage_limit}` : ""}
                      </Td>
                      <Td>
                        {v.valid_from || v.valid_until
                          ? `${formatDate(v.valid_from)} – ${formatDate(v.valid_until)}`
                          : "Tanpa batas"}
                      </Td>
                      <Td>
                        <StatusBadge status={v.status} />
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={vouchers.meta.current_page} lastPage={vouchers.meta.last_page} makeHref={(p) => `/portal/voucher?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
