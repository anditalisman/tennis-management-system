"use client";

import { useActionState, useState } from "react";
import { registerParticipantAction } from "@/lib/actions/registration";
import { AGE_CATEGORIES, GUARDIAN_REQUIRED_AGE_CATEGORIES } from "@/lib/constants";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";
import { cn } from "@/components/ui/cn";

const GUARDIAN_REQUIRED = new Set<string>(GUARDIAN_REQUIRED_AGE_CATEGORIES);

export function RegistrationWizard({ defaultBranchId }: { defaultBranchId?: number }) {
  const [state, formAction, pending] = useActionState(registerParticipantAction, undefined);
  const [step, setStep] = useState(0);
  const [ageCategory, setAgeCategory] = useState("");
  const fieldErrors = state?.fieldErrors ?? {};
  const needsGuardian = GUARDIAN_REQUIRED.has(ageCategory);

  const steps = needsGuardian ? (["Data Peserta", "Data Wali", "Konfirmasi"] as const) : (["Data Peserta", "Konfirmasi"] as const);
  const confirmStep = steps.length - 1;

  return (
    <form action={formAction} className="flex flex-col gap-6">
      <ol className="flex items-center gap-2 text-xs font-semibold text-(--color-ink-500)">
        {steps.map((label, idx) => (
          <li key={label} className="flex items-center gap-2">
            <span
              className={cn(
                "flex h-6 w-6 items-center justify-center rounded-full border",
                idx <= step
                  ? "border-(--color-court-600) bg-(--color-court-600) text-white"
                  : "border-(--color-ink-900)/20 text-(--color-ink-400)",
              )}
            >
              {idx + 1}
            </span>
            <span className={idx === step ? "text-(--color-ink-900)" : undefined}>{label}</span>
            {idx < steps.length - 1 && <span className="mx-1 h-px w-6 bg-(--color-ink-900)/15" />}
          </li>
        ))}
      </ol>

      {state?.error && <Alert tone="crit">{state.error}</Alert>}

      <section className={cn("flex flex-col gap-4", step !== 0 && "hidden")} aria-hidden={step !== 0}>
        <input type="hidden" name="branch_id" value={defaultBranchId ?? ""} />
        <Input label="Nama lengkap" name="full_name" required error={fieldErrors.full_name} />
        <Input label="Email" name="email" type="email" required error={fieldErrors.email} />
        <div className="grid grid-cols-2 gap-4">
          <Input label="Nomor telepon" name="phone" type="tel" required error={fieldErrors.phone} />
          <Select label="Jenis kelamin" name="gender" defaultValue="">
            <option value="">Pilih</option>
            <option value="male">Laki-laki</option>
            <option value="female">Perempuan</option>
          </Select>
        </div>
        <Select label="Level kemampuan" name="skill_level" defaultValue="beginner">
          <option value="beginner">Pemula</option>
          <option value="intermediate">Menengah</option>
          <option value="advanced">Mahir</option>
        </Select>
        <Select
          label="Kategori usia"
          name="age_category"
          required
          value={ageCategory}
          onChange={(e) => setAgeCategory(e.target.value)}
          error={fieldErrors.age_category}
        >
          <option value="" disabled>
            Pilih kategori usia
          </option>
          {AGE_CATEGORIES.map((c) => (
            <option key={c.value} value={c.value}>
              {c.label}
            </option>
          ))}
        </Select>
        {!needsGuardian && (
          <Input
            label="Buat kata sandi"
            name="password"
            type="password"
            required
            hint="Minimal 8 karakter, kombinasi huruf besar/kecil dan angka. Dipakai untuk login."
            error={fieldErrors.password}
          />
        )}
        <Button type="button" onClick={() => setStep(1)} disabled={!ageCategory} className="mt-2 self-end">
          Lanjut
        </Button>
      </section>

      {needsGuardian && (
        <section className={cn("flex flex-col gap-4", step !== 1 && "hidden")} aria-hidden={step !== 1}>
          <p className="text-sm text-(--color-ink-500)">
            Kategori usia yang dipilih memerlukan akun wali untuk memantau jadwal, tagihan, dan perkembangan peserta.
          </p>
          <Input label="Nama wali" name="guardian_name" required error={fieldErrors["guardian.name"]} />
          <Select label="Hubungan dengan peserta" name="guardian_relation" required defaultValue="">
            <option value="" disabled>
              Pilih
            </option>
            <option value="Ayah">Ayah</option>
            <option value="Ibu">Ibu</option>
            <option value="Wali">Wali</option>
          </Select>
          <Input label="Nomor telepon wali" name="guardian_phone" type="tel" required error={fieldErrors["guardian.phone"]} />
          <Input label="Email wali (untuk login)" name="guardian_email" type="email" required error={fieldErrors["guardian.email"]} />
          <Input
            label="Kata sandi"
            name="guardian_password"
            type="password"
            required
            hint="Minimal 8 karakter, kombinasi huruf besar/kecil dan angka."
            error={fieldErrors["guardian.password"]}
          />
          <div className="flex justify-between">
            <Button type="button" variant="outline" onClick={() => setStep(0)}>
              Kembali
            </Button>
            <Button type="button" onClick={() => setStep(2)}>
              Lanjut
            </Button>
          </div>
        </section>
      )}

      <section className={cn("flex flex-col gap-4", step !== confirmStep && "hidden")} aria-hidden={step !== confirmStep}>
        <label className="flex items-start gap-3 text-sm text-(--color-ink-700)">
          <input type="checkbox" name="policy_accepted" required className="mt-1 h-4 w-4 rounded border-(--color-ink-900)/30" />
          <span>
            Saya menyetujui kebijakan privasi dan penggunaan foto/video peserta untuk keperluan dokumentasi &amp; promosi
            Zul Tennis Clinic.
          </span>
        </label>
        <div className="flex justify-between">
          <Button type="button" variant="outline" onClick={() => setStep(confirmStep - 1)}>
            Kembali
          </Button>
          <Button type="submit" loading={pending}>
            Daftar Sekarang
          </Button>
        </div>
      </section>
    </form>
  );
}
