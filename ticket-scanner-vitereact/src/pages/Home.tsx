"use client";

import { useEffect, useRef, useState } from "react";
import { Html5Qrcode } from "html5-qrcode";

type ApiResponse = {
  status: "berhasil" | "error";
  message: string;
};

export default function Home() {
  const qrRegionId = "qr-reader";
  const qrCodeRef = useRef<Html5Qrcode | null>(null);

  const successAudioRef = useRef<HTMLAudioElement | null>(null);
  const errorAudioRef = useRef<HTMLAudioElement | null>(null);

  const [cameras, setCameras] = useState<{ id: string; label: string }[]>([]);
  const [selectedCamera, setSelectedCamera] = useState("");
  const [lastScanned, setLastScanned] = useState<string | null>(null);

  const [alert, setAlert] = useState<{
    type: "success" | "error";
    message: string;
  } | null>(null);

  const scanningLockRef = useRef(false);

  /* INIT */
  useEffect(() => {
    successAudioRef.current = new Audio("/success.mp3");
    errorAudioRef.current = new Audio("/failed.mp3");

    successAudioRef.current.volume = 1.0;
    errorAudioRef.current.volume = 1.0;

    Html5Qrcode.getCameras().then((devices) => {
      if (devices.length) {
        setCameras(devices);
        setSelectedCamera(devices[0].id);
      }
    });

    return () => {
      qrCodeRef.current?.stop().catch(() => {});
      qrCodeRef.current?.clear();
    };
  }, []);

  /* START CAMERA */
  useEffect(() => {
    if (!selectedCamera) return;

    const start = async () => {
      if (qrCodeRef.current) {
        await qrCodeRef.current.stop().catch(() => {});
        await qrCodeRef.current.clear();
      }

      const scanner = new Html5Qrcode(qrRegionId);
      qrCodeRef.current = scanner;

      await scanner.start(
        { deviceId: { exact: selectedCamera } },
        {
          fps: 12,
          aspectRatio: 1,
        },
        async (decodedText) => {
          if (scanningLockRef.current) return;
          scanningLockRef.current = true;

          setLastScanned(decodedText);
          await handlePresent(decodedText);

          setTimeout(() => {
            scanningLockRef.current = false;
          }, 1500);
        },
        () => {}
      );

      // Mirror + full cover fix
      setTimeout(() => {
        const video = document.querySelector("#qr-reader video") as HTMLVideoElement;
        if (video) {
          video.style.transform = "scaleX(-1)";
          video.style.width = "100%";
          video.style.height = "100%";
          video.style.objectFit = "cover";
        }
      }, 300);
    };

    start();
  }, [selectedCamera]);

  /* CALL PRESENT API */
  const handlePresent = async (qr: string) => {
    try {
      const res = await fetch(
        `https://ospkmhthamrin.com/api/oh/present?qrString=${encodeURIComponent(qr)}`
      );
      const data: ApiResponse = await res.json();

      if (data.status === "berhasil") {
        successAudioRef.current?.play().catch(() => {});
        setAlert({ type: "success", message: data.message });
      } else {
        errorAudioRef.current?.play().catch(() => {});
        setAlert({ type: "error", message: data.message });
      }

      setTimeout(() => setAlert(null), 2500);
    } catch (err) {
      errorAudioRef.current?.play().catch(() => {});
      setAlert({ type: "error", message: "Server error / API unreachable" });
      setTimeout(() => setAlert(null), 2500);
    }
  };

  return (
    <div className="w-screen h-screen flex flex-col bg-[#0B1220] text-white overflow-hidden">
      {/* NAVBAR */}
      <header className="h-20 flex items-center justify-center bg-[#0F172A] border-b border-blue-900 shrink-0">
        <div className="text-center">
          <h1 className="text-xl font-semibold tracking-wide text-blue-400">
            REGISTRASI ULANG OPEN HOUSE
          </h1>
          <p className="text-sm text-blue-200/70">
            E-TICKETING SYSTEM BY OSPK M.H. THAMRIN
          </p>
        </div>
      </header>

      {/* MAIN */}
      <main className="flex-1 min-h-0 w-full flex flex-col xl:flex-row">
        {/* CAMERA */}
        <section className="relative flex-1 min-h-0 w-full bg-black overflow-hidden border border-blue-900">
          <div id={qrRegionId} className="absolute inset-0" />

          {/* SCANNER FRAME */}
          <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div className="w-[70vmin] h-[70vmin] max-w-[90%] max-h-[90%] rounded-2xl border-4 border-blue-500 shadow-[0_0_40px_#3B82F6]" />
          </div>

          {/* HOT ALERT */}
          {alert && (
            <div
              className={`absolute top-5 left-1/2 -translate-x-1/2 px-6 py-3 rounded-xl text-sm font-semibold shadow-xl
              ${
                alert.type === "success"
                  ? "bg-green-600 text-white"
                  : "bg-red-600 text-white"
              }`}
            >
              {alert.message}
            </div>
          )}
        </section>

        {/* PANEL */}
        <aside className="w-full xl:w-[360px] shrink-0 flex flex-col justify-center gap-6 p-4 bg-[#0B1220]">
          <div className="bg-[#0F172A] border border-blue-900 rounded-2xl p-5">
            <p className="text-sm text-blue-300 mb-2">Sumber Kamera</p>
            <select
              value={selectedCamera}
              onChange={(e) => setSelectedCamera(e.target.value)}
              className="w-full p-3 rounded-xl bg-[#020617] border border-blue-900 outline-none text-white"
            >
              {cameras.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.label || "Camera"}
                </option>
              ))}
            </select>
          </div>

          {lastScanned && (
            <div className="bg-[#0F172A] border border-blue-900 rounded-2xl p-4">
              <p className="text-xs text-blue-300 mb-1">Terakhir di Scan</p>
              <p className="font-mono text-sm break-words text-blue-100">
                {lastScanned}
              </p>
            </div>
          )}
        </aside>
      </main>
    </div>
  );
}
