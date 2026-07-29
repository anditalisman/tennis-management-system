import Link from "next/link";
import { MobileNav } from "./MobileNav";

const NAV = [
  { href: "/program", label: "Program" },
  { href: "/paket", label: "Paket & Biaya" },
  { href: "/pelatih", label: "Pelatih" },
  { href: "/kontak", label: "Kontak" },
];

const FOOTER_NAV = [
  { href: "/profil", label: "Profil" },
  { href: "/jadwal", label: "Jadwal" },
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
      <header className="sticky top-0 z-40 border-b border-(--color-ink-900)/10 bg-(--color-paper)/90 backdrop-blur">
        <div className="relative mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <Link
            href="/"
            className="min-w-0 shrink truncate font-display text-lg font-extrabold tracking-tight text-(--color-court-700)"
          >
            Zul Tennis Clinic
          </Link>
          <nav className="hidden flex-wrap items-center gap-x-5 gap-y-1 text-sm font-medium text-(--color-ink-700) lg:flex">
            {NAV.map((item) => (
              <Link key={item.href} href={item.href} className="hover:text-(--color-court-600)">
                {item.label}
              </Link>
            ))}
          </nav>
          <div className="flex shrink-0 items-center gap-3">
            <Link
              href="/login"
              className="hidden text-sm font-semibold text-(--color-ink-700) hover:text-(--color-court-600) sm:inline"
            >
              Masuk
            </Link>
            <Link
              href="/pendaftaran"
              className="rounded-full bg-(--color-court-600) px-4 py-2 text-sm font-semibold text-white hover:bg-(--color-court-700)"
            >
              Daftar Sekarang
            </Link>
            <MobileNav items={NAV} />
          </div>
        </div>
      </header>

      <main className="flex-1">{children}</main>

      <footer className="border-t border-(--color-ink-900)/10 bg-(--color-paper-raised)">
        <div className="mx-auto max-w-6xl px-6 py-10 text-sm text-(--color-ink-500)">
          <p className="font-display text-base font-bold text-(--color-court-700)">
            Zul Tennis Clinic
          </p>
          <p className="mt-1">Train Better, Play Stronger.</p>
          <nav className="mt-6 flex flex-wrap gap-x-5 gap-y-2 font-medium">
            {FOOTER_NAV.map((item) => (
              <Link key={item.href} href={item.href} className="hover:text-(--color-court-600)">
                {item.label}
              </Link>
            ))}
          </nav>
          <p className="mt-6">© {new Date().getFullYear()} Zul Tennis Clinic Management System.</p>
        </div>
      </footer>
    </div>
  );
}
