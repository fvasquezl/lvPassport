---
name: speckit-reconcile
description: "Run the spec-kit quality loop for an lvPassport feature: /speckit-analyze, reconcile design docs (spec/plan/research/data-model) to the ACTUAL implementation, close coverage gaps with green Pest tests, then re-analyze until 0 CRITICAL/HIGH. Use after touching a spec-kit feature's docs or code, or to audit consistency between specs, code, and tests."
argument-hint: "Feature dir name, e.g. 002-article-comments"
metadata:
  author: lvPassport
user-invocable: true
disable-model-invocation: false
---

# Spec-kit reconcile loop

Drive a spec-kit feature to consistency: docs match the code that actually shipped, every functional
requirement has test coverage, and `/speckit-analyze` comes back clean. This is **iterative** — analyze,
fix, re-analyze — not a one-shot. Read-only analysis never edits files; the reconcile/test steps do.

The target feature is the argument (e.g. `002-article-comments`). If omitted, use the active feature in
`.specify/feature.json`.

## Gotcha: spec-kit is branch-based

On the `main` branch the spec-kit scripts fail with "Not on a feature branch". For every spec-kit
invocation on `main`, set the feature explicitly:

```bash
export SPECIFY_FEATURE=<NNN-feature-name>
```

`.specify/feature.json` (`{"feature_directory": "specs/<NNN-...>"}`) wins when resolving the directory;
use `SPECIFY_FEATURE_DIRECTORY=specs/<NNN-...>` to point at a different one. Confirm resolution with:

```bash
SPECIFY_FEATURE=<NNN> bash .specify/scripts/bash/check-prerequisites.sh --json --require-tasks --include-tasks
```

## Step 1 — Analyze

Run `/speckit-analyze` for the feature (it reads spec.md, plan.md, tasks.md, research.md,
data-model.md, contracts/, and the constitution). Capture the findings table and the metrics
(Inconsistency / Ambiguity / Duplication counts, coverage %, CRITICAL/HIGH count). Treat constitution
MUST violations as CRITICAL.

## Step 2 — Triage

Order findings by severity (CRITICAL → HIGH → MEDIUM → LOW). For each, classify the fix:
- **Docs-vs-reality inconsistency** (a design doc describes an approach the code did NOT take) → Step 3.
- **Coverage gap** (an FR/edge case with no task or test) → Step 4.
- **Duplication / ambiguity / wording** (LOW) → optional spec polish; surface but don't over-invest.

## Step 3 — Reconcile docs to the ACTUAL implementation (key principle)

Design docs must describe **what was built, not what was originally planned.** Read the real code
(`app/JsonApi/V2/...`, `app/Policies/...`, `app/Http/Controllers/...`, routes) and rewrite the stale
sections so research `D#` decisions, plan summary/structure, and the data-model authorization matrix
all match. Note the alternative that was *considered but not chosen* rather than asserting the wrong one
(e.g. comments: the implementation uses a `CommentPolicy` via `Gate::inspect` with `store`-ownership as
a payload check in the authorizer — NOT the authorizer-direct approach research originally "picked").
Also keep `tasks.md`, `api-v2-progress.md`, and the `CLAUDE.md` SPECKIT marker consistent.

## Step 4 — Close coverage gaps with green tests

Delegate to the **`pest-tester`** agent (or follow `tests/Pest.php` conventions). For each uncovered FR
or edge case, add a Pest feature test and **verify real behavior before fixing the assertion** (run it
once, observe the actual status/pointer, then assert that — e.g. a comment on a non-existent article
returns `404`). Cover the super-admin path where an FR claims it (bypass via `Gate::before` for
update/delete; remember `store` author-ownership is NOT bypassed). Record the new tests in `tasks.md`
(a "Phase N: coverage hardening (post-analyze)" block) and update test counts.

## Step 5 — Quality gates (constitution)

```bash
vendor/bin/sail artisan test --compact --filter=<area>   # the touched area, green
vendor/bin/sail artisan test --compact                   # full suite, no regression
vendor/bin/sail bin pint --dirty --format agent          # style on changed files
```

## Step 6 — Re-analyze (confirm)

Re-run `/speckit-analyze` and show the before/after metrics so the user can see the targeted findings
disappear (e.g. "Inconsistency 1 → 0", "Critical 0"). Stop when there are **no CRITICAL/HIGH** findings
left (LOW polish is optional). Report what changed and what remains, with honest counts. Do not weaken
or delete tests, and leave V1 untouched.
