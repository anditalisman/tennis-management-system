import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { VoucherForm } from "@/components/portal/VoucherForm";

export const metadata = { title: "Buat Voucher" };

export default async function NewVoucherPage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Buat Voucher</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Voucher" />
        <CardBody>
          <VoucherForm />
        </CardBody>
      </Card>
    </div>
  );
}
