import type { Metadata } from "next";
import { Header } from "@/components/header";
import { Providers } from "@/components/providers";
import "./globals.css";

export const metadata: Metadata = {
  title: "Abangkuh — pilihan baik setiap hari",
  description: "Jelajahi katalog dan kelola pesanan Abangkuh.",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="id">
      <body>
        <Providers>
          <Header />
          <main className="mx-auto min-h-[calc(100vh-150px)] max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
            {children}
          </main>
          <footer className="border-t border-ink/10 px-5 py-8 text-center text-xs text-ink/45">
            © {new Date().getFullYear()} Abangkuh. Dibuat untuk kebutuhan harian.
          </footer>
        </Providers>
      </body>
    </html>
  );
}
