import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull, serverApiPaginated } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { PackageCheckoutForm } from "@/components/portal/PackageCheckoutForm";
import { formatCurrency } from "@/lib/format";

export const metadata = { title: "Daftar Paket" };

type Package = { id: number; name: string; session_count: number; price: number; status: string };
type Participant = { id: string; full_name: string };

export default async function PackageCheckoutPage({ params }: { params: Promise<{ id: string }> }) {
  const session = await verifySession();
  const { id } = await params;

  const pkg = await serverApiOrNull<Package>(`/packages/${id}`);
  if (!pkg || pkg.status !== "active") notFound();

  const isGuardian = hasRole(session.user.roles, ROLES.GUARDIAN);
  const children = isGuardian ? (await serverApiPaginated<Participant>("/participants?per_page=100")).data : undefined;

  return (
    <div className="max-w-xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Daftar Paket</h1>
      <Card className="mt-6">
        <CardHeader title={pkg.name} description={`${pkg.session_count} sesi · ${formatCurrency(pkg.price)}`} />
        <CardBody>
          <PackageCheckoutForm packageId={pkg.id} guardianChildren={children} />
        </CardBody>
      </Card>
    </div>
  );
}
