import { serverApi } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { EmptyState } from "@/components/ui/Feedback";
import { PageHeader } from "../PageHeader";
import { formatCurrency } from "@/lib/format";

export const metadata = { title: "Paket & Biaya" };

type Package = {
  id: number;
  name: string;
  session_count: number;
  validity_days: number;
  price: number;
};

export default async function PaketPage() {
  const packages = await serverApi<Package[]>("/public/packages");

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <PageHeader
        eyebrow="Paket & Biaya"
        title="Pilihan Paket Latihan"
        description="Hubungi kami untuk info lebih lanjut mengenai paket latihan."
      />

      {packages.length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada paket tersedia" description="Informasi paket akan segera diperbarui." />
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {packages.map((pkg) => (
            <Card key={pkg.id} className="transition-shadow hover:shadow-md">
              <CardHeader title={pkg.name} />
              <CardBody>
                <p className="font-display text-2xl font-extrabold text-(--color-court-500)">{formatCurrency(pkg.price)}</p>
                <ul className="mt-3 flex flex-col gap-1.5 text-sm text-(--color-ink-500)">
                  <li>{pkg.session_count} sesi latihan</li>
                  <li>Berlaku {pkg.validity_days} hari</li>
                </ul>
              </CardBody>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
