import { verifySession } from "@/lib/dal";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { UserForm } from "@/components/portal/UserForm";

export const metadata = { title: "Tambah Pengguna" };

export default async function NewUserPage() {
  await verifySession();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Pengguna</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Pengguna" />
        <CardBody>
          <UserForm />
        </CardBody>
      </Card>
    </div>
  );
}
