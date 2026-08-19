import { Card, CardBody } from "@/components/ui/Card";
import { PageHeader } from "../PageHeader";

export const metadata = { title: "Testimoni" };

const TESTIMONIALS = [
  {
    name: "Ibu Wulan",
    role: "Wali Peserta — Program Anak-anak",
    quote:
      "Anak saya jadi lebih percaya diri dan disiplin sejak ikut latihan di sini. Pelatihnya sabar dan komunikatif ke orang tua.",
  },
  {
    name: "Rangga P.",
    role: "Peserta — Junior Development",
    quote:
      "Progress teknik pukulan saya terasa jauh lebih baik dalam beberapa bulan. Jadwalnya juga fleksibel untuk anak sekolah.",
  },
  {
    name: "Dedi Kurniawan",
    role: "Peserta — Performance Adult",
    quote:
      "Program latihannya terstruktur dan pelatihnya benar-benar memperhatikan detail teknik, bukan cuma sekadar main.",
  },
];

export default function TestimoniPage() {
  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <PageHeader
        eyebrow="Testimoni"
        title="Apa Kata Mereka"
        description="Pengalaman peserta dan orang tua yang telah bergabung di Zul Tennis Clinic."
      />

      <div className="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {TESTIMONIALS.map((t) => (
          <Card key={t.name} className="transition-shadow hover:shadow-md">
            <CardBody>
              <p className="text-(--color-court-500)">&ldquo;</p>
              <p className="text-sm text-(--color-ink-700)">{t.quote}</p>
              <p className="mt-4 font-semibold text-(--color-ink-900)">{t.name}</p>
              <p className="text-xs text-(--color-ink-500)">{t.role}</p>
            </CardBody>
          </Card>
        ))}
      </div>
    </div>
  );
}
