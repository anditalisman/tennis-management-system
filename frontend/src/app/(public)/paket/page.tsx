import { serverApi } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { EmptyState } from "@/components/ui/Feedback";
import { formatCurrency } from "@/lib/format";

export const metadata = { title: "Paket & Biaya" };

type Package = {
  id: number;
  name: string;
  session_count: number;
  validity_days: number;
  price: number;
  type: string;
};

const TYPE_LABELS: Record<string, string> = {
  private: "Private",
  kelompok: "Kelompok",
  korporat: "Korporat",
};

const TYPE_DESCRIPTIONS: Record<string, string> = {
  private: "Sesi latihan satu lawan satu dengan pelatih.",
  kelompok: "Jam latihan digabung dengan peserta lain dalam satu kelas.",
  korporat: "Untuk satu instansi atau perusahaan.",
};

const TYPE_ORDER = ["private", "kelompok", "korporat"];

export default async function PaketPage() {
  const packages = await serverApi<Package[]>("/public/packages");

  const grouped = packages.reduce<Record<string, Package[]>>((acc, pkg) => {
    acc[pkg.type] = acc[pkg.type] ? [...acc[pkg.type], pkg] : [pkg];
    return acc;
  }, {});

  const sortedTypes = Object.keys(grouped).sort((a, b) => TYPE_ORDER.indexOf(a) - TYPE_ORDER.indexOf(b));

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Paket & Biaya</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Pilihan Paket Latihan
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">Pilih paket private, kelompok, atau korporat sesuai kebutuhan Anda.</p>

      {packages.length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada paket tersedia" description="Informasi paket akan segera diperbarui." />
        </div>
      ) : (
        <div className="mt-10 flex flex-col gap-10">
          {sortedTypes.map((type) => (
            <div key={type}>
              <h2 className="font-display text-xl font-bold text-(--color-ink-900)">{TYPE_LABELS[type] ?? type}</h2>
              {TYPE_DESCRIPTIONS[type] && <p className="mt-1 text-sm text-(--color-ink-500)">{TYPE_DESCRIPTIONS[type]}</p>}
              <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {grouped[type].map((pkg) => (
                  <Card key={pkg.id}>
                    <CardHeader title={pkg.name} />
                    <CardBody>
                      <p className="font-display text-2xl font-extrabold text-(--color-court-700)">
                        {formatCurrency(pkg.price)}
                      </p>
                      <ul className="mt-3 flex flex-col gap-1.5 text-sm text-(--color-ink-500)">
                        <li>{pkg.session_count} sesi latihan</li>
                        <li>Berlaku {pkg.validity_days} hari</li>
                      </ul>
                    </CardBody>
                  </Card>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
