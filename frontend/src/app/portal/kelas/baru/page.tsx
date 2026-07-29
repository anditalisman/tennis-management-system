import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { getDefaultBranchId } from "@/lib/branch";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { ClassForm } from "@/components/portal/ClassForm";

export const metadata = { title: "Tambah Kelas" };

export default async function NewClassPage() {
  await verifySession();

  const [programs, defaultBranchId, coaches, courts] = await Promise.all([
    serverApiPaginated<{ id: number; name: string }>("/programs?per_page=100"),
    getDefaultBranchId(),
    serverApiPaginated<{ id: number; name: string }>("/coaches?per_page=100"),
    serverApiPaginated<{ id: number; name: string }>("/courts?per_page=100"),
  ]);

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Kelas</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Kelas" />
        <CardBody>
          <ClassForm defaultBranchId={defaultBranchId} programs={programs.data} coaches={coaches.data} courts={courts.data} />
        </CardBody>
      </Card>
    </div>
  );
}
