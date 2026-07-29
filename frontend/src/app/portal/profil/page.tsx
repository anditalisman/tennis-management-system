import { verifySession } from "@/lib/dal";
import { serverApi } from "@/lib/server-api";
import { ROLE_LABELS } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { formatDateTime } from "@/lib/format";
import { logoutAction } from "@/lib/actions/auth";

export const metadata = { title: "Profil Saya" };

type Me = {
  name: string;
  email: string;
  phone: string | null;
  roles: string[];
  last_login_at: string | null;
};

export default async function ProfilPage() {
  await verifySession();
  const me = await serverApi<Me>("/me");

  return (
    <div className="max-w-lg">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Profil Saya</h1>

      <Card className="mt-6">
        <CardHeader title="Informasi Akun" />
        <CardBody className="flex flex-col gap-4">
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Nama</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{me.name}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Email</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{me.email}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Telepon</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{me.phone ?? "-"}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Peran</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{me.roles.map((r) => ROLE_LABELS[r] ?? r).join(", ")}</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase text-(--color-ink-500)">Login Terakhir</p>
            <p className="mt-1 text-sm text-(--color-ink-900)">{formatDateTime(me.last_login_at)}</p>
          </div>
        </CardBody>
      </Card>

      <form action={logoutAction} className="mt-6">
        <Button type="submit" variant="outline">
          Keluar dari Akun
        </Button>
      </form>
    </div>
  );
}
