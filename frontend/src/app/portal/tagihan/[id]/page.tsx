import Link from "next/link";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { StatusBadge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { PaymentForm } from "@/components/portal/PaymentForm";
import { formatCurrency, formatDate, formatDateTime } from "@/lib/format";
import { verifyPaymentAction } from "@/lib/actions/invoices";

export const metadata = { title: "Detail Tagihan" };

type InvoiceItem = { id: number; item_type: string; description: string | null; qty: number; unit_price: number; subtotal: number };
type Invoice = {
  id: number;
  invoice_no: string;
  due_date: string | null;
  status: string;
  subtotal_amount: number;
  discount_amount: number;
  tax_amount: number;
  total_amount: number;
  amount_paid: number;
  items: InvoiceItem[];
  created_at: string;
};

type Payment = {
  id: number;
  method: string;
  amount: number;
  status: string;
  reference_no: string | null;
  proof_url: string | null;
  verified_at: string | null;
  created_at: string;
};

const PAYABLE_STATUSES = ["unpaid", "partially_paid", "overdue"];

export default async function TagihanDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { id } = await params;
  const { error } = await searchParams;
  const invoiceId = Number(id);

  const invoice = await serverApiOrNull<Invoice>(`/invoices/${invoiceId}`);
  if (!invoice) notFound();

  const payments = (await serverApiOrNull<Payment[]>(`/invoices/${invoiceId}/payments`)) ?? [];

  const canVerify = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.FINANCE);
  const canPay = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.PARTICIPANT, ROLES.GUARDIAN) && PAYABLE_STATUSES.includes(invoice.status);
  const outstanding = invoice.total_amount - invoice.amount_paid;

  return (
    <div className="max-w-3xl">
      <Link href="/portal/tagihan" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke daftar tagihan
      </Link>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">{invoice.invoice_no}</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Jatuh tempo {formatDate(invoice.due_date)}</p>
        </div>
        <StatusBadge status={invoice.status} />
      </div>

      <Card className="mt-6">
        <CardHeader title="Rincian Tagihan" />
        <CardBody className="p-0">
          <Table>
            <Thead>
              <Tr>
                <Th>Deskripsi</Th>
                <Th>Qty</Th>
                <Th>Harga</Th>
                <Th>Subtotal</Th>
              </Tr>
            </Thead>
            <Tbody>
              {invoice.items.map((item) => (
                <Tr key={item.id}>
                  <Td>{item.description ?? item.item_type}</Td>
                  <Td>{item.qty}</Td>
                  <Td>{formatCurrency(item.unit_price)}</Td>
                  <Td>{formatCurrency(item.subtotal)}</Td>
                </Tr>
              ))}
            </Tbody>
          </Table>
          <div className="flex flex-col gap-1.5 border-t border-(--color-ink-900)/10 px-5 py-4 text-sm">
            <div className="flex justify-between text-(--color-ink-500)">
              <span>Subtotal</span>
              <span>{formatCurrency(invoice.subtotal_amount)}</span>
            </div>
            {invoice.discount_amount > 0 && (
              <div className="flex justify-between text-(--color-ink-500)">
                <span>Diskon</span>
                <span>-{formatCurrency(invoice.discount_amount)}</span>
              </div>
            )}
            {invoice.tax_amount > 0 && (
              <div className="flex justify-between text-(--color-ink-500)">
                <span>Pajak</span>
                <span>{formatCurrency(invoice.tax_amount)}</span>
              </div>
            )}
            <div className="flex justify-between font-semibold text-(--color-ink-900)">
              <span>Total</span>
              <span>{formatCurrency(invoice.total_amount)}</span>
            </div>
            <div className="flex justify-between text-(--color-ink-500)">
              <span>Dibayar</span>
              <span>{formatCurrency(invoice.amount_paid)}</span>
            </div>
            {outstanding > 0 && (
              <div className="flex justify-between font-semibold text-(--color-crit)">
                <span>Sisa Tagihan</span>
                <span>{formatCurrency(outstanding)}</span>
              </div>
            )}
          </div>
        </CardBody>
      </Card>

      <Card className="mt-6">
        <CardHeader title="Riwayat Pembayaran" />
        <CardBody>
          {payments.length === 0 ? (
            <EmptyState title="Belum ada pembayaran" />
          ) : (
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {payments.map((payment) => (
                <li key={payment.id} className="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                  <div className="text-sm">
                    <p className="font-semibold capitalize text-(--color-ink-900)">
                      {payment.method} · {formatCurrency(payment.amount)}
                    </p>
                    <p className="text-(--color-ink-500)">
                      {formatDateTime(payment.created_at)}
                      {payment.reference_no ? ` · Ref: ${payment.reference_no}` : ""}
                    </p>
                    {payment.proof_url && (
                      <a href={payment.proof_url} target="_blank" rel="noreferrer" className="font-semibold text-(--color-court-600) hover:underline">
                        Lihat bukti transfer
                      </a>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    <StatusBadge status={payment.status} />
                    {canVerify && payment.status === "pending" && (
                      <>
                        <form action={verifyPaymentAction.bind(null, invoiceId, payment.id, "reject")}>
                          <Button type="submit" variant="outline" size="sm">
                            Tolak
                          </Button>
                        </form>
                        <form action={verifyPaymentAction.bind(null, invoiceId, payment.id, "approve")}>
                          <Button type="submit" size="sm">
                            Verifikasi
                          </Button>
                        </form>
                      </>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      {canPay && (
        <Card className="mt-6">
          <CardHeader title="Kirim Pembayaran" description="Unggah bukti transfer bila membayar via transfer atau QRIS." />
          <CardBody>
            <PaymentForm invoiceId={invoiceId} defaultAmount={outstanding > 0 ? outstanding : invoice.total_amount} />
          </CardBody>
        </Card>
      )}
    </div>
  );
}
