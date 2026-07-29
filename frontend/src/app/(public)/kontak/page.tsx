import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";

export const metadata = { title: "Kontak" };

type Branch = { id: number; name: string; address: string | null; phone: string | null };

export default async function KontakPage() {
  const branches = await serverApi<Branch[]>("/public/branches");

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Kontak</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Hubungi Kami
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Punya pertanyaan seputar program, jadwal, atau pendaftaran? Hubungi cabang terdekat langsung, atau kirim pesan
        via WhatsApp.
      </p>

      <div className="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2">
        {branches.map((branch) => (
          <Card key={branch.id}>
            <CardBody>
              <h3 className="font-display text-base font-bold text-(--color-ink-900)">{branch.name}</h3>
              {branch.address && <p className="mt-2 text-sm text-(--color-ink-500)">{branch.address}</p>}
              {branch.phone && (
                <a
                  href={`https://wa.me/${branch.phone.replace(/[^0-9]/g, "")}`}
                  target="_blank"
                  rel="noreferrer"
                  className="mt-3 inline-block text-sm font-semibold text-(--color-court-600) hover:text-(--color-court-700)"
                >
                  {branch.phone} — Chat via WhatsApp
                </a>
              )}
            </CardBody>
          </Card>
        ))}
      </div>

      <div className="mt-10 rounded-2xl border border-(--color-ink-900)/10 bg-(--color-paper-raised) px-6 py-5 text-sm text-(--color-ink-500)">
        Email: <span className="font-semibold text-(--color-ink-900)">info@zultennisclinic.test</span>
      </div>
    </div>
  );
}
