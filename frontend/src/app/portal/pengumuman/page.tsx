import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApi } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatDate } from "@/lib/format";
import { deleteAnnouncementAction } from "@/lib/actions/announcements";

export const metadata = { title: "Pengumuman" };

type Announcement = {
  id: number;
  title: string;
  body: string;
  status: string;
  publish_at: string | null;
  created_by: string | null;
};

export default async function PengumumanPage() {
  const session = await verifySession();
  const canManage = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.ADMINISTRATOR);

  const announcements = await serverApi<Announcement[]>("/announcements");

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Pengumuman</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Informasi terbaru dari Zul Tennis Clinic.</p>
        </div>
        {canManage && (
          <Link href="/portal/pengumuman/baru">
            <Button>Buat Pengumuman</Button>
          </Link>
        )}
      </div>

      {announcements.length === 0 ? (
        <div className="mt-6">
          <EmptyState title="Belum ada pengumuman" />
        </div>
      ) : (
        <div className="mt-6 flex flex-col gap-4">
          {announcements.map((a) => (
            <Card key={a.id}>
              <CardBody>
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <h2 className="font-display text-lg font-bold text-(--color-ink-900)">{a.title}</h2>
                    <p className="mt-1 text-xs text-(--color-ink-500)">
                      {formatDate(a.publish_at)} {a.created_by ? `· ${a.created_by}` : ""}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge tone={a.status === "published" ? "good" : "neutral"}>{a.status === "published" ? "Terbit" : "Draf"}</Badge>
                    {canManage && (
                      <form action={deleteAnnouncementAction.bind(null, a.id)}>
                        <button type="submit" className="text-sm font-semibold text-(--color-crit) hover:underline">
                          Hapus
                        </button>
                      </form>
                    )}
                  </div>
                </div>
                <p className="mt-3 whitespace-pre-line text-sm text-(--color-ink-700)">{a.body}</p>
              </CardBody>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
