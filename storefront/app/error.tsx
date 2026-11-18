"use client";

export default function ErrorPage({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="mx-auto max-w-xl rounded-3xl border border-red-200 bg-white p-8 text-center">
      <h1 className="text-xl font-extrabold">Halaman belum bisa dimuat</h1>
      <p className="mt-2 text-sm text-ink/60">
        Coba lagi. Jika masih gagal, periksa koneksi ke API toko.
      </p>
      <button
        className="focus-ring mt-5 rounded-full bg-forest px-5 py-2.5 text-sm font-bold text-white"
        onClick={reset}
        type="button"
      >
        Coba lagi
      </button>
    </div>
  );
}
