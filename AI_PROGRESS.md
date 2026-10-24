# AI_PROGRESS

## Done this sequence

1. **Password reset** — `POST /api/auth/forgot-password`, `POST /api/auth/reset-password`, migration `identity_password_reset_tokens`, domain commands, tests
2. **i18n foundation** — `frontend/locales/{en,fa}.json`, `useI18n`, RTL `dir` on `<html>`, forgot/reset pages
3. **Request correlation** — `AssignRequestId` middleware, `X-Request-Id` request/response, log context

## Next

- Wire production mailer for PasswordResetNotifier (currently log + local/debug token)
- Expand UI strings through `t()` across remaining pages
- OpenAPI product rename/price sync if not already applied
