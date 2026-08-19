import { Badge } from "@/components/ui/Badge";

type BracketParticipant = {
  id: number;
  full_name: string;
  nickname: string;
  category: "beginner" | "upper_beginner";
};

type BracketTeam = {
  id: number;
  participant_one: BracketParticipant | null;
  participant_two: BracketParticipant | null;
};

type BracketMatch = {
  id: number;
  round: number;
  position: number;
  team_one: BracketTeam | null;
  team_two: BracketTeam | null;
};

const CATEGORY_LABELS: Record<string, string> = { beginner: "Beginner", upper_beginner: "Upper Beginner" };

function ParticipantLine({ participant }: { participant: BracketParticipant | null }) {
  if (!participant) {
    return <p className="text-xs italic text-(--color-ink-400)">Tanpa pasangan</p>;
  }
  return (
    <p className="text-sm text-(--color-ink-900)">
      {participant.full_name} <span className="text-(--color-ink-500)">({participant.nickname})</span>{" "}
      <Badge tone={participant.category === "beginner" ? "info" : "court"}>{CATEGORY_LABELS[participant.category]}</Badge>
    </p>
  );
}

function TeamSlot({ team }: { team: BracketTeam | null }) {
  if (!team) {
    return (
      <div className="rounded-lg border border-dashed border-(--color-ink-900)/15 px-3 py-2 text-sm font-semibold text-(--color-good)">
        BYE — Langsung Lolos
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-1 rounded-lg border border-(--color-ink-900)/10 bg-(--color-paper) px-3 py-2">
      <ParticipantLine participant={team.participant_one} />
      <ParticipantLine participant={team.participant_two} />
    </div>
  );
}

/**
 * Round-1 knockout match-ups (Order of Play). Each side is a TEAM — one
 * Beginner partnered with one Upper Beginner — not a lone individual: teams
 * play against other teams, categories never face each other directly.
 */
export function TournamentBracket({ matches }: { matches: BracketMatch[] }) {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
      {matches.map((match) => (
        <div key={match.id} className="rounded-xl border border-(--color-ink-900)/10 p-3">
          <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-(--color-ink-500)">
            Partai #{match.position}
          </p>
          <div className="flex flex-col gap-2">
            <TeamSlot team={match.team_one} />
            <p className="text-center text-xs font-semibold text-(--color-ink-400)">VS</p>
            <TeamSlot team={match.team_two} />
          </div>
        </div>
      ))}
    </div>
  );
}
