import Link from "next/link";
import { Card, CardBody } from "@/components/ui/Card";
import { PageHeader } from "../PageHeader";

export const metadata = { title: "Profil Klinik" };

const VALUES = [
  { title: "Disiplin", desc: "Latihan terstruktur dengan kurikulum bertahap sesuai usia dan level." },
  { title: "Kompetensi Pelatih", desc: "Seluruh pelatih bersertifikat dan dievaluasi berkala." },
  { title: "Keselamatan", desc: "Standar keamanan lapangan, peralatan, dan penanganan cedera." },
  { title: "Perkembangan Terukur", desc: "Evaluasi berkala dan laporan perkembangan untuk setiap peserta." },
];

export default function ProfilPage() {
  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <PageHeader
        eyebrow="Profil Klinik"
        title="Tentang Zul Tennis Clinic"
        description="Akademi tenis yang dikelola secara profesional, menghadirkan program latihan terstruktur untuk
          anak-anak, remaja, dan dewasa. Kami berkomitmen membina pemain dari level pemula hingga kompetitif
          melalui kurikulum yang jelas, pelatih berpengalaman, dan fasilitas yang terawat."
      />

      <div className="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-2">
        {VALUES.map((value) => (
          <Card key={value.title} className="transition-shadow hover:shadow-md">
            <CardBody>
              <h3 className="font-display text-base font-bold text-(--color-ink-900)">{value.title}</h3>
              <p className="mt-1.5 text-sm leading-relaxed text-(--color-ink-500)">{value.desc}</p>
            </CardBody>
          </Card>
        ))}
      </div>

      <div className="mt-16 rounded-2xl bg-(--color-court-700) px-8 py-10 sm:px-12">
        <h2 className="font-display text-2xl font-bold text-white">Siap bergabung bersama kami?</h2>
        <p className="mt-2 max-w-lg text-white/70">
          Mulai perjalanan tenis Anda hari ini — daftar sekarang atau lihat pilihan paket latihan yang tersedia.
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Link
            href="/pendaftaran"
            className="rounded-full bg-(--color-ball-500) px-6 py-3 text-sm font-bold text-(--color-court-900) hover:bg-(--color-ball-400)"
          >
            Daftar Sekarang
          </Link>
          <Link
            href="/paket"
            className="rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
          >
            Lihat Paket & Biaya
          </Link>
        </div>
      </div>
    </div>
  );
}
