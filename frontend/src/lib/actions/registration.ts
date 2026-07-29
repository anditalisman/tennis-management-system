"use server";

import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { GUARDIAN_REQUIRED_AGE_CATEGORIES } from "@/lib/constants";

export type RegistrationState =
  | { error?: string; fieldErrors?: Record<string, string>; registrationNo?: string }
  | undefined;

export async function registerParticipantAction(
  _prevState: RegistrationState,
  formData: FormData,
): Promise<RegistrationState> {
  const ageCategory = String(formData.get("age_category") ?? "");
  const needsGuardian = (GUARDIAN_REQUIRED_AGE_CATEGORIES as readonly string[]).includes(ageCategory);

  const payload = {
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    full_name: String(formData.get("full_name") ?? ""),
    email: String(formData.get("email") ?? ""),
    age_category: ageCategory,
    gender: String(formData.get("gender") ?? "") || undefined,
    skill_level: String(formData.get("skill_level") ?? "") || undefined,
    phone: String(formData.get("phone") ?? "") || undefined,
    policy_accepted: formData.get("policy_accepted") === "on",
    ...(needsGuardian
      ? {
          guardian: {
            name: String(formData.get("guardian_name") ?? ""),
            relation: String(formData.get("guardian_relation") ?? ""),
            phone: String(formData.get("guardian_phone") ?? ""),
            email: String(formData.get("guardian_email") ?? ""),
            password: String(formData.get("guardian_password") ?? ""),
          },
        }
      : { password: String(formData.get("password") ?? "") }),
  };

  let response: { registration_no: string };
  try {
    response = await serverApi<{ registration_no: string }>("/participants", {
      method: "POST",
      body: payload,
    });
  } catch (error) {
    if (error instanceof ApiError) {
      return { error: error.message, fieldErrors: error.fieldErrors() };
    }
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  redirect(`/pendaftaran/berhasil?no=${encodeURIComponent(response.registration_no)}`);
}
