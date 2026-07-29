// No "server-only" restriction here — this needs to be importable from
// proxy.ts (edge-adjacent execution) as well as server-only modules.
export const SESSION_COOKIE_NAME = "ztcms_session";

// Mirrors backend/app/Models/Participant.php AGE_CATEGORIES — keep in sync.
export const AGE_CATEGORIES = [
  { value: "u10", label: "U10 (di bawah 10 tahun)" },
  { value: "u12", label: "U12 (di bawah 12 tahun)" },
  { value: "u14", label: "U14 (di bawah 14 tahun)" },
  { value: "u16", label: "U16 (di bawah 16 tahun)" },
  { value: "dewasa", label: "Dewasa" },
  { value: "prestasi", label: "Prestasi (jalur kompetitif)" },
] as const;

// Mirrors backend Participant::GUARDIAN_REQUIRED_CATEGORIES — every category
// except "dewasa" needs a guardian account (minors by bracket, plus the
// competitive "prestasi" track regardless of age).
export const GUARDIAN_REQUIRED_AGE_CATEGORIES = ["u10", "u12", "u14", "u16", "prestasi"] as const;
