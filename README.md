# TTMed — Medical Practice Management System

A multi-tenant SaaS platform for private medical centres in Trinidad and Tobago.

## Overview

TTMed is a hosted platform that allows independent medical centres to subscribe and manage their entire operation through a single secure application. Each medical centre operates in complete isolation from every other tenant.

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | React 18 + TypeScript via Inertia.js |
| Database | MySQL 8 (database-per-tenant) |
| Cache | Redis 7 |
| Queues | Redis via Laravel Horizon |
| Real-time | Laravel Reverb (WebSockets) |
| PDF | DomPDF (server-side Blade templates) |
| Email | SMTP2GO |
| File Storage | Private local server storage |
| Web Server | Nginx + PHP-FPM 8.3 (HestiaCP managed) |
| Deployment | Native on Hestia VPS (no Docker) |

## Quick Start (Development)

```bash
cp .env.example .env
# Configure .env with local MySQL and Redis credentials
composer install
npm install && npm run dev
php artisan key:generate
php artisan migrate --database=platform
php artisan db:seed --class=PlatformSeeder
php artisan reverb:start &
php artisan horizon &
```

Access the platform admin portal at: `http://localhost/platform`

## Documentation

| Document | Description |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | System architecture and component design |
| [DATABASE.md](DATABASE.md) | Database design and entity relationships |
| [SECURITY.md](SECURITY.md) | Security architecture and controls |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Deployment configuration and procedures |
| [IMPLEMENTATION_ROADMAP.md](IMPLEMENTATION_ROADMAP.md) | Development phases and task breakdown |
| [IMPLEMENTATION_PROGRESS.md](IMPLEMENTATION_PROGRESS.md) | Current build status |

## Development Phases

| Phase | Description | Status |
|---|---|---|
| 0 | Architecture and planning | Complete |
| 1 | Foundation, Docker, auth, permissions | Pending |
| 2 | Medical centre administration | Pending |
| 3 | Patient management | Pending |
| 4 | Reception and queues | Pending |
| 5 | Nursing and triage | Pending |
| 6 | Doctor consultations | Pending |
| 7 | Prescriptions and documents | Pending |
| 8 | Appointments | Pending |
| 9 | Billing and cash management | Pending |
| 10 | Laboratory and inventory | Pending |
| 11 | Reporting | Pending |
| 12 | Security hardening and deployment | Pending |

## Jurisdiction

Designed for Trinidad and Tobago. Default currency: TTD. Default timezone: America/Port_of_Spain.
Legal compliance review against the Data Protection Act and relevant medical professional regulations must be completed before commercial deployment.
