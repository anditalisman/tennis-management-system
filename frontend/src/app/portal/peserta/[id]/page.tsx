import Link from "next/link";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { isStaff } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { StatusBadge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatDate } from "@/lib/format";
import { verifyParticipantAction } from "@/lib/actions/participants";
import { generateReferralCodeAction } from "@/lib/actions/referrals";
import { ASPECT_LABELS } from "@/lib/evaluation-aspects";

export const metadata = { title: "Detail Peserta" };

type Participant = {
  id: string;
  registration_no: string;
  full_name: string;
  birth_date: string | null;
  gender: string | null;
  skill_level: string | null;
  phone: string | null;
  address: string | null;
  status: string;
  guardians: { id: string; name: string; relation: string; is_primary: boolean }[];
};

type ParticipantPackage = {
  id: number;
  package_name: string | null;
  sessions_remaining: number;
  valid_until: string | null;
  status: string;
};

type Referral = { id: number; code: string; referred_participant_name: string | null; reward_status: string };

type EvaluationDetail = { aspect: string; score: number; note: string | null };
type Evaluation = {
  id: number;
  coach_name: string | null;
  evaluation_date: string | null;
  next_target: string | null;
  details: EvaluationDetail[];
};

export default async function PesertaDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { id } = await params;
  const { error } = await searchParams;

  const participant = await serverApiOrNull<Participant>(`/participants/${id}`);
  if (!participant) notFound();

  const packages = (await serverApiOrNull<ParticipantPackage[]>(`/participants/${id}/packages`)) ?? [];
  const evaluations = (await serverApiOrNull<Evaluation[]>(`/participants/${id}/evaluations`)) ?? [];
  const referrals = (await serverApiOrNull<Referral[]>(`/participants/${id}/referrals`)) ?? [];
  const staff = isStaff(session.user.roles);
  const generateReferral = generateReferralCodeAction.bind(null, id);

  const approve = verifyParticipantAction.bind(null, id, "approve");
  const reject = verifyParticipantAction.bind(null, id, "reject");

  return (
    <div className="max-w-4xl">
      <Link href="/portal/peserta" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke daftar peserta
      </Link>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">{participant.full_name}</h1>
          <p className="mt-1 font-mono text-sm text-(--color-ink-500)">{participant.registration_no}</p>
        </div>
        <StatusBadge status={participant.status} />
      </div>

      {staff && participant.status === "pending_verification" && (
        <Card className="mt-6 border-(--color-warn)/30">
          <CardBody className="flex flex-wrap items-center justify-between gap-4">
            <p className="text-sm text-(--color-ink-700)">Pendaftaran ini menunggu verifikasi Anda.</p>
            <div className="flex gap-2">
              <form action={reject}>
                <Button type="submit" variant="outline">
                  Tolak
                </Button>
              </form>
              <form action={approve}>
                <Button type="submit">Setujui</Button>
              </form>
            </div>
          </CardBody>
        </Card>
      )}

      <Card className="mt-6">
        <CardHeader
          title="Data Peserta"
          action={
            staff ? (
              <Link href={`/portal/peserta/${id}/edit`} className="text-sm font-semibold text-(--color-court-600) hover:underline">
                Edit
              </Link>
            ) : undefined
          }
        />
        <CardBody className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Tanggal Lahir</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{formatDate(participant.birth_date)}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Jenis Kelamin</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">
              {participant.gender === "male" ? "Laki-laki" : participant.gender === "female" ? "Perempuan" : "-"}
            </p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Level</p>
            <p className="mt-1 text-sm capitalize text-(--color-ink-900)">{participant.skill_level ?? "-"}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Telepon</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{participant.phone ?? "-"}</p>
          </div>
          <div className="sm:col-span-2">
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Alamat</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{participant.address ?? "-"}</p>
          </div>
        </CardBody>
      </Card>

      <Card className="mt-6">
        <CardHeader title="Wali" />
        <CardBody>
          {participant.guardians.length === 0 ? (
            <p className="text-sm text-(--color-ink-500)">Tidak ada wali terhubung.</p>
          ) : (
            <ul className="flex flex-col gap-2">
              {participant.guardians.map((g) => (
                <li key={g.id} className="flex items-center justify-between text-sm">
                  <span className="font-semibold text-(--color-ink-900)">{g.name}</span>
                  <span className="text-(--color-ink-500)">
                    {g.relation}
                    {g.is_primary ? " · Utama" : ""}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      <Card className="mt-6">
        <CardHeader title="Paket Latihan" />
        <CardBody>
          {packages.length === 0 ? (
            <EmptyState title="Belum ada paket dibeli" />
          ) : (
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {packages.map((pkg) => (
                <li key={pkg.id} className="flex items-center justify-between py-3 text-sm first:pt-0 last:pb-0">
                  <div>
                    <p className="font-semibold text-(--color-ink-900)">{pkg.package_name}</p>
                    <p className="text-(--color-ink-500)">
                      Sisa {pkg.sessions_remaining} sesi · Berlaku hingga {formatDate(pkg.valid_until)}
                    </p>
                  </div>
                  <StatusBadge status={pkg.status} />
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      <Card className="mt-6">
        <CardHeader title="Riwayat Evaluasi" />
        <CardBody>
          {evaluations.length === 0 ? (
            <EmptyState title="Belum ada evaluasi" />
          ) : (
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {evaluations.map((ev) => (
                <li key={ev.id} className="py-4 first:pt-0 last:pb-0">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-semibold text-(--color-ink-900)">{formatDate(ev.evaluation_date)}</p>
                    <p className="text-xs text-(--color-ink-500)">Pelatih: {ev.coach_name ?? "-"}</p>
                  </div>
                  <div className="mt-2 flex flex-wrap gap-1.5">
                    {ev.details.map((d) => (
                      <span
                        key={d.aspect}
                        className="rounded-full bg-(--color-ink-900)/6 px-2.5 py-1 text-xs font-medium text-(--color-ink-700)"
                      >
                        {ASPECT_LABELS[d.aspect] ?? d.aspect}: {d.score}/5
                      </span>
                    ))}
                  </div>
                  {ev.next_target && <p className="mt-2 text-sm text-(--color-ink-500)">Target berikutnya: {ev.next_target}</p>}
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      <Card className="mt-6">
        <CardHeader
          title="Kode Referral"
          description="Bagikan kode ini agar peserta baru mendapat manfaat referral."
          action={
            <form action={generateReferral}>
              <Button type="submit" variant="outline" size="sm">
                Buat Kode Baru
              </Button>
            </form>
          }
        />
        <CardBody>
          {referrals.length === 0 ? (
            <EmptyState title="Belum ada kode referral" />
          ) : (
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {referrals.map((r) => (
                <li key={r.id} className="flex items-center justify-between py-3 text-sm first:pt-0 last:pb-0">
                  <div>
                    <span className="font-mono font-semibold text-(--color-ink-900)">{r.code}</span>
                    {r.referred_participant_name && (
                      <span className="ml-2 text-(--color-ink-500)">→ {r.referred_participant_name}</span>
                    )}
                  </div>
                  <StatusBadge status={r.reward_status} />
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>
    </div>
  );
}
