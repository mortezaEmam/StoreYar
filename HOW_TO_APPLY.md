# TASK-007 + TASK-008

```bash
git am TASK-007-008.patch
# or: git apply TASK-007-008.patch

php artisan test tests/Architecture
php artisan test tests/Unit/Shared/Application/Idempotency
```

Removes empty module folders (only .gitkeep). Deprecates App\Models\User.

If `git am` conflicts on LEGACY doc only, use:
git checkout --theirs docs/architecture/LEGACY_AND_QUARANTINE.md
# or apply and skip docs
