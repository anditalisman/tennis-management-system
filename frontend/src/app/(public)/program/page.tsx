import Link from "next/link";
import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";

export const metadata = { title: "Program Latihan" };

type Program = {
  id: number;
  name: string;
  age_group: string | null;
  skill_level: string | null;
  target_competency: string | null;
  description: string | null;
};

const AGE_LABELS: Record<string, string> = { anak: "Anak-anak", remaja: "Remaja", dewasa: "Dewasa" };
const SKILL_LABELS: Record<string, string> = { beginner: "Pemula", intermediate: "Menengah", advanced: "Mahir" };

export default async function ProgramPage() {
  const programs = await serverApi<Program[]>("/public/programs");

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Program</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Program Latihan Tenis
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Pilih program yang sesuai dengan usia dan level kemampuan. Setiap program memiliki target kompetensi yang
        jelas dan dibimbing oleh pelatih bersertifikat.
      </p>

      {programs.length === 0 ? (
        <div className="mt-10">
          <EmptyState title="Belum ada program tersedia" description="Program latihan akan segera diumumkan." />
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
          {programs.map((program) => (
            <Card key={program.id}>
              <CardBody>
                <div className="flex flex-wrap gap-2">
                  {program.age_group && <Badge tone="court">{AGE_LABELS[program.age_group] ?? program.age_group}</Badge>}
                  {program.skill_level && <Badge>{SKILL_LABELS[program.skill_level] ?? program.skill_level}</Badge>}
                </div>
                <h3 className="mt-3 font-display text-lg font-bold text-(--color-ink-900)">{program.name}</h3>
                {program.description && <p className="mt-2 text-sm text-(--color-ink-500)">{program.description}</p>}
                {program.target_competency && (
                  <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-(--color-ink-500)">
                    Target Kompetensi
                  </p>
                )}
                {program.target_competency && (
                  <p className="mt-1 text-sm text-(--color-ink-700)">{program.target_competency}</p>
                )}
              </CardBody>
            </Card>
          ))}
        </div>
      )}

      <div className="mt-12 flex flex-wrap gap-3">
        <Link
          href="/paket"
          className="rounded-full bg-(--color-court-600) px-6 py-3 text-sm font-semibold text-white hover:bg-(--color-court-700)"
        >
          Lihat Paket & Biaya
        </Link>
        <Link
          href="/pendaftaran"
          className="rounded-full border border-(--color-ink-900)/15 px-6 py-3 text-sm font-semibold text-(--color-ink-700) hover:bg-(--color-ink-900)/5"
        >
          Daftar Sekarang
        </Link>
      </div>
    </div>
  );
}
