import { serverApi } from "@/lib/server-api";
import { Card, CardBody } from "@/components/ui/Card";

export const metadata = { title: "Profil Klinik" };

type Branch = { id: number; name: string; address: string | null; phone: string | null };

const VALUES = [
  { title: "Disiplin", desc: "Latihan terstruktur dengan kurikulum bertahap sesuai usia dan level." },
  { title: "Kompetensi Pelatih", desc: "Seluruh pelatih bersertifikat dan dievaluasi berkala." },
  { title: "Keselamatan", desc: "Standar keamanan lapangan, peralatan, dan penanganan cedera." },
  { title: "Perkembangan Terukur", desc: "Evaluasi berkala dan laporan perkembangan untuk setiap peserta." },
];

export default async function ProfilPage() {
  const branches = await serverApi<Branch[]>("/public/branches");

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Profil Klinik</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Tentang Zul Tennis Clinic
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Zul Tennis Clinic adalah akademi tenis yang dikelola secara profesional, menghadirkan program latihan
        terstruktur untuk anak-anak, remaja, dan dewasa. Kami berkomitmen membina pemain dari level pemula hingga
        kompetitif melalui kurikulum yang jelas, pelatih berpengalaman, dan fasilitas yang terawat.
      </p>

      <div className="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2">
        {VALUES.map((value) => (
          <Card key={value.title}>
            <CardBody>
              <h3 className="font-display text-base font-bold text-(--color-ink-900)">{value.title}</h3>
              <p className="mt-1.5 text-sm text-(--color-ink-500)">{value.desc}</p>
            </CardBody>
          </Card>
        ))}
      </div>

      <div className="mt-16">
        <h2 className="font-display text-2xl font-bold text-(--color-ink-900)">Cabang Kami</h2>
        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
          {branches.map((branch) => (
            <Card key={branch.id}>
              <CardBody>
                <h3 className="font-display text-base font-bold text-(--color-ink-900)">{branch.name}</h3>
                {branch.address && <p className="mt-1.5 text-sm text-(--color-ink-500)">{branch.address}</p>}
                {branch.phone && <p className="mt-1 text-sm text-(--color-ink-500)">{branch.phone}</p>}
              </CardBody>
            </Card>
          ))}
        </div>
      </div>
    </div>
  );
}
