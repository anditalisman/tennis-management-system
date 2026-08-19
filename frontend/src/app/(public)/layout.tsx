import Link from "next/link";
import { MobileNav } from "./MobileNav";

const NAV = [
  { href: "/paket", label: "Paket & Biaya" },
  { href: "/pelatih", label: "Pelatih" },
  { href: "/jadwal", label: "Jadwal" },
  { href: "/turnamen-kemerdekaan", label: "Turnamen Kemerdekaan" },
  { href: "/kontak", label: "Kontak" },
];

const FOOTER_NAV = [
  { href: "/profil", label: "Profil" },
  { href: "/lapangan", label: "Lapangan" },
  { href: "/galeri", label: "Galeri" },
  { href: "/testimoni", label: "Testimoni" },
  { href: "/faq", label: "FAQ" },
];

export default function PublicLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <div className="flex min-h-full flex-col">
      <header className="sticky top-0 z-40 border-b border-(--color-ink-900)/10 bg-(--color-paper)/85 backdrop-blur-md">
        <div className="relative mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <Link href="/" className="group flex min-w-0 shrink items-center gap-2.5">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-(--color-court-600) font-display text-sm font-black text-(--color-ball-400) transition-transform group-hover:scale-105">
              ZT
            </span>
            <span className="truncate font-display text-lg font-extrabold tracking-tight text-(--color-court-500)">
              Zul Tennis Clinic
            </span>
          </Link>
          <nav className="hidden flex-wrap items-center gap-x-7 gap-y-1 text-sm font-semibold text-(--color-ink-700) lg:flex">
            {NAV.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className="relative py-1 transition-colors hover:text-(--color-court-500)"
              >
                {item.label}
              </Link>
            ))}
          </nav>
          <div className="flex shrink-0 items-center gap-3">
            <Link
              href="/login"
              className="hidden text-sm font-semibold text-(--color-ink-700) hover:text-(--color-court-500) sm:inline"
            >
              Masuk
            </Link>
            <Link
              href="/pendaftaran"
              className="rounded-full bg-(--color-court-600) px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-(--color-court-600)/30 transition-colors hover:bg-(--color-court-700)"
            >
              Daftar Sekarang
            </Link>
            <MobileNav items={NAV} />
          </div>
        </div>
      </header>

      <main className="flex-1">{children}</main>

      <footer className="border-t border-(--color-ink-900)/10 bg-(--color-court-900)">
        <div className="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-6 py-14 sm:grid-cols-[1.3fr_1fr]">
          <div>
            <div className="flex items-center gap-2.5">
              <span className="flex h-9 w-9 items-center justify-center rounded-full bg-(--color-ball-500) font-display text-sm font-black text-(--color-court-900)">
                ZT
              </span>
              <p className="font-display text-lg font-extrabold text-white">Zul Tennis Clinic</p>
            </div>
            <p className="mt-3 max-w-xs text-sm text-white/60">
              Train Better, Play Stronger. Akademi tenis terkelola secara profesional dengan kurikulum
              terstruktur dan pelatih bersertifikat.
            </p>
            <a
              href="https://wa.me/"
              target="_blank"
              rel="noreferrer"
              className="mt-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/15"
            >
              Hubungi via WhatsApp →
            </a>
          </div>
          <nav className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm font-medium text-white/70 sm:justify-items-end">
            {FOOTER_NAV.map((item) => (
              <Link key={item.href} href={item.href} className="transition-colors hover:text-(--color-ball-400)">
                {item.label}
              </Link>
            ))}
          </nav>
        </div>
        <div className="border-t border-white/10">
          <p className="mx-auto max-w-6xl px-6 py-5 text-xs text-white/40">
            © {new Date().getFullYear()} Zul Tennis Clinic Management System.
          </p>
        </div>
      </footer>
    </div>
  );
}
