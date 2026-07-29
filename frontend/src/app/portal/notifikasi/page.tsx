import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardHeader } from "@/components/ui/Card";
import { Pagination } from "@/components/ui/Pagination";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatDateTime } from "@/lib/format";
import { markNotificationReadAction } from "@/lib/actions/notifications";

export const metadata = { title: "Notifikasi" };

type Notification = {
  id: number;
  channel: string;
  title: string;
  body: string;
  status: string;
  read_at: string | null;
  created_at: string;
};

export default async function NotifikasiPage({ searchParams }: { searchParams: Promise<{ page?: string; unread?: string }> }) {
  await verifySession();
  const { page = "1", unread } = await searchParams;
  const unreadOnly = unread === "1";

  const notifications = await serverApiPaginated<Notification>(
    `/notifications?page=${page}&per_page=20${unreadOnly ? "&unread_only=1" : ""}`,
  );

  return (
    <div className="max-w-2xl">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Notifikasi</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Pemberitahuan untuk akun Anda.</p>
        </div>
        <div className="flex gap-2">
          <Link href="/portal/notifikasi">
            <Button variant={unreadOnly ? "ghost" : "primary"} size="sm">
              Semua
            </Button>
          </Link>
          <Link href="/portal/notifikasi?unread=1">
            <Button variant={unreadOnly ? "primary" : "ghost"} size="sm">
              Belum dibaca
            </Button>
          </Link>
        </div>
      </div>

      <Card className="mt-6">
        {notifications.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title={unreadOnly ? "Tidak ada notifikasi belum dibaca" : "Belum ada notifikasi"} />
          </div>
        ) : (
          <>
            <CardHeader title={`${notifications.meta.total} Notifikasi`} />
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8 p-4">
              {notifications.data.map((n) => (
                <li key={n.id} className="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                  <div>
                    <div className="flex items-center gap-2">
                      <p className="text-sm font-semibold text-(--color-ink-900)">{n.title}</p>
                      {!n.read_at && <Badge tone="court">Baru</Badge>}
                    </div>
                    <p className="mt-1 text-sm text-(--color-ink-700)">{n.body}</p>
                    <p className="mt-1 text-xs text-(--color-ink-500)">{formatDateTime(n.created_at)}</p>
                  </div>
                  {!n.read_at && (
                    <form action={markNotificationReadAction.bind(null, n.id)}>
                      <Button type="submit" variant="outline" size="sm">
                        Tandai dibaca
                      </Button>
                    </form>
                  )}
                </li>
              ))}
            </ul>
            <div className="px-4 pb-4">
              <Pagination
                currentPage={notifications.meta.current_page}
                lastPage={notifications.meta.last_page}
                makeHref={(p) => `/portal/notifikasi?page=${p}${unreadOnly ? "&unread=1" : ""}`}
              />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
