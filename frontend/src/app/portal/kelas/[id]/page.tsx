import Link from "next/link";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated, serverApiOrNull } from "@/lib/server-api";
import { isStaff } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { StatusBadge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { formatDate } from "@/lib/format";
import {
  bookTrialClassAction,
  convertTrialClassAction,
  enrollParticipantAction,
  removeMemberAction,
} from "@/lib/actions/classes";

export const metadata = { title: "Detail Kelas" };

type TrainingClass = {
  id: number;
  name: string;
  active_member_count: number;
  capacity_max: number;
  quota_remaining: number;
  status: string;
};
type Member = { id: string; full_name: string; registration_no: string; status: string };
type WaitingEntry = { participant_id: string; participant_name: string; status: string; created_at: string };
type Participant = { id: string; full_name: string; registration_no: string };
type Trial = { id: number; participant_name: string; trial_date: string | null; converted_to_member: boolean };

export default async function KelasDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { id } = await params;
  const { error } = await searchParams;
  const classId = Number(id);
  const staff = isStaff(session.user.roles);

  const trainingClass = await serverApiOrNull<TrainingClass>(`/classes/${id}`);
  if (!trainingClass) notFound();

  const [membersResult, waitingListResult, allParticipants, trialsResult] = await Promise.all([
    serverApiOrNull<Member[]>(`/classes/${id}/members`),
    serverApiOrNull<WaitingEntry[]>(`/classes/${id}/waiting-list`),
    staff ? serverApiPaginated<Participant>("/participants?status=active&per_page=100") : Promise.resolve(null),
    staff ? serverApiOrNull<Trial[]>(`/classes/${id}/trials`) : Promise.resolve(null),
  ]);
  const members = membersResult ?? [];
  const waitingList = waitingListResult ?? [];
  const trials = trialsResult ?? [];

  const memberIds = new Set(members.map((m) => m.id));
  const enrollable = allParticipants?.data.filter((p) => !memberIds.has(p.id)) ?? [];

  return (
    <div className="max-w-3xl">
      <Link href="/portal/kelas" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke daftar kelas
      </Link>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">{trainingClass.name}</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">
            {trainingClass.active_member_count}/{trainingClass.capacity_max} anggota · Sisa kuota {trainingClass.quota_remaining}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <StatusBadge status={trainingClass.status} />
          {staff && (
            <Link href={`/portal/kelas/${id}/edit`} className="text-sm font-semibold text-(--color-court-600) hover:underline">
              Edit
            </Link>
          )}
        </div>
      </div>

      {staff && (
        <Card className="mt-6">
          <CardHeader title="Daftarkan Peserta" />
          <CardBody>
            {enrollable.length === 0 ? (
              <p className="text-sm text-(--color-ink-500)">Tidak ada peserta aktif yang bisa didaftarkan.</p>
            ) : (
              <form action={enrollParticipantAction.bind(null, classId)} className="flex flex-wrap items-end gap-3">
                <div className="w-72">
                  <Select label="Pilih peserta" name="participant_id" required defaultValue="">
                    <option value="" disabled>
                      Pilih peserta
                    </option>
                    {enrollable.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.full_name} ({p.registration_no})
                      </option>
                    ))}
                  </Select>
                </div>
                <Button type="submit">Daftarkan</Button>
              </form>
            )}
          </CardBody>
        </Card>
      )}

      {staff && (
        <Card className="mt-6">
          <CardHeader title="Kelas Percobaan (Trial)" description="Booking sesi percobaan sebelum peserta mendaftar penuh." />
          <CardBody>
            {enrollable.length === 0 ? (
              <p className="text-sm text-(--color-ink-500)">Tidak ada peserta aktif yang bisa dijadwalkan trial.</p>
            ) : (
              <form action={bookTrialClassAction.bind(null, classId)} className="flex flex-wrap items-end gap-3">
                <div className="w-72">
                  <Select label="Pilih peserta" name="participant_id" required defaultValue="">
                    <option value="" disabled>
                      Pilih peserta
                    </option>
                    {enrollable.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.full_name} ({p.registration_no})
                      </option>
                    ))}
                  </Select>
                </div>
                <Input label="Tanggal trial" name="trial_date" type="date" required />
                <Button type="submit">Booking Trial</Button>
              </form>
            )}

            {trials.length > 0 && (
              <ul className="mt-4 flex flex-col divide-y divide-(--color-ink-900)/8">
                {trials.map((t) => (
                  <li key={t.id} className="flex items-center justify-between py-3 text-sm first:pt-0 last:pb-0">
                    <div>
                      <p className="font-semibold text-(--color-ink-900)">{t.participant_name}</p>
                      <p className="text-(--color-ink-500)">{formatDate(t.trial_date)}</p>
                    </div>
                    {t.converted_to_member ? (
                      <StatusBadge status="converted" />
                    ) : (
                      <form action={convertTrialClassAction.bind(null, classId, t.id)}>
                        <Button type="submit" variant="outline" size="sm">
                          Konversi ke Anggota
                        </Button>
                      </form>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </CardBody>
        </Card>
      )}

      <Card className="mt-6">
        <CardHeader title="Anggota Kelas" />
        <CardBody>
          {members.length === 0 ? (
            <EmptyState title="Belum ada anggota" />
          ) : (
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {members.map((m) => (
                <li key={m.id} className="flex items-center justify-between py-3 text-sm first:pt-0 last:pb-0">
                  <div>
                    <p className="font-semibold text-(--color-ink-900)">{m.full_name}</p>
                    <p className="font-mono text-xs text-(--color-ink-500)">{m.registration_no}</p>
                  </div>
                  <div className="flex items-center gap-3">
                    <StatusBadge status={m.status} />
                    {staff && (
                      <form action={removeMemberAction.bind(null, classId, m.id)}>
                        <button type="submit" className="text-sm font-semibold text-(--color-crit) hover:underline">
                          Keluarkan
                        </button>
                      </form>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      {waitingList.length > 0 && (
        <Card className="mt-6">
          <CardHeader title="Waiting List" />
          <CardBody>
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {waitingList.map((w) => (
                <li key={w.participant_id} className="flex items-center justify-between py-3 text-sm first:pt-0 last:pb-0">
                  <p className="font-semibold text-(--color-ink-900)">{w.participant_name}</p>
                  <StatusBadge status={w.status} />
                </li>
              ))}
            </ul>
          </CardBody>
        </Card>
      )}
    </div>
  );
}
