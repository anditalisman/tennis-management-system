import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { UserForm } from "@/components/portal/UserForm";

export const metadata = { title: "Edit Pengguna" };

type User = {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  branch_id: number | null;
  status: string;
  roles: string[];
};

export default async function EditUserPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const user = await serverApiOrNull<User>(`/users/${id}`);
  if (!user) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Pengguna</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Pengguna" />
        <CardBody>
          <UserForm user={user} />
        </CardBody>
      </Card>
    </div>
  );
}
