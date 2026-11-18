"use client";

import Link from "next/link";
import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/components/providers";

export default function RegisterPage() {
  const { register } = useAuth();
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");
    try {
      await register(name, email, password, confirmation);
      router.replace("/orders");
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Pendaftaran gagal.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto max-w-md rounded-[2rem] bg-white p-7 shadow-card sm:p-10">
      <p className="text-xs font-bold uppercase tracking-[0.2em] text-forest">Buat akun</p>
      <h1 className="mt-3 text-3xl font-black">Mulai belanja di Abangkuh.</h1>
      <form className="mt-7 space-y-4" onSubmit={submit}>
        <label className="block text-sm font-semibold" htmlFor="name">
          Nama
          <input className="focus-ring mt-2 w-full rounded-xl border border-ink/15 px-4 py-3 font-normal" id="name" maxLength={255} onChange={(event) => setName(event.target.value)} required value={name} />
        </label>
        <label className="block text-sm font-semibold" htmlFor="email">
          Email
          <input autoComplete="email" className="focus-ring mt-2 w-full rounded-xl border border-ink/15 px-4 py-3 font-normal" id="email" onChange={(event) => setEmail(event.target.value)} required type="email" value={email} />
        </label>
        <label className="block text-sm font-semibold" htmlFor="password">
          Kata sandi
          <input autoComplete="new-password" className="focus-ring mt-2 w-full rounded-xl border border-ink/15 px-4 py-3 font-normal" id="password" onChange={(event) => setPassword(event.target.value)} required type="password" value={password} />
        </label>
        <p className="-mt-2 text-xs text-ink/50">Minimal 12 karakter, huruf besar/kecil, angka, dan simbol.</p>
        <label className="block text-sm font-semibold" htmlFor="password_confirmation">
          Ulangi kata sandi
          <input autoComplete="new-password" className="focus-ring mt-2 w-full rounded-xl border border-ink/15 px-4 py-3 font-normal" id="password_confirmation" onChange={(event) => setConfirmation(event.target.value)} required type="password" value={confirmation} />
        </label>
        {error && <p className="rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{error}</p>}
        <button className="focus-ring w-full rounded-full bg-forest px-5 py-3 font-bold text-white disabled:opacity-50" disabled={loading} type="submit">
          {loading ? "Membuat akun..." : "Daftar"}
        </button>
      </form>
      <p className="mt-5 text-sm text-ink/55">Sudah punya akun? <Link className="font-bold text-forest underline" href="/login">Masuk</Link>.</p>
    </section>
  );
}
