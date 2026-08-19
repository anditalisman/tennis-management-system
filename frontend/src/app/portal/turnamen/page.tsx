import { verifySession } from "@/lib/dal";
import { serverApi } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Button } from "@/components/ui/Button";
import { TournamentBracket } from "@/components/TournamentBracket";
import {
  clearTournamentDrawAction,
  generateTournamentDrawAction,
  moveTournamentParticipantCategoryAction,
} from "@/lib/actions/tournament";

export const metadata = { title: "Turnamen Kemerdekaan" };

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
const OTHER_CATEGORY: Record<string, "beginner" | "upper_beginner"> = {
  beginner: "upper_beginner",
  upper_beginner: "beginner",
};

export default async function TurnamenAdminPage({
  searchParams,
}: {
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { error } = await searchParams;
  const canManage = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.ADMINISTRATOR);

  const [participants, matches] = await Promise.all([
    serverApi<TournamentParticipant[]>("/public/tournament/participants"),
    serverApi<TournamentMatch[]>("/public/tournament/matches"),
  ]);

  const beginnerCount = participants.filter((p) => p.category === "beginner").length;
  const upperBeginnerCount = participants.length - beginnerCount;

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Turnamen Kemerdekaan</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">
            {participants.length} peserta terdaftar · {beginnerCount} Beginner · {upperBeginnerCount} Upper Beginner
          </p>
        </div>
        {canManage && (
          <div className="flex flex-wrap gap-2">
            {matches.length > 0 && (
              <form action={clearTournamentDrawAction}>
                <Button type="submit" variant="outline">
                  Hapus Bagan
                </Button>
              </form>
            )}
            <form action={generateTournamentDrawAction}>
              <Button type="submit" disabled={participants.length < 2}>
                {matches.length > 0 ? "Undi Ulang" : "Acak Pasangan & Undi Bagan"}
              </Button>
            </form>
          </div>
        )}
      </div>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
        <div className="lg:col-span-2">
          <Card>
            <CardHeader title="Peserta" />
            <CardBody>
              {participants.length === 0 ? (
                <EmptyState title="Belum ada peserta" description="Bagikan tautan pendaftaran ke calon peserta." />
              ) : (
                <Table>
                  <Thead>
                    <Tr>
                      <Th>Nama</Th>
                      <Th>Kategori</Th>
                      {canManage && <Th>Aksi</Th>}
                    </Tr>
                  </Thead>
                  <Tbody>
                    {participants.map((p) => (
                      <Tr key={p.id}>
                        <Td>
                          {p.full_name} <span className="text-(--color-ink-500)">({p.nickname})</span>
                        </Td>
                        <Td>
                          <Badge tone={p.category === "beginner" ? "info" : "court"}>{CATEGORY_LABELS[p.category]}</Badge>
                        </Td>
                        {canManage && (
                          <Td>
                            <form
                              action={moveTournamentParticipantCategoryAction.bind(null, p.id, OTHER_CATEGORY[p.category])}
                            >
                              <button type="submit" className="text-xs font-semibold text-(--color-court-600) hover:underline">
                                Pindah ke {CATEGORY_LABELS[OTHER_CATEGORY[p.category]]}
                              </button>
                            </form>
                          </Td>
                        )}
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              )}
            </CardBody>
          </Card>
        </div>

        <div className="lg:col-span-3">
          <Card>
            <CardHeader
              title="Bagan Babak 1 (Order of Play)"
              description="Sistem Gugur — tim (Beginner + Upper Beginner) melawan tim lain."
            />
            <CardBody>
              {matches.length === 0 ? (
                <EmptyState
                  title="Bagan belum diundi"
                  description="Klik tombol di atas setelah semua peserta mendaftar."
                />
              ) : (
                <TournamentBracket matches={matches} />
              )}
            </CardBody>
          </Card>
        </div>
      </div>
    </div>
  );
}
