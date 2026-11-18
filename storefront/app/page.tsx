import { ProductCatalog } from "@/components/product-catalog";

export default function HomePage() {
  return (
    <>
      <section className="relative mb-14 overflow-hidden rounded-[2rem] bg-forest px-7 py-12 text-white sm:px-12 sm:py-16">
        <div className="relative z-10 max-w-2xl">
          <p className="mb-4 text-xs font-bold uppercase tracking-[0.24em] text-lime">
            Belanja lebih berarti
          </p>
          <h1 className="text-4xl font-black leading-[1.05] tracking-tight sm:text-6xl">
            Barang baik,
            <br />
            untuk hari yang baik.
          </h1>
          <p className="mt-5 max-w-lg text-sm leading-6 text-white/70 sm:text-base">
            Kurasi kebutuhan harian pilihan dengan pengalaman belanja yang
            sederhana dan nyaman.
          </p>
          <a
            className="focus-ring mt-8 inline-flex rounded-full bg-lime px-6 py-3 text-sm font-extrabold text-ink transition hover:bg-white"
            href="#katalog"
          >
            Jelajahi katalog <span className="ml-3" aria-hidden="true">↓</span>
          </a>
        </div>
        <div className="absolute -right-10 -top-20 h-80 w-80 rounded-full border-[40px] border-white/5 sm:right-12 sm:top-8" />
        <div className="absolute -bottom-36 right-28 h-72 w-72 rounded-full bg-lime/10 blur-2xl" />
        <div className="absolute bottom-8 right-12 hidden rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur sm:block">
          <span className="block text-3xl font-black text-lime">01</span>
          <span className="mt-1 block text-xs text-white/70">Pilih dengan tenang</span>
        </div>
      </section>
      <div id="katalog">
        <ProductCatalog />
      </div>
    </>
  );
}
