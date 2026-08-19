import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { PageHeader } from "../PageHeader";

export const metadata = { title: "Kontak" };

type Location = { id: number; name: string; address: string | null; phone: string | null };

export default async function KontakPage() {
  const locations = await serverApi<Location[]>("/public/branches");
  const location = locations[0];

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <PageHeader
        eyebrow="Kontak"
        title="Hubungi Kami"
        description="Punya pertanyaan seputar paket latihan, jadwal, atau pendaftaran? Kirim pesan via WhatsApp atau
          kunjungi lokasi kami langsung."
      />

      <div className="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <Card>
          <CardBody className="flex flex-col gap-1.5 px-6 py-6">
            <p className="text-xs font-bold uppercase tracking-widest text-(--color-court-500)">Lokasi</p>
            <h3 className="font-display text-lg font-bold text-(--color-ink-900)">
              {location?.name ?? "Zul Tennis Clinic"}
            </h3>
            {location?.address && <p className="text-sm text-(--color-ink-500)">{location.address}</p>}
            {location?.phone && (
              <a
                href={`https://wa.me/${location.phone.replace(/[^0-9]/g, "")}`}
                target="_blank"
                rel="noreferrer"
                className="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-(--color-court-600) px-4 py-2 text-sm font-semibold text-white hover:bg-(--color-court-700)"
              >
                Chat via WhatsApp →
              </a>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardBody className="flex flex-col gap-1.5 px-6 py-6">
            <p className="text-xs font-bold uppercase tracking-widest text-(--color-court-500)">Email</p>
            <h3 className="font-display text-lg font-bold text-(--color-ink-900)">info@zultennisclinic.test</h3>
            <p className="text-sm text-(--color-ink-500)">
              Untuk pertanyaan umum, kerja sama, atau media, silakan kirim email kapan saja.
            </p>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
