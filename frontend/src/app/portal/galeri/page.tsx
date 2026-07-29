import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { Pagination } from "@/components/ui/Pagination";

export const metadata = { title: "Galeri" };

type GalleryMedia = { id: number; type: string; url: string };
type Gallery = { id: number; title: string | null; status: string; visibility: string; media: GalleryMedia[] };

export default async function GaleriListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const canUpload = hasRole(session.user.roles, ROLES.COACH, ROLES.SUPER_ADMIN);

  const galleries = await serverApiPaginated<Gallery>(`/galleries?page=${page}&per_page=12`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Galeri</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Dokumentasi kegiatan latihan.</p>
        </div>
        {canUpload && (
          <Link href="/portal/galeri/baru">
            <Button>Unggah Galeri</Button>
          </Link>
        )}
      </div>

      {galleries.data.length === 0 ? (
        <div className="mt-6">
          <EmptyState title="Belum ada galeri" />
        </div>
      ) : (
        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {galleries.data.map((gallery) => (
            <Card key={gallery.id}>
              <div className="grid grid-cols-2 gap-0.5 overflow-hidden rounded-t-2xl bg-(--color-ink-900)/5">
                {gallery.media.slice(0, 4).map((m) => (
                  <div key={m.id} className="aspect-square bg-(--color-ink-900)/10">
                    {m.type === "image" && (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={m.url} alt="" className="h-full w-full object-cover" />
                    )}
                  </div>
                ))}
                {gallery.media.length === 0 && <div className="col-span-2 flex h-32 items-center justify-center text-xs text-(--color-ink-500)">Belum ada media</div>}
              </div>
              <CardHeader
                title={gallery.title ?? "Tanpa judul"}
                action={<StatusBadge status={gallery.status} />}
                className="border-t-0"
              />
              <div className="px-5 pb-4">
                <Link href={`/portal/galeri/${gallery.id}`} className="text-sm font-semibold text-(--color-court-600) hover:underline">
                  Lihat Detail
                </Link>
              </div>
            </Card>
          ))}
        </div>
      )}

      <div className="mt-6">
        <Pagination currentPage={galleries.meta.current_page} lastPage={galleries.meta.last_page} makeHref={(p) => `/portal/galeri?page=${p}`} />
      </div>
    </div>
  );
}
