# Agent Guidelines for Mamiviet Restaurant App

## Build/Lint/Test Commands
- `npm run dev` - Start development server (port 8080)
- `npm run build` - Production build
- `npm run build:dev` - Development mode build
- `npm run lint` - Run ESLint
- `npm run preview` - Preview production build
- **No test framework configured** - no test commands available

## Code Style Guidelines

### TypeScript Configuration
- Relaxed TypeScript: `noImplicitAny: false`, `strictNullChecks: false`, `strict: false`
- `any` type usage is common due to relaxed settings
- Path aliases: `@/*` maps to `./src/*`

### Imports & Structure
- Mix of absolute imports (`@/components/...`) and relative imports (`./pages/...`)
- Group external libraries first, then internal imports
- Services use object-based exports: `export const serviceName = { method1, method2 }`

### Naming Conventions
- Components: PascalCase (`Header`, `LoginModal`)
- Functions: camelCase (`getProfile`, `updateProfile`)
- Files: PascalCase for components, camelCase for utilities/services
- Constants: UPPER_SNAKE_CASE (`USER_TOKEN_KEY`)

### Component Patterns
- Function components with arrow syntax preferred
- Props destructuring in function parameters
- Hooks: `useState`, `useEffect`, `useCallback` commonly used

### Internationalization (CRITICAL)
- **ALL user-facing text must use translation keys** - never hardcode text
- Primary language: German (`de.json`), Secondary: English (`en.json`)
- Use `useTranslation()` hook: `const { t } = useTranslation()`
- Update both locale files when adding new text

### Error Handling
- Minimal error handling - mostly returns API responses directly
- Async operations use `try/catch` sparingly
- API errors handled at component level

### Cursor Rules
- All text in code must use translation keys, no hardcoding Vietnamese/German/English
- German primary, English secondary for all UI content
- Update `src/lib/locales/de.json` and `en.json` for new text

### Additional Notes
- Uses React 18 with shadcn/ui components
- State management: React Context + TanStack Query
- Authentication: Separate user/admin tokens in localStorage