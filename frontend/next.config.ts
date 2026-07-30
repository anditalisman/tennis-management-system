import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  experimental: {
    // Default is 1mb, far below the backend's 10MB per-file upload cap
    // (gallery photos, payment proof) — file uploads go through Server
    // Actions here, so this is the layer that was actually rejecting them.
    serverActions: {
      bodySizeLimit: "12mb",
    },
  },
};

export default nextConfig;
