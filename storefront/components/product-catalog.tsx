"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { apiRequest, formatRupiah, type Page, type Product } from "@/lib/api";
import { useCart } from "@/components/providers";

export function ProductCatalog() {
  const [products, setProducts] = useState<Product[]>([]);
  const [search, setSearch] = useState("");
  const [query, setQuery] = useState("");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const { add } = useCart();

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    const params = new URLSearchParams({ per_page: "12", page: String(page) });
    if (query) params.set("search", query);

    apiRequest<Page<Product>>(`/products?${params}`)
      .then((result) => {
        if (active) {
          setProducts(result.data);
          setLastPage(result.last_page);
        }
      })
      .catch((cause: unknown) => {
        if (active) {
          setError(cause instanceof Error ? cause.message : "Katalog gagal dimuat.");
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [page, query]);

  function submitSearch(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    setQuery(search.trim());
  }

  return (
    <section>
      <div className="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-forest">
            Pilihan untuk hari ini
          </p>
          <h2 className="text-3xl font-extrabold tracking-tight sm:text-4xl">Temukan barang favoritmu.</h2>
        </div>
        <form className="flex gap-2" onSubmit={submitSearch}>
          <label className="sr-only" htmlFor="search">Cari produk</label>
          <input
            className="focus-ring min-w-0 rounded-xl border border-ink/10 bg-white px-4 py-3 text-sm sm:w-64"
            id="search"
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Cari produk..."
            value={search}
          />
          <button className="focus-ring rounded-xl bg-forest px-5 py-3 text-sm font-bold text-white hover:bg-ink" type="submit">
            Cari
          </button>
        </form>
      </div>

      {loading ? (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }, (_, index) => (
            <div className="h-72 animate-pulse rounded-3xl bg-white/70" key={index} />
          ))}
        </div>
      ) : error ? (
        <div className="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800" role="alert">
          {error}
        </div>
      ) : products.length === 0 ? (
        <div className="rounded-3xl border border-ink/10 bg-white p-10 text-center text-ink/60">
          Belum ada produk yang cocok dengan pencarianmu.
        </div>
      ) : (
        <>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {products.map((product, index) => (
              <article className="group overflow-hidden rounded-3xl border border-ink/5 bg-white shadow-card transition hover:-translate-y-1" key={product.id}>
                <Link href={`/products/${product.id}`} className="focus-ring block">
                  <div className={`relative flex h-48 items-end overflow-hidden p-6 ${index % 3 === 1 ? "bg-[#eee9da]" : index % 3 === 2 ? "bg-[#e4eddd]" : "bg-[#e8eee8]"}`}>
                    <span className="absolute -right-4 -top-10 h-40 w-40 rounded-full border-[24px] border-white/40 transition group-hover:scale-110" />
                    <span className="relative max-w-[75%] text-3xl font-black leading-tight tracking-tight text-forest">{product.name}</span>
                    <span className="absolute bottom-5 right-5 rounded-full bg-white px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-forest">{product.category}</span>
                  </div>
                </Link>
                <div className="p-6">
                  <p className="mb-3 min-h-10 text-sm leading-5 text-ink/60">{product.description || "Pilihan berkualitas untuk kebutuhan harian."}</p>
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <p className="font-extrabold">{formatRupiah(product.price)}</p>
                      <p className="mt-1 text-xs text-ink/45">Stok {product.stock_quantity}</p>
                    </div>
                    <button
                      className="focus-ring rounded-full bg-lime px-4 py-2.5 text-xs font-extrabold hover:bg-[#b6da6e] disabled:cursor-not-allowed disabled:opacity-50"
                      disabled={product.stock_quantity < 1}
                      onClick={() => add(product)}
                      type="button"
                    >
                      {product.stock_quantity > 0 ? "Tambah +" : "Habis"}
                    </button>
                  </div>
                </div>
              </article>
            ))}
          </div>
          <div className="mt-8 flex items-center justify-center gap-4">
            <button className="focus-ring rounded-full border border-ink/15 bg-white px-4 py-2 text-sm font-semibold disabled:opacity-40" disabled={page <= 1} onClick={() => setPage((value) => value - 1)} type="button">Sebelumnya</button>
            <span className="text-sm text-ink/55">Halaman {page} dari {lastPage}</span>
            <button className="focus-ring rounded-full border border-ink/15 bg-white px-4 py-2 text-sm font-semibold disabled:opacity-40" disabled={page >= lastPage} onClick={() => setPage((value) => value + 1)} type="button">Berikutnya</button>
          </div>
        </>
      )}
    </section>
  );
}
