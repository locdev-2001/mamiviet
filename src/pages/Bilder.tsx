import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import GalleryGrid from "@/components/GalleryGrid";
import React, { useEffect, useState } from "react";
import Loading from "@/components/ui/loading";

export default function Bilder() {
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    const timer = setTimeout(() => setLoading(false), 1200);
    return () => clearTimeout(timer);
  }, []);
  if (loading) return <div className="min-h-screen flex items-center justify-center bg-black"><Loading /></div>;
  return (
    <div className="min-h-screen bg-black text-white flex flex-col">
      <Header />
      <main className="flex-1 mt-16">
        <section className="max-w-7xl mx-auto px-4 py-12">
          <GalleryGrid withTitle={true} />
        </section>
      </main>
      <Footer />
    </div>
  );
} 