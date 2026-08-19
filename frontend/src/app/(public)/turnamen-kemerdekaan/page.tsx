import { serverApi } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { TournamentBracket } from "@/components/TournamentBracket";
import { TournamentRegistrationForm } from "./TournamentRegistrationForm";

export const metadata = { title: "Mini Turnamen Kemerdekaan" };

type TournamentParticipant = {
  id: number;
  full_name: string;
  nickname: string;
  category: "beginner" | "upper_beginner";
};

type TournamentTeam = {
  id: number;
  participant_one: TournamentParticipant | null;
  participant_two: TournamentParticipant | null;
};

type TournamentMatch = {
  id: number;
  round: number;
  position: number;
  team_one: TournamentTeam | null;
  team_two: TournamentTeam | null;
};

const CATEGORY_LABELS: Record<string, string> = { beginner: "Beginner", upper_beginner: "Upper Beginner" };

export default async function TurnamenKemerdekaanPage({
  searchParams,
}: {
  searchParams: Promise<{ terdaftar?: string }>;
}) {
  const { terdaftar } = await searchParams;

  const [participants, matches] = await Promise.all([
    serverApi<TournamentParticipant[]>("/public/tournament/participants"),
    serverApi<TournamentMatch[]>("/public/tournament/matches"),
  ]);

  return (
    <div className="mx-auto max-w-5xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">
        HUT Kemerdekaan RI
      </span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Mini Turnamen Kemerdekaan
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Turnamen persahabatan dalam rangka memperingati Hari Kemerdekaan Republik Indonesia. Setiap peserta kategori
        Beginner akan berpasangan (satu tim) dengan peserta kategori Upper Beginner, lalu tiap pasangan akan
        bertanding melawan pasangan lain. Format pertandingan: Sistem Gugur (Knockout).
      </p>

      <div className="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-5">
        <div className="lg:col-span-2">
          <Card>
            <CardHeader title="Formulir Pendaftaran" description="Isi data diri untuk mengikuti turnamen." />
            <CardBody>
              {terdaftar === "1" && (
                <div className="mb-4">
                  <Alert tone="good">Pendaftaran berhasil! Nama Anda sudah tercatat di daftar peserta.</Alert>
                </div>
              )}
              <TournamentRegistrationForm />
            </CardBody>
          </Card>
        </div>

        <div className="lg:col-span-3">
          <Card>
            <CardHeader
              title="Peserta Terdaftar"
              description={`${participants.length} peserta telah mendaftar.`}
            />
            <CardBody>
              {participants.length === 0 ? (
                <EmptyState title="Belum ada peserta" description="Jadilah yang pertama mendaftar!" />
              ) : (
                <Table>
                  <Thead>
                    <Tr>
                      <Th>Nama</Th>
                      <Th>Kategori</Th>
                    </Tr>
                  </Thead>
                  <Tbody>
                    {participants.map((p) => (
                      <Tr key={p.id}>
                        <Td>
                          {p.full_name} <span className="text-(--color-ink-500)">({p.nickname})</span>
                        </Td>
                        <Td>
                          <Badge tone={p.category === "beginner" ? "info" : "court"}>
                            {CATEGORY_LABELS[p.category]}
                          </Badge>
                        </Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              )}
            </CardBody>
          </Card>
        </div>
      </div>

      {matches.length > 0 && (
        <div className="mt-8">
          <Card>
            <CardHeader
              title="Bagan Babak 1 (Order of Play)"
              description="Hasil undian tim (Beginner + Upper Beginner) dan pasangan lawannya — Sistem Gugur."
            />
            <CardBody>
              <TournamentBracket matches={matches} />
            </CardBody>
          </Card>
        </div>
      )}
    </div>
  );
}
