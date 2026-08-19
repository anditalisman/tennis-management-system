import { verifySession } from "@/lib/dal";
import { serverApi } from "@/lib/server-api";
import { StatTile } from "@/components/ui/Feedback";

type StaffDashboard = {
  scope: "staff";
  participants_active: number;
  participants_pending_verification: number;
  schedules_upcoming_7d: number;
  galleries_pending_moderation: number;
};
type CoachDashboard = { scope: "coach"; my_schedules_upcoming_7d: number };
type ParticipantDashboard = { scope: "participant"; my_active_classes: number };
type GuardianDashboard = { scope: "guardian"; children_count: number };
type NoneDashboard = { scope: "none" };

type DashboardData = StaffDashboard | CoachDashboard | ParticipantDashboard | GuardianDashboard | NoneDashboard;

export const metadata = { title: "Dashboard" };

export default async function DashboardPage() {
  const session = await verifySession();
  const dashboard = await serverApi<DashboardData>("/dashboard");

  return (
    <div className="max-w-5xl">
      <div className="relative overflow-hidden rounded-2xl bg-(--color-court-700) px-6 py-8 sm:px-8">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(201,223,60,0.22),transparent_55%)]" />
        <div className="relative">
          <p className="text-xs font-bold uppercase tracking-widest text-(--color-ball-400)">Dashboard</p>
          <h1 className="mt-2 font-display text-2xl font-extrabold text-white sm:text-3xl">
            Selamat datang, {session.user.name.split(" ")[0]}
          </h1>
          <p className="mt-1.5 text-sm text-white/70">Ringkasan operasional Zul Tennis Clinic.</p>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {dashboard.scope === "staff" && (
          <>
            <StatTile label="Peserta aktif" value={dashboard.participants_active} />
            <StatTile label="Menunggu verifikasi" value={dashboard.participants_pending_verification} hint="Peserta baru mendaftar" />
            <StatTile label="Jadwal 7 hari ke depan" value={dashboard.schedules_upcoming_7d} />
            <StatTile label="Galeri menunggu moderasi" value={dashboard.galleries_pending_moderation} />
          </>
        )}
        {dashboard.scope === "coach" && (
          <StatTile label="Jadwal mengajar 7 hari ke depan" value={dashboard.my_schedules_upcoming_7d} />
        )}
        {dashboard.scope === "participant" && <StatTile label="Kelas aktif saya" value={dashboard.my_active_classes} />}
        {dashboard.scope === "guardian" && <StatTile label="Anak yang dipantau" value={dashboard.children_count} />}
        {dashboard.scope === "none" && (
          <p className="text-sm text-(--color-ink-500)">Belum ada ringkasan untuk peran akun ini.</p>
        )}
      </div>
    </div>
  );
}
