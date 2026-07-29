import Link from "next/link";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { hasRole, isStaff, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { StatusBadge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { publishGalleryAction, deleteGalleryAction } from "@/lib/actions/galleries";

export const metadata = { title: "Detail Galeri" };

type GalleryMedia = { id: number; type: string; url: string };
type Gallery = {
  id: number;
  title: string | null;
  status: string;
  visibility: string;
  uploaded_by: string | null;
  media: GalleryMedia[];
};

export default async function GaleriDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { id } = await params;
  const { error } = await searchParams;
  const galleryId = Number(id);

  const gallery = await serverApiOrNull<Gallery>(`/galleries/${id}`);
  if (!gallery) notFound();

  const canModerate = isStaff(session.user.roles) || hasRole(session.user.roles, ROLES.COACH);

  return (
    <div className="max-w-3xl">
      <Link href="/portal/galeri" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke galeri
      </Link>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">{gallery.title ?? "Tanpa judul"}</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Diunggah oleh {gallery.uploaded_by ?? "-"}</p>
        </div>
        <StatusBadge status={gallery.status} />
      </div>

      {canModerate && (
        <div className="mt-4 flex flex-wrap gap-2">
          {gallery.status !== "approved" && (
            <form action={publishGalleryAction.bind(null, galleryId)}>
              <Button type="submit">Publikasikan</Button>
            </form>
          )}
          <form action={deleteGalleryAction.bind(null, galleryId)}>
            <Button type="submit" variant="danger">
              Hapus Galeri
            </Button>
          </form>
        </div>
      )}

      <Card className="mt-6">
        <CardHeader title={`${gallery.media.length} Media`} />
        <CardBody>
          {gallery.media.length === 0 ? (
            <EmptyState title="Belum ada media" />
          ) : (
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              {gallery.media.map((m) => (
                <div key={m.id} className="aspect-square overflow-hidden rounded-xl bg-(--color-ink-900)/5">
                  {m.type === "image" ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={m.url} alt="" className="h-full w-full object-cover" />
                  ) : (
                    <video src={m.url} controls className="h-full w-full object-cover" />
                  )}
                </div>
              ))}
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  );
}
