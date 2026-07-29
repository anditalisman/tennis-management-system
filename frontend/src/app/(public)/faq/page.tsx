export const metadata = { title: "FAQ" };

const FAQS = [
  {
    q: "Berapa usia minimal untuk mendaftar?",
    a: "Kami menerima peserta mulai usia 5 tahun untuk program Little Aces, hingga dewasa untuk program Performance Adult.",
  },
  {
    q: "Apakah bisa mencoba kelas sebelum mendaftar paket?",
    a: "Bisa. Silakan hubungi cabang terdekat untuk menjadwalkan sesi trial sebelum membeli paket.",
  },
  {
    q: "Bagaimana cara pembayaran paket latihan?",
    a: "Pembayaran dapat dilakukan melalui transfer bank atau metode lain yang tersedia di portal peserta setelah pendaftaran disetujui.",
  },
  {
    q: "Apa yang terjadi jika sesi latihan terlewat?",
    a: "Sesi yang tidak dapat dihadiri dapat dikoordinasikan dengan pelatih untuk penjadwalan ulang, mengikuti kebijakan masing-masing cabang.",
  },
  {
    q: "Apakah paket latihan memiliki masa berlaku?",
    a: "Ya, setiap paket memiliki masa berlaku dalam hari sejak aktivasi, dapat dilihat pada halaman Paket & Biaya.",
  },
  {
    q: "Bagaimana cara memantau perkembangan anak saya?",
    a: "Wali dapat memantau jadwal, kehadiran, dan evaluasi perkembangan peserta melalui portal wali setelah login.",
  },
];

export default function FaqPage() {
  return (
    <div className="mx-auto max-w-3xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">FAQ</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Pertanyaan yang Sering Diajukan
      </h1>

      <div className="mt-10 flex flex-col divide-y divide-(--color-ink-900)/10 rounded-2xl border border-(--color-ink-900)/10 bg-(--color-paper-raised)">
        {FAQS.map((item) => (
          <details key={item.q} className="group px-5 py-4">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-(--color-ink-900)">
              {item.q}
              <span className="text-(--color-ink-500) transition group-open:rotate-45">+</span>
            </summary>
            <p className="mt-2 text-sm text-(--color-ink-500)">{item.a}</p>
          </details>
        ))}
      </div>
    </div>
  );
}
