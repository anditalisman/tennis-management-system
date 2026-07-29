import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { InvoiceForm } from "@/components/portal/InvoiceForm";

export const metadata = { title: "Buat Tagihan" };

export default async function NewInvoicePage() {
  await verifySession();

  const [participants, packages] = await Promise.all([
    serverApiPaginated<{ id: string; full_name: string; registration_no: string }>("/participants?status=active&per_page=100"),
    serverApiPaginated<{ id: number; name: string; price: number }>("/packages?status=active&per_page=100"),
  ]);

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Buat Tagihan</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Tagihan" />
        <CardBody>
          <InvoiceForm participants={participants.data} packages={packages.data} />
        </CardBody>
      </Card>
    </div>
  );
}
