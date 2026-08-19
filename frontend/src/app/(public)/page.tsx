import Link from "next/link";
import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { StatTile } from "@/components/ui/Feedback";
import { formatCurrency } from "@/lib/format";

const CTAS = [
  { href: "/pendaftaran", label: "Daftar Sekarang", primary: true },
  { href: "/paket", label: "Lihat Paket & Biaya", primary: false },
];

const HIGHLIGHTS = [
  { title: "Kurikulum Terstruktur", desc: "Tahapan latihan yang jelas dari dasar hingga kompetitif." },
  { title: "Pelatih Bersertifikat", desc: "Dibimbing pelatih berpengalaman yang dievaluasi berkala." },
  { title: "Jadwal Fleksibel", desc: "Sesi latihan yang mudah disesuaikan dengan rutinitas Anda." },
];

type Package = { id: number; name: string; session_count: number; validity_days: number; price: number };
type Coach = { id: number; name: string | null };

export default async function HomePage() {
  const [packages, coaches] = await Promise.all([
    serverApi<Package[]>("/public/packages"),
    serverApi<Coach[]>("/public/coaches"),
  ]);

  return (
    <>
      <section className="relative overflow-hidden bg-(--color-court-700)">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(201,223,60,0.28),transparent_55%)]" />
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,rgba(31,107,61,0.6),transparent_50%)]" />
        <div
          className="absolute inset-0 opacity-[0.07]"
          style={{
            backgroundImage:
              "radial-gradient(circle, white 1.5px, transparent 1.5px)",
            backgroundSize: "28px 28px",
          }}
        />
        <div className="relative mx-auto max-w-6xl px-6 py-24 sm:py-32">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-(--color-ball-400) ring-1 ring-inset ring-white/15">
            Zul Tennis Clinic
          </span>
          <h1 className="mt-6 max-w-2xl font-display text-5xl font-black leading-[1.02] text-white sm:text-7xl">
            Train Better,
            <br />
            <span className="text-(--color-ball-400)">Play Stronger.</span>
          </h1>
          <p className="mt-6 max-w-xl text-lg text-white/75">
            Akademi tenis terkelola secara profesional — program latihan terstruktur,
            pelatih bersertifikat, dan jadwal yang mudah diikuti untuk semua usia dan level.
          </p>
          <div className="mt-10 flex flex-wrap gap-3">
            {CTAS.map((cta) => (
              <Link
                key={cta.href}
                href={cta.href}
                className={
                  cta.primary
                    ? "rounded-full bg-(--color-ball-500) px-7 py-3.5 text-sm font-bold text-(--color-court-900) shadow-lg shadow-black/20 transition-transform hover:scale-[1.03] hover:bg-(--color-ball-400)"
                    : "rounded-full border border-white/30 px-7 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-white/10"
                }
              >
                {cta.label}
              </Link>
            ))}
            <a
              href="https://wa.me/"
              target="_blank"
              rel="noreferrer"
              className="rounded-full border border-white/30 px-7 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-white/10"
            >
              Hubungi via WhatsApp
            </a>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-6 py-20">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <StatTile label="Paket latihan" value={packages.length} />
          <StatTile label="Pelatih aktif" value={coaches.length} />
          <StatTile label="Level latihan" value="Pemula – Mahir" />
        </div>

        <div className="mt-20 grid grid-cols-1 gap-8 sm:grid-cols-3">
          {HIGHLIGHTS.map((item) => (
            <div key={item.title}>
              <div className="h-9 w-9 rounded-lg bg-(--color-ball-500)" />
              <h3 className="mt-4 font-display text-lg font-bold text-(--color-ink-900)">{item.title}</h3>
              <p className="mt-1.5 text-sm leading-relaxed text-(--color-ink-500)">{item.desc}</p>
            </div>
          ))}
        </div>

        {packages.length > 0 && (
          <>
            <div className="mt-20 flex items-end justify-between gap-4">
              <div>
                <span className="text-xs font-bold uppercase tracking-widest text-(--color-court-500)">
                  Paket &amp; Biaya
                </span>
                <h2 className="mt-2 font-display text-2xl font-bold text-(--color-ink-900) sm:text-3xl">
                  Pilih Paket Latihanmu
                </h2>
              </div>
              <Link href="/paket" className="shrink-0 text-sm font-semibold text-(--color-court-500) hover:text-(--color-ink-900)">
                Lihat semua →
              </Link>
            </div>
            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {packages.slice(0, 3).map((pkg) => (
                <Card key={pkg.id} className="group transition-shadow hover:shadow-md">
                  <CardBody>
                    <h3 className="font-display text-base font-bold text-(--color-ink-900)">{pkg.name}</h3>
                    <p className="mt-2 font-display text-2xl font-extrabold text-(--color-court-500)">
                      {formatCurrency(pkg.price)}
                    </p>
                    <p className="mt-1 text-sm text-(--color-ink-500)">
                      {pkg.session_count} sesi · berlaku {pkg.validity_days} hari
                    </p>
                  </CardBody>
                </Card>
              ))}
            </div>
          </>
        )}
      </section>
    </>
  );
}
