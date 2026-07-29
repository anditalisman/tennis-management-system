"use client";

import { useActionState } from "react";
import { createUserAction, updateUserAction } from "@/lib/actions/users";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";
import { ROLE_LABELS, ROLES } from "@/lib/roles";

type User = {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  branch_id: number | null;
  status?: string;
  roles: string[];
};

export function UserForm({ user }: { user?: User }) {
  const action = user ? updateUserAction.bind(null, user.id) : createUserAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Nama lengkap" name="name" required defaultValue={user?.name} error={fieldErrors.name} />
      <Input label="Email" name="email" type="email" required defaultValue={user?.email} error={fieldErrors.email} />
      <Input label="Telepon (opsional)" name="phone" defaultValue={user?.phone ?? ""} error={fieldErrors.phone} />
      <Input
        label={user ? "Password baru (opsional)" : "Password"}
        name="password"
        type="password"
        required={!user}
        hint={user ? "Kosongkan bila tidak ingin mengubah password" : "Minimal 8 karakter, kombinasi huruf besar/kecil & angka"}
        error={fieldErrors.password}
      />
      {user && (
        <Select label="Status" name="status" defaultValue={user.status ?? "active"}>
          <option value="active">Aktif</option>
          <option value="suspended">Ditangguhkan</option>
          <option value="inactive">Nonaktif</option>
        </Select>
      )}
      <div className="flex flex-col gap-1.5">
        <span className="text-sm font-semibold text-(--color-ink-900)">
          Peran <span className="text-(--color-crit)">*</span>
        </span>
        <div className="grid grid-cols-2 gap-2 rounded-lg border border-(--color-ink-900)/15 p-3 sm:grid-cols-3">
          {Object.values(ROLES).map((slug) => (
            <label key={slug} className="flex items-center gap-2 text-sm text-(--color-ink-900)">
              <input type="checkbox" name="roles" value={slug} defaultChecked={user?.roles.includes(slug)} className="h-4 w-4 rounded" />
              {ROLE_LABELS[slug]}
            </label>
          ))}
        </div>
        {fieldErrors.roles && <p className="text-xs font-medium text-(--color-crit)">{fieldErrors.roles}</p>}
      </div>
      <Button type="submit" loading={pending} className="self-end">
        {user ? "Simpan Perubahan" : "Tambah Pengguna"}
      </Button>
    </form>
  );
}
