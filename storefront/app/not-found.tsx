import Link from "next/link";

export default function NotFound() {
  return (
    <div className="mx-auto max-w-xl py-20 text-center">
      <p className="text-sm font-bold uppercase tracking-widest text-forest">404</p>
      <h1 className="mt-3 text-3xl font-black">Halaman tidak ditemukan</h1>
      <Link className="focus-ring mt-6 inline-block rounded-full bg-lime px-5 py-3 text-sm font-bold" href="/">
        Kembali ke katalog
      </Link>
    </div>
  );
}
