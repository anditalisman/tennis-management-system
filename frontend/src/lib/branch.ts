import "server-only";
import { serverApi } from "@/lib/server-api";

// This deployment operates a single branch — forms no longer ask staff/guests
// to pick one. Uses the public endpoint (no permission requirement) so it
// works for every role, including roles without branches.view.
export async function getDefaultBranchId(): Promise<number | undefined> {
  const branches = await serverApi<{ id: number }[]>("/public/branches");
  return branches[0]?.id;
}
