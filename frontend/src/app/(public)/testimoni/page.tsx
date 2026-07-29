import { Card, CardBody } from "@/components/ui/Card";

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
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Testimoni</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Apa Kata Mereka
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Pengalaman peserta dan orang tua yang telah bergabung di Zul Tennis Clinic.
      </p>

      <div className="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {TESTIMONIALS.map((t) => (
          <Card key={t.name}>
            <CardBody>
              <p className="text-(--color-court-600)">&ldquo;</p>
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
