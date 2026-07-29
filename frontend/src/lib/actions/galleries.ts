"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/session";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type GalleryFormState = { error?: string } | undefined;

const API_INTERNAL_URL = process.env.API_INTERNAL_URL ?? "http://nginx/api/v1";

export async function createGalleryAction(_prevState: GalleryFormState, formData: FormData): Promise<GalleryFormState> {
  const classId = Number(formData.get("class_id"));
  const title = String(formData.get("title") ?? "") || undefined;
  const files = formData.getAll("files").filter((f): f is File => f instanceof File && f.size > 0);

  let galleryId: number;
  try {
    const gallery = await serverApi<{ id: number }>("/galleries", { method: "POST", body: { class_id: classId, title } });
    galleryId = gallery.id;
  } catch (error) {
    return { error: error instanceof ApiError ? error.message : "Tidak dapat terhubung ke server. Coba lagi." };
  }

  if (files.length > 0) {
    const session = await getSession();
    const uploadForm = new FormData();
    files.forEach((file) => uploadForm.append("files[]", file));

    const res = await fetch(`${API_INTERNAL_URL}/galleries/${galleryId}/media`, {
      method: "POST",
      headers: { Accept: "application/json", ...(session ? { Authorization: `Bearer ${session.token}` } : {}) },
      body: uploadForm,
    });
    if (!res.ok) {
      return { error: "Galeri dibuat, tetapi unggah media gagal. Coba tambahkan media lagi dari halaman detail." };
    }
  }

  revalidatePath("/portal/galeri");
  redirect(`/portal/galeri/${galleryId}`);
}

export async function publishGalleryAction(galleryId: number): Promise<void> {
  await runMutation(`/portal/galeri/${galleryId}`, async () => {
    await serverApi(`/galleries/${galleryId}/publish`, { method: "POST", body: {} });
    revalidatePath(`/portal/galeri/${galleryId}`);
    revalidatePath("/portal/galeri");
  });
}

export async function deleteGalleryAction(galleryId: number): Promise<void> {
  await runMutation("/portal/galeri", async () => {
    await serverApi(`/galleries/${galleryId}`, { method: "DELETE" });
    revalidatePath("/portal/galeri");
  });
}
