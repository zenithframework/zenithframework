# Security Policy

## Supported Versions

The following versions of Zenith Framework are currently receiving security updates:

| Version | Supported          |
| ------- | ------------------ |
| 3.0.x   | :white_check_mark: |
| 2.1.x   | :white_check_mark: |
| < 2.1   | :x:                |

## Reporting a Vulnerability

We take the security of Zenith Framework seriously. If you discover a security vulnerability, please follow these steps:

### **DO NOT** open a public issue or discussion

1. **Email us directly** at security@zenframework.dev (or create a private advisory on GitHub)
2. Include the following information:
   - A clear description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact assessment
   - Suggested fix (if you have one)
3. We will acknowledge receipt of your report within **48 hours**
4. We will provide a detailed response within **7 days** with our assessment and planned fix timeline
5. We request that you keep the vulnerability private until we release a patch
6. Once patched, we will publicly credit the reporter (unless anonymity is requested)

## Security Response Timeline

1. **Day 0-2**: Vulnerability reported and acknowledged
2. **Day 3-7**: Security team assesses impact and plans fix
3. **Day 8-14**: Fix developed and internally tested
4. **Day 15-21**: Patch released and security advisory published
5. **Day 21+**: Public disclosure (with reporter credit)

## Security Best Practices

### Configuration

1. **Never commit `.env` files** to version control
2. **Set `APP_DEBUG=false`** in production
3. **Use strong `APP_KEY`** - generate with `php zen key:generate`
4. **Configure proper CORS** headers for API routes
5. **Enable HTTPS** and set secure session cookies

```env
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key

DB_CONNECTION=mysql
DB_DATABASE=zen_prod
DB_USERNAME=zen_user
DB_PASSWORD=strong-password-here

SESSION_SECURE=true
SESSION_SAME_SITE=Strict
```

### Authentication & Authorization

1. **Always hash passwords** using `password_hash()` with `PASSWORD_DEFAULT` or `PASSWORD_BCRYPT`
2. **Use parameterized queries** via the QueryBuilder to prevent SQL injection
3. **Validate all user input** using the built-in Validator
4. **Implement CSRF protection** on all state-changing forms
5. **Use middleware** to protect routes

```php
// ✅ GOOD: Using validation
$validator = Validator::make($request->all(), [
    'email' => 'required|email|unique:users,email',
    'password' => 'required|string|min:8',
]);

// ✅ GOOD: Using parameterized queries
User::where('email', $email)->first();

// ❌ BAD: Raw SQL with user input
$qb->raw("SELECT * FROM users WHERE email = '$email'");
```

### Database Security

1. **Use prepared statements** - the QueryBuilder does this automatically
2. **Limit database permissions** - use least-privilege principle
3. **Enable query logging** in development only
4. **Run migrations in CI/CD** with proper credentials

### Session Security

1. **Regenerate session ID** after login: `Session::regenerate()`
2. **Set secure cookie flags** in production
3. **Use HTTP-only cookies** to prevent XSS theft
4. **Implement session timeouts** for sensitive applications

```php
// Session security configuration
Session::setConfig([
    'secure' => true,       // HTTPS only
    'http_only' => true,    // Prevent JavaScript access
    'same_site' => 'Strict', // CSRF protection
    'lifetime' => 7200,     // 2 hours
]);
```

### Cross-Site Scripting (XSS) Prevention

1. **Use template escaping** - `{{ $variable }}` escapes by default
2. **Use `{!! $variable !!}`** only with trusted, sanitized content
3. **Sanitize rich text** before storing or displaying

```php
// ✅ GOOD: Escaped output
<p>{{ $user->name }}</p>

// ❌ BAD: Unescaped user input
<p>{!! $user->comment !!}</p>
```

### Cross-Site Request Forgery (CSRF) Protection

Zenith Framework includes built-in CSRF protection. All POST, PUT, PATCH, and DELETE requests must include a valid CSRF token.

```php
// In your forms
<form method="POST" action="/users">
    @csrf
    <!-- form fields -->
</form>

// AJAX requests - include token in headers
fetch('/api/users', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

### Rate Limiting

Protect your application from brute-force attacks:

```php
// Rate limit login attempts
$limiter = new RateLimiter('login', $request->ip());
if ($limiter->tooManyAttempts(5, 60)) {
    return response()->json(['error' => 'Too many attempts'], 429);
}
```

### Content Security Policy (CSP)

Implement CSP headers to prevent XSS and data injection attacks:

```php
response()->withHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'");
```

## Built-in Security Features

### Enterprise Security (Zen 2.0+)

Zenith Framework includes advanced security features out of the box:

- **WAF (Web Application Firewall)** - Rule-based request filtering
- **DDoS Protection** - Traffic analysis and challenge-based blocking
- **IP Blocking** - Auto-ban, CIDR ranges, geo-blocking, ASN filtering
- **Rate Limiting** - Per-user, per-IP, with quota management
- **CSRF Protection** - Token-based form protection
- **Encryption** - AES-256-GCM and HMAC support
- **Two-Factor Authentication** - TOTP (Google Authenticator compatible)

### Core Security Middleware

| Middleware | Purpose |
|-----------|---------|
| `VerifyCsrfToken` | CSRF token validation |
| `EncryptCookies` | Cookie encryption |
| `TrustProxies` | Trusted proxy handling |
| `CheckForMaintenanceMode` | Maintenance mode |
| `SecurityHeaders` | Security header injection |

## Security Checklist

Before deploying to production:

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_KEY` is set and unique
- [ ] Database credentials are strong and not default
- [ ] Session cookies are configured as secure
- [ ] CORS policy is properly configured
- [ ] All user input is validated
- [ ] CSRF protection is enabled on all forms
- [ ] Passwords are hashed with `password_hash()`
- [ ] Error pages don't leak sensitive information
- [ ] HTTPS is enforced
- [ ] Rate limiting is configured
- [ ] File uploads are validated and sanitized
- [ ] Dependencies are up to date

## Common Vulnerabilities

### SQL Injection

**Prevented by:** Using QueryBuilder parameterized queries

```php
// ✅ SAFE
User::where('email', $email)->first();

// ❌ DANGEROUS
$qb->raw("SELECT * FROM users WHERE email = '$email'");
```

### XSS (Cross-Site Scripting)

**Prevented by:** Using `{{ }}` escaping in templates

```php
// ✅ SAFE
<p>{{ $user->input }}</p>

// ❌ DANGEROUS
<p>{!! $user->input !!}</p>
```

### CSRF (Cross-Site Request Forgery)

**Prevented by:** Using `@csrf` in forms

```php
<form method="POST" action="/users">
    @csrf
    <!-- fields -->
</form>
```

### Mass Assignment

**Prevented by:** Defining `$fillable` or `$guarded` on models

```php
class User extends Model
{
    // ✅ SAFE: Only these fields can be mass-assigned
    protected array $fillable = ['name', 'email', 'password'];
    
    // ❌ DANGEROUS: All fields assignable
    // protected array $fillable = [];
}
```

## Acknowledgments

We thank all security researchers who have responsibly disclosed vulnerabilities to help make Zenith Framework more secure.

## Contact

- **Security Email**: security@zenframework.dev
- **Documentation**: [GUIDE.md](./GUIDE.md)
- **Contributing**: [CONTRIBUTING.md](./CONTRIBUTING.md)
