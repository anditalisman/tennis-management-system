import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PaymentMethodForm } from "@/components/portal/PaymentMethodForm";

export const metadata = { title: "Tambah Metode Pembayaran" };

export default async function NewPaymentMethodPage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Metode Pembayaran</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Metode" />
        <CardBody>
          <PaymentMethodForm />
        </CardBody>
      </Card>
    </div>
  );
}
