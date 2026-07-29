import type { Metadata } from "next";
import { Archivo, Manrope } from "next/font/google";
import "./globals.css";

const display = Archivo({
  variable: "--font-display",
  subsets: ["latin"],
  weight: ["700", "800", "900"],
});

const body = Manrope({
  variable: "--font-body",
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
});

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"),
  title: {
    default: "Zul Tennis Clinic — Train Better, Play Stronger",
    template: "%s · Zul Tennis Clinic",
  },
  description:
    "Akademi tenis terkelola secara profesional: program latihan, jadwal, pelatih bersertifikat, dan pendaftaran online di Zul Tennis Clinic.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="id"
      className={`${display.variable} ${body.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col font-sans bg-(--color-paper) text-(--color-ink-900)">
        {children}
      </body>
    </html>
  );
}
