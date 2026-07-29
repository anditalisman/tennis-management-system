// Mirrors backend/app/Models/Role.php constants — keep in sync.
export const ROLES = {
  SUPER_ADMIN: "super-admin",
  MANAGEMENT: "management",
  ADMINISTRATOR: "administrator",
  COACH: "coach",
  PARTICIPANT: "participant",
  GUARDIAN: "guardian",
  FINANCE: "finance",
} as const;

export type RoleSlug = (typeof ROLES)[keyof typeof ROLES];

export const STAFF_ROLES: RoleSlug[] = [
  ROLES.SUPER_ADMIN,
  ROLES.MANAGEMENT,
  ROLES.ADMINISTRATOR,
];

export function hasRole(roles: string[], ...slugs: string[]): boolean {
  return roles.some((r) => slugs.includes(r));
}

export function isStaff(roles: string[]): boolean {
  return hasRole(roles, ...STAFF_ROLES);
}

/** Which sidebar/nav set a user lands on after login. */
export function primaryPortal(roles: string[]): "admin" | "coach" | "finance" | "participant" {
  if (hasRole(roles, ROLES.SUPER_ADMIN, ROLES.MANAGEMENT, ROLES.ADMINISTRATOR)) return "admin";
  if (hasRole(roles, ROLES.FINANCE)) return "finance";
  if (hasRole(roles, ROLES.COACH)) return "coach";
  return "participant";
}

export const ROLE_LABELS: Record<string, string> = {
  [ROLES.SUPER_ADMIN]: "Super Admin",
  [ROLES.MANAGEMENT]: "Manajemen",
  [ROLES.ADMINISTRATOR]: "Administrator",
  [ROLES.COACH]: "Pelatih",
  [ROLES.PARTICIPANT]: "Peserta",
  [ROLES.GUARDIAN]: "Orang Tua/Wali",
  [ROLES.FINANCE]: "Keuangan",
};
