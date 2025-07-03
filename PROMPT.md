# PROMPT

## Reveiw current file

- What do you think about the current file?
- Is it good? Rate it on a 1-10 scale
- Suggest your improvements

## AI Prompt Engineering Guidelines

- Clearly define input, output, and constraints for each coding agent workflow.
- Ensure prompts are concise, focused, and avoid introducing breaking changes.
- If needed, break down into multiple steps/prompts. Write actionable, self-contained prompts using Laravel 12 and Nuxt 3 terminology.
- Do not generate documentation, examples, or explanatory content unless explicitly requested.

## PROMOT to cleanup and optimize FRONTEND

Refactor and optimize the current file code with the following requirements

- Use Bootstrap 5.3 classes wherever possible (Bootstrap-First Approach).
- Use custom CSS classes only if absolutely necessary.
- Preserve all existing logic and functionality.
- Maintain the current UI/UX exactly as it is (no visual or behavioral changes).
- Remove unnecessary or unused CSS classes.
- Eliminate redundant or unused JavaScript functions or code.
- Simplify JavaScript logic where possible without changing behavior.
- Ensure the final code is clean, efficient, maintainable, and production-ready.
- After refactoring, provide a rating of the code on a 1-10 scale both before and after optimization, highlighting the improvements made.

## No directy call to authoneticated endpoints

Search the frontend codebase for any direct calls to the authentication endpoint that do not go through `/frontend/services/api.ts`. Specifically, look for instances where `$fetch` is called directly to access authentication-related endpoints.

**Task:**

- List all locations (files and line numbers) where `$fetch` is used directly for authentication, bypassing the `/frontend/services/api.ts` service layer.

**Goal:**  
Ensure all authenticated requests are routed through `/frontend/services/api.ts` for consistency, maintainability, and centralized error handling.

✅ All pages (/pages/**)
✅ All components (/components/**)
✅ All composables (/composables/**)
✅ All services (/services/**)
✅ All utilities (/utils/**)
✅ All plugins (/plugins/**)
✅ All middleware (/middleware/\*\*)
✅ Root application file (app.vue)
