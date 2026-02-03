# Frontend Documentation

## Overview

The IPAM frontend is a Vue.js 3 Single Page Application (SPA) built with TypeScript, providing a modern and responsive user interface for IP address management.

## Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | 3.5 | UI framework with Composition API |
| TypeScript | 5.8 | Type-safe JavaScript |
| Vite | 6.3 | Build tool and dev server |
| Pinia | 2.1 | State management |
| Vue Router | 4.5 | Client-side routing |
| Vitest | 4.0 | Unit testing |

## Project Structure

```
frontend/
├── Dockerfile              # Production multi-stage build
├── Dockerfile.dev          # Development with HMR
├── package.json            # Dependencies and scripts
├── vite.config.ts          # Vite configuration
├── vitest.config.ts        # Test configuration
├── tsconfig.json           # TypeScript configuration
├── index.html              # Entry HTML
├── docker/
│   └── nginx.conf          # Production nginx config
├── public/                 # Static assets
└── src/
    ├── main.ts             # Application entry point
    ├── App.vue             # Root component
    ├── style.css           # Global styles
    ├── components/         # Reusable components
    │   └── HelloWorld.vue
    ├── views/              # Page components
    │   ├── Login.vue
    │   ├── Dashboard.vue
    │   ├── IpAddresses.vue
    │   ├── AuditLogs.vue
    │   └── Settings.vue
    ├── stores/             # Pinia state stores
    │   ├── auth.ts
    │   ├── auth.test.ts
    │   ├── ipAddresses.ts
    │   └── ipAddresses.test.ts
    ├── router/             # Vue Router config
    │   └── index.ts
    ├── services/           # API services
    │   └── api.ts
    ├── types/              # TypeScript types
    │   └── index.ts
    └── utils/              # Utility functions
        ├── validation.ts
        └── validation.test.ts
```

## Pages

### Login (`/login`)
- **Access**: Public
- **Features**: Email/password authentication
- **Components**: Form validation, error messages
- **Redirect**: Dashboard on success

### Dashboard (`/`)
- **Access**: Authenticated users
- **Features**: Statistics overview
- **Data**: Total IPs, allocated count, available count

### IP Addresses (`/ip-addresses`)
- **Access**: Authenticated users
- **Features**: 
  - View all IP addresses (table)
  - Search and filter
  - Add new IP (admin only)
  - Edit IP (admin only)
  - Delete IP (admin only)
- **Components**: Modal dialogs, pagination

### Audit Logs (`/audit-logs`)
- **Access**: Admin only
- **Features**:
  - View all audit entries
  - Filter by action type
  - Search by IP/user

### Settings (`/settings`)
- **Access**: Admin only
- **Features**:
  - User management
  - Create new users
  - Update user roles
  - Delete users

## State Management

### Auth Store (`stores/auth.ts`)

Manages authentication state and user session using Vue 3 Composition API.

```typescript
// State
const user = ref<User | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
const initialized = ref(false)  // Tracks if auth state has been checked

// Computed
const isAuthenticated = computed(() => !!user.value)
const isAdmin = computed(() => user.value?.role === 'admin')
const isOperator = computed(() => ['admin', 'operator'].includes(user.value?.role || ''))

// Actions
login(credentials: LoginCredentials): Promise<AuthResponse>
logout(): Promise<void>
fetchUser(): Promise<void>       // Fetches current user from /auth/user
initialize(): Promise<void>      // Called on app start, checks localStorage token
```

**Key Features:**
- `initialized` flag prevents multiple auth checks on page load
- Token stored in `localStorage` with expiry timestamp
- Automatic token refresh before expiration
- Graceful handling of 401 errors during initialization

### IP Addresses Store (`stores/ipAddresses.ts`)

Manages IP address data and operations.

```typescript
interface IpAddressesState {
  items: IpAddress[];
  loading: boolean;
  error: string | null;
}

// Actions
fetchAll(): Promise<void>
create(data: CreateIpPayload): Promise<void>
update(id: number, data: UpdateIpPayload): Promise<void>
delete(id: number): Promise<void>
```

## Routing

### Route Configuration

```typescript
const routes = [
  { path: '/login', component: Login, meta: { public: true } },
  { path: '/', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/ip-addresses', component: IpAddresses, meta: { requiresAuth: true } },
  { path: '/audit-logs', component: AuditLogs, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/settings', component: Settings, meta: { requiresAuth: true, requiresAdmin: true } },
];
```

### Navigation Guards

```typescript
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore();
  
  // Initialize auth state if not done (checks localStorage token)
  if (!authStore.initialized) {
    await authStore.initialize();
  }

  const isAuthenticated = authStore.isAuthenticated;
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin);
  const isGuestRoute = to.matched.some(record => record.meta.guest);

  if (requiresAuth && !isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } });
  } else if (isGuestRoute && isAuthenticated) {
    next({ name: 'dashboard' });
  } else if (requiresAdmin && !authStore.isAdmin) {
    next({ name: 'dashboard' });
  } else {
    next();
  }
});
```

**Key Features:**
- Async guard waits for `initialize()` before checking auth
- `initialized` flag ensures auth check only runs once per session
- Supports redirect back to original URL after login

## API Integration

### API Client (`api/client.ts`)

Custom fetch-based API client with built-in authentication and token management.

```typescript
class ApiClient {
  private baseUrl: string
  private token: string | null
  private tokenExpiry: number | null
  
  // Token management
  setToken(token: string | null, expiresIn?: number): void
  getToken(): string | null
  isTokenExpiringSoon(): boolean  // True if < 5 min remaining
  isTokenExpired(): boolean       // True if past expiry time
  
  // HTTP methods
  get<T>(endpoint: string): Promise<T>
  post<T>(endpoint: string, data?: unknown): Promise<T>
  put<T>(endpoint: string, data?: unknown): Promise<T>
  delete<T>(endpoint: string): Promise<T>
}
```

**Key Features:**
- Token stored in `localStorage` with expiry timestamp
- Automatic `Authorization: Bearer` header injection
- Background token refresh timer (checks every 60 seconds)
- Proactive refresh when token expires in < 5 minutes
- 401 retry: attempts token refresh, then retries original request
- Special handling for `/auth/user` endpoint (no redirect on 401 during init)

### Automatic Token Refresh

The API client implements automatic token renewal to prevent session interruption:

| Feature | Description |
|---------|-------------|
| **Token Expiry Tracking** | Stores `token_expiry` timestamp in localStorage |
| **Proactive Refresh** | Refreshes token when < 5 minutes remaining |
| **Background Timer** | Checks token expiry every 60 seconds |
| **401 Retry** | On 401 error, attempts refresh then retries request |
| **Seamless UX** | Active users never see session expiration |

```typescript
// Token management methods
setToken(token: string | null, expiresIn?: number)  // Store token with expiry
isTokenExpiringSoon(): boolean  // True if < 5 min remaining
isTokenExpired(): boolean       // True if past expiry time
```

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/auth/login` | POST | User login |
| `/auth/logout` | POST | User logout |
| `/auth/refresh` | POST | Refresh access token |
| `/auth/me` | GET | Get current user |
| `/ip-addresses` | GET | List all IPs |
| `/ip-addresses` | POST | Create IP |
| `/ip-addresses/{id}` | PUT | Update IP |
| `/ip-addresses/{id}` | DELETE | Delete IP |
| `/audit-logs` | GET | List audit logs |
| `/users` | GET | List users (admin) |
| `/users` | POST | Create user (admin) |
| `/users/{id}` | PUT | Update user (admin) |
| `/users/{id}` | DELETE | Delete user (admin) |

## TypeScript Types

```typescript
// User type
interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'user';
  created_at: string;
}

// IP Address type
interface IpAddress {
  id: number;
  ip_address: string;
  label: string;
  comment: string | null;
  created_by: number;
  created_at: string;
  updated_at: string;
  creator?: User;
}

// Audit Log type
interface AuditLog {
  id: number;
  user_id: number;
  action: 'create' | 'update' | 'delete';
  entity_type: string;
  entity_id: number;
  old_values: object | null;
  new_values: object | null;
  ip_address: string;
  created_at: string;
  user?: User;
}
```

## Testing

### Test Setup

Tests use Vitest with Vue Test Utils:

```typescript
// vitest.config.ts
export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
  },
});
```

### Running Tests

```bash
# Run all tests
npm run test:run

# Run tests in watch mode
npm run test

# Run with coverage
npm run test:coverage
```

### Test Categories

| File | Tests | Description |
|------|-------|-------------|
| `auth.test.ts` | 4 | Auth store state management |
| `ipAddresses.test.ts` | 4 | IP store state management |
| `validation.test.ts` | 8 | IP address validation utils |

### Example Test

```typescript
import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from './auth';

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('should initialize with null user', () => {
    const store = useAuthStore();
    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
  });
});
```

## Docker Deployment

### Production Build

```dockerfile
# Build stage
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Serve stage
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    # SPA routing - redirect all requests to index.html
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
}
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `VITE_API_URL` | `http://localhost:8000/api` | Backend API base URL |

**Note**: Vite embeds environment variables at build time. For runtime configuration, use a config file or window object injection.

## Development

### Local Setup

```bash
# Install dependencies
cd frontend
npm install

# Start development server
npm run dev
# Runs on http://localhost:5173

# Build for production
npm run build

# Preview production build
npm run preview
```

### Docker Development

```bash
# Start with docker-compose
make dev

# Or manually
docker compose -f docker-compose.yml -f docker-compose.dev.yml up frontend
```

## Styling

### Global Styles

Located in `src/style.css`:
- CSS custom properties for theming
- Base element styles
- Utility classes

### Component Styles

Each Vue component uses scoped styles:
```vue
<style scoped>
.component-class {
  /* Styles only apply to this component */
}
</style>
```

## Best Practices

### Composition API
- Use `<script setup>` syntax
- Define props with `defineProps<T>()`
- Define emits with `defineEmits<T>()`
- Use composables for reusable logic

### State Management
- Use Pinia stores for shared state
- Keep component state local when possible
- Use computed properties for derived state

### TypeScript
- Define interfaces for all data structures
- Use strict mode
- Avoid `any` type

### Error Handling
- Use try/catch in async functions
- Display user-friendly error messages
- Log errors for debugging

---

*Last updated: February 3, 2026*
