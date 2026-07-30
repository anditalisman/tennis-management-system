import Link from "next/link";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { isStaff, hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { StatusBadge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { formatDate, formatTime } from "@/lib/format";
import { cancelScheduleAction, checkInAction } from "@/lib/actions/schedules";
import { bulkVerifyAttendanceAction } from "@/lib/actions/attendance";

export const metadata = { title: "Detail Jadwal" };

type Schedule = {
  id: number;
  class_id: number;
  court_id: number;
  coach_id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  type: string;
  status: string;
  cancellation_reason: string | null;
};
type TrainingClass = { id: number; name: string };
type Court = { id: number; name: string };
type Coach = { id: number; name: string | null };
type Member = { id: string; full_name: string; registration_no: string };
type AttendanceRecord = {
  id: number;
  participant_id: string;
  participant_name: string | null;
  status: string;
  check_in_at: string | null;
};

const STATUS_OPTIONS = [
  { value: "present", label: "Hadir" },
  { value: "late", label: "Terlambat" },
  { value: "absent", label: "Tidak Hadir" },
  { value: "excused", label: "Izin" },
  { value: "sick", label: "Sakit" },
  { value: "left_early", label: "Pulang Awal" },
];

export default async function JadwalDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { id } = await params;
  const { error } = await searchParams;
  const scheduleId = Number(id);
  const staff = isStaff(session.user.roles);
  const isCoach = hasRole(session.user.roles, ROLES.COACH);
  const isParticipant = hasRole(session.user.roles, ROLES.PARTICIPANT);

  const schedule = await serverApiOrNull<Schedule>(`/schedules/${id}`);
  if (!schedule) notFound();

  const [trainingClass, court, coach, attendance] = await Promise.all([
    serverApiOrNull<TrainingClass>(`/classes/${schedule.class_id}`),
    serverApiOrNull<Court>(`/courts/${schedule.court_id}`),
    serverApiOrNull<Coach>(`/coaches/${schedule.coach_id}`),
    serverApiOrNull<AttendanceRecord[]>(`/schedules/${id}/attendance`),
  ]);
  const members = (staff || isCoach) ? ((await serverApiOrNull<Member[]>(`/classes/${schedule.class_id}/members`)) ?? []) : [];

  const attendanceByParticipant = new Map((attendance ?? []).map((a) => [a.participant_id, a]));
  // A participant can only ever see their own attendance record here
  // (AttendanceController::index filters by canSeeParticipant), so this is
  // safely "did I already check in", not "someone else's record".
  const myAttendance = isParticipant ? (attendance ?? [])[0] : undefined;
  const canManageAttendance = staff || isCoach;
  const canCancel = staff;
  const isCancelled = schedule.status === "cancelled";

  return (
    <div className="max-w-3xl">
      <Link href="/portal/jadwal" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke daftar jadwal
      </Link>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">{trainingClass?.name ?? "Sesi Latihan"}</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">
            {formatDate(schedule.session_date, { weekday: "long" })} · {formatTime(schedule.start_time)}–{formatTime(schedule.end_time)}
          </p>
          <p className="mt-1 text-sm text-(--color-ink-500)">
            Lapangan: {court?.name ?? "-"} · Pelatih: {coach?.name ?? "-"}
          </p>
        </div>
        <StatusBadge status={schedule.status} />
      </div>

      {isCancelled && schedule.cancellation_reason && (
        <div className="mt-4">
          <Alert tone="warn">Dibatalkan: {schedule.cancellation_reason}</Alert>
        </div>
      )}

      {isParticipant && !isCancelled && (
        <Card className="mt-6">
          <CardBody className="flex flex-wrap items-center justify-between gap-4">
            {myAttendance ? (
              <>
                <p className="text-sm text-(--color-ink-700)">
                  Anda sudah check-in{myAttendance.check_in_at ? ` pukul ${formatTime(myAttendance.check_in_at)}` : ""}
                  {myAttendance.status === "pending" ? ", menunggu verifikasi pelatih." : "."}
                </p>
                <StatusBadge status={myAttendance.status} />
              </>
            ) : (
              <>
                <p className="text-sm text-(--color-ink-700)">Sudah sampai di lokasi latihan?</p>
                <form action={checkInAction.bind(null, scheduleId)}>
                  <Button type="submit">Check-in Sekarang</Button>
                </form>
              </>
            )}
          </CardBody>
        </Card>
      )}

      {canManageAttendance && !isCancelled && (
        <Card className="mt-6">
          <CardHeader title="Verifikasi Absensi" />
          <CardBody>
            {members.length === 0 ? (
              <EmptyState title="Belum ada anggota kelas" />
            ) : (
              <form action={bulkVerifyAttendanceAction.bind(null, scheduleId)} className="flex flex-col gap-3">
                {members.map((m) => {
                  const existing = attendanceByParticipant.get(m.id);
                  return (
                    <div key={m.id} className="flex items-center justify-between gap-4 rounded-lg border border-(--color-ink-900)/10 px-4 py-3">
                      <input type="hidden" name="participant_id" value={m.id} />
                      <div>
                        <p className="text-sm font-semibold text-(--color-ink-900)">{m.full_name}</p>
                        <p className="text-xs text-(--color-ink-500)">{m.registration_no}</p>
                      </div>
                      <div className="w-40">
                        <Select name={`status_${m.id}`} defaultValue={existing?.status ?? "present"}>
                          {STATUS_OPTIONS.map((opt) => (
                            <option key={opt.value} value={opt.value}>
                              {opt.label}
                            </option>
                          ))}
                        </Select>
                      </div>
                    </div>
                  );
                })}
                <Button type="submit" className="self-end">
                  Simpan Absensi
                </Button>
              </form>
            )}
          </CardBody>
        </Card>
      )}

      {canCancel && !isCancelled && (
        <Card className="mt-6">
          <CardHeader title="Batalkan Jadwal" />
          <CardBody>
            <form action={cancelScheduleAction.bind(null, scheduleId)} className="flex flex-wrap items-end gap-3">
              <div className="flex-1">
                <Input label="Alasan pembatalan" name="reason" required />
              </div>
              <Button type="submit" variant="danger">
                Batalkan
              </Button>
            </form>
          </CardBody>
        </Card>
      )}
    </div>
  );
}
