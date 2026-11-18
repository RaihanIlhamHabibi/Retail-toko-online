"use client";

import Link from "next/link";
import { useState } from "react";
import { useAuth, useCart } from "@/components/providers";

export function Header() {
  const { user, logout } = useAuth();
  const { count } = useCart();
  const [error, setError] = useState("");

  async function signOut() {
    setError("");
    try {
      await logout();
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Tidak dapat keluar.");
    }
  }

  return (
    <header className="sticky top-0 z-20 border-b border-ink/10 bg-cream/90 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
        <Link href="/" className="focus-ring flex items-center gap-2 rounded-xl">
          <span className="grid h-10 w-10 place-items-center rounded-2xl bg-forest text-lg font-black text-lime">
            A
          </span>
          <span>
            <span className="block text-lg font-extrabold tracking-tight">
              abangkuh
            </span>
            <span className="block text-[10px] font-bold uppercase tracking-[0.2em] text-ink/50">
              curated daily goods
            </span>
          </span>
        </Link>
        <nav className="flex items-center gap-2 text-sm font-semibold sm:gap-5">
          <Link className="focus-ring rounded-lg px-2 py-2 hover:text-forest" href="/">
            Katalog
          </Link>
          {user ? (
            <>
              <Link
                className="focus-ring hidden rounded-lg px-2 py-2 hover:text-forest sm:inline"
                href="/orders"
              >
                Pesanan
              </Link>
              <button
                className="focus-ring rounded-lg px-2 py-2 hover:text-forest"
                onClick={() => void signOut()}
                type="button"
              >
                Keluar
              </button>
            </>
          ) : (
            <Link
              className="focus-ring rounded-lg px-2 py-2 hover:text-forest"
              href="/login"
            >
              Masuk
            </Link>
          )}
          <Link
            className="focus-ring rounded-full bg-lime px-4 py-2 text-ink transition hover:bg-[#b6da6e]"
            href="/cart"
          >
            Tas <span className="ml-1 rounded-full bg-ink px-2 py-0.5 text-xs text-white">{count}</span>
          </Link>
        </nav>
      </div>
      {error && <p className="mx-auto max-w-7xl px-8 pb-3 text-right text-xs text-red-700" role="alert">{error}</p>}
    </header>
  );
}
