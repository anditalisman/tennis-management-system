import { VerifyWhatsappForm } from "./VerifyWhatsappForm";

export const metadata = { title: "Verifikasi WhatsApp" };

export default async function VerifyWhatsappPage({
  searchParams,
}: {
  searchParams: Promise<{ email?: string }>;
}) {
  const { email } = await searchParams;

  return <VerifyWhatsappForm email={email ?? ""} />;
}
