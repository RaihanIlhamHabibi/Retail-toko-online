"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuth } from "@/components/providers";
import { apiRequest, formatRupiah, type Order } from "@/lib/api";

export default function OrderDetailPage() {
  const { token } = useAuth();
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const [order, setOrder] = useState<Order | null>(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);

  const loadOrder = useCallback(async () => {
    if (!token) return;
    const result = await apiRequest<Order>(
      `/orders/${encodeURIComponent(params.id)}`,
      {},
      token,
    );
    setOrder(result);
  }, [params.id, token]);

  useEffect(() => {
    if (!token) {
      router.replace(`/login?redirect=/orders/${encodeURIComponent(params.id)}`);
      return;
    }
    let active = true;
    loadOrder()
      .catch((cause: unknown) => {
        if (active) setError(cause instanceof Error ? cause.message : "Pesanan gagal dimuat.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [loadOrder, params.id, router, token]);

  async function changeStatus(action: "payment" | "cancel") {
    if (!token) return;
    setError("");
    try {
      const updated = await apiRequest<Order>(
        `/orders/${order?.id}/${action}`,
        { method: "POST" },
        token,
      );
      setOrder(updated);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Status pesanan gagal diperbarui.");
    }
  }

  if (loading) return <p className="py-16 text-center text-ink/55">Memuat pesanan...</p>;
  if (error && !order) return <p className="rounded-xl bg-red-50 p-4 text-sm text-red-800" role="alert">{error}</p>;
  if (!order) return null;

  return (
    <section className="mx-auto max-w-4xl">
      <Link className="text-sm font-bold text-forest" href="/orders">← Riwayat pesanan</Link>
      <div className="mt-5 rounded-[2rem] bg-white p-6 shadow-card sm:p-9">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-forest">Detail pesanan</p>
            <h1 className="mt-2 break-all text-2xl font-black">#{order.order_number}</h1>
          </div>
          <span className="rounded-full bg-lime/60 px-4 py-2 text-sm font-bold">{order.status.replaceAll("_", " ")}</span>
        </div>
        <div className="mt-7 divide-y divide-ink/10">
          {order.items.map((item) => (
            <div className="flex flex-wrap justify-between gap-3 py-4" key={item.id}>
              <div>
                <p className="font-bold">{item.product_name}</p>
                <p className="mt-1 text-sm text-ink/55">{item.quantity} × {formatRupiah(item.unit_price)}</p>
              </div>
              <p className="font-bold">{formatRupiah(item.subtotal)}</p>
            </div>
          ))}
        </div>
        <div className="mt-5 flex justify-between border-t border-ink/10 pt-5 text-lg font-extrabold">
          <span>Total</span><span>{formatRupiah(order.total)}</span>
        </div>
        {error && <p className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{error}</p>}
        <div className="mt-6 flex flex-wrap gap-3">
          {order.status === "pending" && (
            <button className="focus-ring rounded-full bg-forest px-5 py-2.5 text-sm font-bold text-white" onClick={() => void changeStatus("payment")} type="button">Lanjutkan pembayaran</button>
          )}
          {["pending", "payment_pending"].includes(order.status) && (
            <button className="focus-ring rounded-full border border-red-300 px-5 py-2.5 text-sm font-bold text-red-700" onClick={() => void changeStatus("cancel")} type="button">Batalkan pesanan</button>
          )}
        </div>
      </div>
    </section>
  );
}
