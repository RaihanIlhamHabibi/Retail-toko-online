"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/components/providers";
import { apiRequest, formatRupiah, type Order, type Page } from "@/lib/api";

export default function OrdersPage() {
  const { token } = useAuth();
  const router = useRouter();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!token) {
      router.replace("/login?redirect=/orders");
      return;
    }
    let active = true;
    apiRequest<Page<Order>>("/orders", {}, token)
      .then((result) => {
        if (active) setOrders(result.data);
      })
      .catch((cause: unknown) => {
        if (active) setError(cause instanceof Error ? cause.message : "Pesanan gagal dimuat.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [token, router]);

  return (
    <section className="mx-auto max-w-4xl">
      <p className="text-xs font-bold uppercase tracking-[0.2em] text-forest">Akunmu</p>
      <h1 className="mt-2 text-3xl font-black">Riwayat pesanan</h1>
      {loading ? <p className="mt-8 text-ink/55">Memuat pesanan...</p> : error ? (
        <p className="mt-8 rounded-xl bg-red-50 p-4 text-sm text-red-800" role="alert">{error}</p>
      ) : orders.length === 0 ? (
        <div className="mt-8 rounded-3xl bg-white p-8 text-center">
          <p className="text-ink/55">Belum ada pesanan.</p>
          <Link className="mt-4 inline-block font-bold text-forest underline" href="/">Mulai belanja</Link>
        </div>
      ) : (
        <div className="mt-7 space-y-3">
          {orders.map((order) => (
            <Link className="focus-ring flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card transition hover:-translate-y-0.5" href={`/orders/${order.id}`} key={order.id}>
              <div>
                <p className="font-bold">#{order.order_number}</p>
                <p className="mt-1 text-xs text-ink/50">{new Date(order.created_at).toLocaleString("id-ID")}</p>
              </div>
              <span className="rounded-full bg-lime/50 px-3 py-1 text-xs font-bold">{order.status.replaceAll("_", " ")}</span>
              <span className="font-extrabold">{formatRupiah(order.total)}</span>
            </Link>
          ))}
        </div>
      )}
    </section>
  );
}
