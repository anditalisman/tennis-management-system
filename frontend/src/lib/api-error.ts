// Shared by lib/api.ts (client) and lib/server-api.ts (server) — no
// server-only restriction so both sides can import the same class and
// `instanceof` checks work regardless of which one threw it.
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";
  }

  /** Flattened first-message-per-field, handy for simple form error display. */
  fieldErrors(): Record<string, string> {
    if (!this.errors) return {};
    return Object.fromEntries(Object.entries(this.errors).map(([field, msgs]) => [field, msgs[0]]));
  }
}
