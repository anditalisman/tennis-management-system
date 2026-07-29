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

export const metadata = { title: "Tagihan" };

type Invoice = {
  id: number;
  invoice_no: string;
  due_date: string | null;
  status: string;
  total_amount: number;
  amount_paid: number;
};

export default async function TagihanListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const canCreate = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.ADMINISTRATOR, ROLES.FINANCE);

  const invoices = await serverApiPaginated<Invoice>(`/invoices?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tagihan</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Daftar tagihan dan status pembayaran.</p>
        </div>
        {canCreate && (
          <Link href="/portal/tagihan/baru">
            <Button>Buat Tagihan</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {invoices.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada tagihan" />
          </div>
        ) : (
          <>
            <CardHeader title={`${invoices.meta.total} Tagihan`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>No. Invoice</Th>
                    <Th>Jatuh Tempo</Th>
                    <Th>Total</Th>
                    <Th>Dibayar</Th>
                    <Th>Status</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {invoices.data.map((inv) => (
                    <Tr key={inv.id}>
                      <Td className="font-mono text-xs">{inv.invoice_no}</Td>
                      <Td>{formatDate(inv.due_date)}</Td>
                      <Td>{formatCurrency(inv.total_amount)}</Td>
                      <Td>{formatCurrency(inv.amount_paid)}</Td>
                      <Td>
                        <StatusBadge status={inv.status} />
                      </Td>
                      <Td>
                        <Link href={`/portal/tagihan/${inv.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Detail
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={invoices.meta.current_page} lastPage={invoices.meta.last_page} makeHref={(p) => `/portal/tagihan?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
