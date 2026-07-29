import Image from "next/image";
import { serverApi } from "@/lib/server-api";
import { EmptyState } from "@/components/ui/Feedback";

export const metadata = { title: "Galeri" };

type GalleryMedia = { id: number; type: string; url: string };
type Gallery = { id: number; title: string | null; media: GalleryMedia[] };

export default async function GaleriPage() {
  const galleries = await serverApi<Gallery[]>("/public/galleries");
  const photos = galleries.flatMap((g) => g.media.map((m) => ({ ...m, title: g.title, galleryId: g.id })));

  return (
    <div className="mx-auto max-w-6xl px-6 py-16">
      <span className="text-xs font-semibold uppercase tracking-widest text-(--color-court-600)">Galeri</span>
      <h1 className="mt-2 font-display text-3xl font-extrabold text-(--color-ink-900) sm:text-4xl">
        Dokumentasi Kegiatan
      </h1>
      <p className="mt-4 max-w-2xl text-(--color-ink-500)">
        Momen latihan, turnamen, dan kegiatan lainnya di Zul Tennis Clinic.
      </p>

      {photos.length === 0 ? (
        <div className="mt-10">
          <EmptyState
            title="Belum ada foto yang dipublikasikan"
            description="Dokumentasi kegiatan akan tampil di sini setelah dimoderasi oleh tim kami."
          />
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
          {photos.map((photo) => (
            <div key={photo.id} className="relative aspect-square overflow-hidden rounded-xl bg-(--color-ink-900)/5">
              {photo.type === "image" ? (
                <Image src={photo.url} alt={photo.title ?? "Dokumentasi"} fill unoptimized className="object-cover" />
              ) : (
                <video src={photo.url} controls className="h-full w-full object-cover" />
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
