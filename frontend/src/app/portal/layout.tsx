import { verifySession } from "@/lib/dal";
import { visibleNavSections } from "@/lib/nav";
import { Sidebar } from "@/components/portal/Sidebar";
import { TopBar } from "@/components/portal/TopBar";

export default async function PortalLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const session = await verifySession();
  const sections = visibleNavSections(session.user.roles);

  return (
    <div className="flex min-h-full bg-(--color-paper)">
      <Sidebar sections={sections} />
      <div className="flex min-h-full flex-1 flex-col">
        <TopBar user={session.user} />
        <main className="flex-1 px-4 py-6 md:px-8 md:py-8">{children}</main>
      </div>
    </div>
  );
}
