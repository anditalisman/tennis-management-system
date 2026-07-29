import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { GalleryUploadForm } from "@/components/portal/GalleryUploadForm";

export const metadata = { title: "Unggah Galeri" };

export default async function NewGalleryPage() {
  await verifySession();
  const classes = await serverApiPaginated<{ id: number; name: string }>("/classes?per_page=100");

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Unggah Galeri</h1>
      <Card className="mt-6">
        <CardHeader title="Dokumentasi Kegiatan" />
        <CardBody>
          <GalleryUploadForm classes={classes.data} />
        </CardBody>
      </Card>
    </div>
  );
}
