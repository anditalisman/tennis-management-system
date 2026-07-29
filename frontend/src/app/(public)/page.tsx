import Link from "next/link";
import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { StatTile } from "@/components/ui/Feedback";

const CTAS = [
  { href: "/pendaftaran", label: "Daftar Sekarang", primary: true },
  { href: "/program", label: "Lihat Program", primary: false },
  { href: "/jadwal", label: "Cek Jadwal", primary: false },
];

type Program = { id: number; name: string; age_group: string | null; description: string | null };
type Branch = { id: number; name: string; address: string | null };

export default async function HomePage() {
  const [programs, branches] = await Promise.all([
    serverApi<Program[]>("/public/programs"),
    serverApi<Branch[]>("/public/branches"),
  ]);

  return (
    <>
      <section className="relative overflow-hidden bg-(--color-court-700)">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(201,223,60,0.25),transparent_55%)]" />
        <div className="relative mx-auto max-w-6xl px-6 py-24 sm:py-32">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-(--color-ball-400)">
            Zul Tennis Clinic
          </span>
          <h1 className="mt-6 max-w-2xl font-display text-4xl font-extrabold leading-[1.05] text-white sm:text-6xl">
            Train Better, Play Stronger.
          </h1>
          <p className="mt-6 max-w-xl text-lg text-white/80">
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
                    ? "rounded-full bg-(--color-ball-500) px-6 py-3 text-sm font-bold text-(--color-court-900) hover:bg-(--color-ball-400)"
                    : "rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
                }
              >
                {cta.label}
              </Link>
            ))}
            <a
              href="https://wa.me/"
              target="_blank"
              rel="noreferrer"
              className="rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
            >
              Hubungi via WhatsApp
            </a>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-6 py-20">
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
          <StatTile label="Program aktif" value={programs.length} />
          <StatTile label="Cabang" value={branches.length} />
          <StatTile label="Level latihan" value="Pemula – Mahir" />
        </div>

        <div className="mt-16 flex items-end justify-between gap-4">
          <h2 className="font-display text-2xl font-bold text-(--color-ink-900) sm:text-3xl">Program Unggulan</h2>
          <Link href="/program" className="text-sm font-semibold text-(--color-court-600) hover:text-(--color-court-700)">
            Lihat semua →
          </Link>
        </div>
        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {programs.slice(0, 3).map((program) => (
            <Card key={program.id}>
              <CardBody>
                <h3 className="font-display text-base font-bold text-(--color-ink-900)">{program.name}</h3>
                {program.description && <p className="mt-2 text-sm text-(--color-ink-500)">{program.description}</p>}
              </CardBody>
            </Card>
          ))}
        </div>
      </section>
    </>
  );
}
