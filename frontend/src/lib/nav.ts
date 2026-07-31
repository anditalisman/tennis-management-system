import { ROLES } from "./roles";

export type NavItem = {
  href: string;
  label: string;
  roles?: string[]; // omitted = visible to everyone signed in
};

export type NavSection = {
  title: string;
  items: NavItem[];
};

const { SUPER_ADMIN, MANAGEMENT, ADMINISTRATOR, COACH, PARTICIPANT, GUARDIAN, FINANCE } = ROLES;
const STAFF = [SUPER_ADMIN, MANAGEMENT, ADMINISTRATOR];

export const NAV_SECTIONS: NavSection[] = [
  {
    title: "Umum",
    items: [
      { href: "/portal/dashboard", label: "Dashboard" },
      { href: "/portal/notifikasi", label: "Notifikasi" },
    ],
  },
  {
    title: "Operasional",
    items: [
      { href: "/portal/peserta", label: "Peserta", roles: [...STAFF] },
      { href: "/portal/wali", label: "Wali & Anak", roles: [...STAFF] },
      { href: "/portal/program", label: "Program", roles: [...STAFF, COACH] },
      { href: "/portal/kelas", label: "Kelas", roles: [...STAFF, COACH] },
      { href: "/portal/jadwal", label: "Jadwal", roles: [...STAFF, COACH, PARTICIPANT, GUARDIAN] },
      { href: "/portal/absensi", label: "Absensi", roles: [...STAFF, COACH] },
      { href: "/portal/lapangan", label: "Lapangan", roles: [...STAFF, COACH] },
      { href: "/portal/inventaris", label: "Inventaris", roles: [...STAFF, COACH] },
      { href: "/portal/pelatih", label: "Pelatih", roles: [...STAFF, COACH, PARTICIPANT, GUARDIAN] },
    ],
  },
  {
    title: "Perkembangan & Dokumentasi",
    items: [
      { href: "/portal/evaluasi", label: "Evaluasi", roles: [...STAFF, COACH, PARTICIPANT, GUARDIAN] },
      { href: "/portal/galeri", label: "Galeri", roles: [...STAFF, COACH, PARTICIPANT, GUARDIAN] },
      { href: "/portal/pengumuman", label: "Pengumuman", roles: [...STAFF, PARTICIPANT, GUARDIAN] },
    ],
  },
  {
    title: "Keuangan",
    items: [
      { href: "/portal/paket", label: "Paket", roles: [...STAFF, FINANCE, PARTICIPANT, GUARDIAN] },
      { href: "/portal/tagihan", label: "Tagihan", roles: [...STAFF, FINANCE, PARTICIPANT, GUARDIAN] },
      { href: "/portal/voucher", label: "Voucher", roles: [SUPER_ADMIN, MANAGEMENT, FINANCE] },
      { href: "/portal/laporan", label: "Laporan", roles: [...STAFF, COACH, FINANCE] },
    ],
  },
  {
    title: "Pengaturan",
    items: [
      { href: "/portal/pengaturan/pengguna", label: "Pengguna & Peran", roles: [SUPER_ADMIN] },
      { href: "/portal/pengaturan/metode-pembayaran", label: "Metode Pembayaran", roles: [SUPER_ADMIN, FINANCE] },
      { href: "/portal/profil", label: "Profil Saya" },
    ],
  },
];

export function visibleNavSections(roles: string[]): NavSection[] {
  return NAV_SECTIONS.map((section) => ({
    ...section,
    items: section.items.filter((item) => !item.roles || item.roles.some((r) => roles.includes(r))),
  })).filter((section) => section.items.length > 0);
}
