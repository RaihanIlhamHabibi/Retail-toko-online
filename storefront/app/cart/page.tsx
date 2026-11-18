"use client";

import Link from "next/link";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth, useCart } from "@/components/providers";
import { apiRequest, formatRupiah, type Order } from "@/lib/api";

export default function CartPage() {
  const { lines, remove, setQuantity, clear } = useCart();
  const { token } = useAuth();
  const router = useRouter();
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const total = lines.reduce(
    (sum, line) => sum + Number(line.product.price) * line.quantity,
    0,
  );

  async function checkout() {
    if (!token) {
      router.push("/login?redirect=/cart");
      return;
    }
    setLoading(true);
    setError("");
    try {
      const order = await apiRequest<Order>(
        "/orders",
        {
          method: "POST",
          body: JSON.stringify({
            items: lines.map((line) => ({
              product_id: line.product.id,
              quantity: line.quantity,
            })),
          }),
        },
        token,
      );
      clear();
      router.push(`/orders/${order.id}`);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Checkout gagal.");
    } finally {
      setLoading(false);
    }
  }

  if (lines.length === 0) {
    return (
      <section className="mx-auto max-w-2xl rounded-[2rem] bg-white p-10 text-center shadow-card">
        <p className="text-4xl" aria-hidden="true">◌</p>
        <h1 className="mt-4 text-2xl font-black">Tas belanjamu masih kosong.</h1>
        <Link className="focus-ring mt-6 inline-block rounded-full bg-forest px-5 py-3 text-sm font-bold text-white" href="/">Lihat katalog</Link>
      </section>
    );
  }

  return (
    <section className="mx-auto max-w-4xl">
      <p className="text-xs font-bold uppercase tracking-[0.2em] text-forest">Checkout</p>
      <h1 className="mt-2 text-3xl font-black">Tas belanja</h1>
      <div className="mt-7 space-y-3">
        {lines.map(({ product, quantity }) => (
          <article className="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-card" key={product.id}>
            <div className="min-w-40 flex-1">
              <h2 className="font-bold">{product.name}</h2>
              <p className="mt-1 text-sm text-ink/55">{formatRupiah(product.price)}</p>
            </div>
            <label className="text-xs font-semibold text-ink/55">
              Jumlah
              <input
                className="focus-ring ml-2 w-20 rounded-lg border border-ink/15 px-2 py-2 text-sm text-ink"
                max={product.stock_quantity}
                min={1}
                onChange={(event) => setQuantity(product.id, Number(event.target.value))}
                type="number"
                value={quantity}
              />
            </label>
            <button className="focus-ring rounded-lg px-2 py-2 text-xs font-bold text-red-700" onClick={() => remove(product.id)} type="button">Hapus</button>
          </article>
        ))}
      </div>
      <div className="mt-6 rounded-2xl bg-white p-6 shadow-card">
        <div className="flex items-center justify-between text-lg font-extrabold">
          <span>Total</span><span>{formatRupiah(total)}</span>
        </div>
        <p className="mt-2 text-xs text-ink/50">Harga dan stok akhir divalidasi kembali oleh server saat checkout.</p>
        {error && <p className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{error}</p>}
        <button className="focus-ring mt-5 w-full rounded-full bg-forest px-5 py-3 font-bold text-white disabled:opacity-50" disabled={loading} onClick={() => void checkout()} type="button">
          {loading ? "Membuat pesanan..." : token ? "Buat pesanan" : "Masuk untuk checkout"}
        </button>
      </div>
    </section>
  );
}
