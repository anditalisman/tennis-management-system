import Link from "next/link";

export default function AuthLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <div className="flex min-h-full flex-col bg-(--color-court-700)">
      <div className="flex flex-1 items-center justify-center px-4 py-12">
        <div className="w-full max-w-md">
          <Link href="/" className="mb-8 flex justify-center font-display text-xl font-extrabold text-white">
            Zul Tennis Clinic
          </Link>
          <div className="rounded-2xl bg-(--color-paper-raised) p-8 shadow-xl">{children}</div>
        </div>
      </div>
    </div>
  );
}
