"use client";

import Link from "next/link";
import { useState, type FormEvent } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/components/providers";

export default function LoginPage() {
  const { login } = useAuth();
  const router = useRouter();
  const search = useSearchParams();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const redirect = search.get("redirect");
  const returnTo = redirect?.startsWith("/") && !redirect.startsWith("//") ? redirect : "/orders";

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");
    try {
      await login(email, password);
      router.replace(returnTo);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Login gagal.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto max-w-md rounded-[2rem] bg-white p-7 shadow-card sm:p-10">
      <p className="text-xs font-bold uppercase tracking-[0.2em] text-forest">Akun abangkuh</p>
      <h1 className="mt-3 text-3xl font-black">Selamat datang kembali.</h1>
      <p className="mt-2 text-sm text-ink/55">Masuk untuk melihat pesanan dan melanjutkan checkout.</p>
      <form className="mt-7 space-y-4" onSubmit={submit}>
        <label className="block text-sm font-semibold" htmlFor="email">
          Email
          <input
            autoComplete="email"
            className="focus-ring mt-2 w-full rounded-xl border border-ink/15 px-4 py-3 font-normal"
            id="email"
            onChange={(event) => setEmail(event.target.value)}
            required
            type="email"
            value={email}
          />
        </label>
        <label className="block text-sm font-semibold" htmlFor="password">
          Kata sandi
          <input
            autoComplete="current-password"
            className="focus-ring mt-2 w-full rounded-xl border border-ink/15 px-4 py-3 font-normal"
            id="password"
            onChange={(event) => setPassword(event.target.value)}
            required
            type="password"
            value={password}
          />
        </label>
        {error && <p className="rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{error}</p>}
        <button className="focus-ring w-full rounded-full bg-forest px-5 py-3 font-bold text-white disabled:opacity-50" disabled={loading} type="submit">
          {loading ? "Memproses..." : "Masuk"}
        </button>
      </form>
      <p className="mt-5 text-xs leading-5 text-ink/50">
        Belum punya akun? <Link className="font-bold text-forest underline" href="/register">Daftar di sini</Link>.
      </p>
      <Link className="mt-5 inline-block text-sm font-bold text-forest underline" href="/">Kembali ke katalog</Link>
    </section>
  );
}
