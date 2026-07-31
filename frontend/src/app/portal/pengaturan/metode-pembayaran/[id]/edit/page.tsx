import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApi } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PaymentMethodForm } from "@/components/portal/PaymentMethodForm";

export const metadata = { title: "Edit Metode Pembayaran" };

type PaymentMethod = {
  id: number;
  type: string;
  label: string;
  details: string | null;
  image_url: string | null;
  is_active: boolean;
};

export default async function EditPaymentMethodPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const methods = await serverApi<PaymentMethod[]>("/payment-methods");
  const method = methods.find((m) => String(m.id) === id);
  if (!method) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Metode Pembayaran</h1>
      <Card className="mt-6">
        <CardHeader title={method.label} />
        <CardBody>
          <PaymentMethodForm method={method} />
        </CardBody>
      </Card>
    </div>
  );
}
