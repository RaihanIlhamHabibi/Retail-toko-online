"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { useCart } from "@/components/providers";
import { apiRequest, formatRupiah, type Product } from "@/lib/api";

export default function ProductPage() {
  const params = useParams<{ id: string }>();
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const { add } = useCart();

  useEffect(() => {
    let active = true;
    setLoading(true);
    apiRequest<Product>(`/products/${encodeURIComponent(params.id)}`)
      .then((result) => {
        if (active) setProduct(result);
      })
      .catch((cause: unknown) => {
        if (active) setError(cause instanceof Error ? cause.message : "Produk gagal dimuat.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [params.id]);

  if (loading) return <p className="py-20 text-center text-ink/55">Memuat produk...</p>;
  if (error || !product) {
    return (
      <div className="mx-auto max-w-xl rounded-3xl bg-white p-8 text-center" role="alert">
        <p>{error || "Produk tidak ditemukan."}</p>
        <Link className="mt-4 inline-block font-bold text-forest underline" href="/">Kembali ke katalog</Link>
      </div>
    );
  }

  return (
    <article className="mx-auto grid max-w-5xl gap-8 rounded-[2rem] bg-white p-6 shadow-card sm:grid-cols-2 sm:p-10">
      <div className="relative grid min-h-72 place-items-center overflow-hidden rounded-3xl bg-[#e8eee8] p-8">
        <span className="absolute h-64 w-64 rounded-full border-[36px] border-white/60" />
        <span className="relative text-center text-4xl font-black tracking-tight text-forest">{product.name}</span>
      </div>
      <div className="flex flex-col justify-center">
        <Link className="mb-8 text-sm font-bold text-forest" href="/">← Kembali ke katalog</Link>
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-forest">{product.category}</p>
        <h1 className="mt-3 text-4xl font-black tracking-tight">{product.name}</h1>
        <p className="mt-4 leading-7 text-ink/65">{product.description || "Pilihan berkualitas untuk kebutuhan harian."}</p>
        <p className="mt-7 text-2xl font-extrabold">{formatRupiah(product.price)}</p>
        <p className="mt-2 text-sm text-ink/55">{product.stock_quantity} tersedia</p>
        <button
          className="focus-ring mt-7 rounded-full bg-forest px-6 py-3 font-bold text-white hover:bg-ink disabled:opacity-40"
          disabled={product.stock_quantity < 1}
          onClick={() => add(product)}
          type="button"
        >
          {product.stock_quantity > 0 ? "Tambahkan ke tas" : "Stok habis"}
        </button>
      </div>
    </article>
  );
}
