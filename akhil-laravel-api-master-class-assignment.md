# Revatics Intern Assignment — Laravel API Master Class

## Objective

Build a production-style REST API in Laravel that demonstrates the main skills from the **Laracasts Laravel API Master Class**:

- JSON API response design
- API versioning
- Laravel Sanctum token authentication
- Token revocation and expiration awareness
- API Resources and Resource Collections
- Conditional includes
- Query filters and sorting
- Nested resources
- POST, PUT, PATCH, DELETE handling
- Policies and token abilities
- Granular permission rules
- Consistent JSON error responses
- API documentation using Scribe
- Postman-based manual testing

---

## Project: Helpdesk Ticket API

You will build a Laravel API for a small helpdesk system.

The API allows authenticated users to create and manage support tickets. Admin users can manage all tickets and users. Normal users can only access their own tickets.

---

## Tech Requirements

- Laravel 12.x
- PHP 8.2+
- Laravel Sanctum
- MySQL or SQLite
- Pest or PHPUnit for tests
- Scribe for API documentation
- No admin panels such as Filament/Nova
- API only. No Blade UI is required.

---

## Submission Deliverables

Submit a ZIP file with source code only.

Do **not** include:

- `vendor/`
- `node_modules/`
- `.env`
- database files unless specifically needed

Your ZIP should include:

```txt
helpdesk-api/
  README.md
  composer.json
  composer.lock
  package.json
  app/
  bootstrap/
  config/
  database/
  routes/
  tests/
```

---

## README Requirements

Your `README.md` must include:

1. Project setup steps
2. `.env` variables required
3. Migration and seeding commands
4. How to run the API locally
5. How to run tests
6. How to run Scribe documentation generation
7. List of available test users and their credentials
8. List of token abilities used in the project
9. API versioning approach
10. Postman testing notes

---

# Data Model

## User

Use Laravel's default `users` table and add:

- `role` enum/string: `admin | agent | customer`

Rules:

- Admin can manage all users and tickets.
- Agent can view assigned tickets and update ticket status.
- Customer can create and view only their own tickets.

---

## Ticket

Fields:

- `id`
- `user_id` — customer who created the ticket
- `assigned_to` — nullable user ID for agent assignment
- `title`
- `description`
- `status` — `open | in_progress | resolved | closed`
- `priority` — `low | medium | high | urgent`
- timestamps

Relationships:

- Ticket belongs to customer/user
- Ticket belongs to assigned agent
- Ticket has many comments

---

## Ticket Comment

Fields:

- `id`
- `ticket_id`
- `user_id`
- `body`
- `is_internal` boolean, default false
- timestamps

Rules:

- Customers can see only public comments.
- Agents and admins can see public and internal comments.
- Customers cannot create internal comments.

---

# Required API Routes

Use versioned routes under `/api/v1`.

```txt
POST   /api/v1/auth/token
DELETE /api/v1/auth/token
GET    /api/v1/tickets
POST   /api/v1/tickets
GET    /api/v1/tickets/{ticket}
PUT    /api/v1/tickets/{ticket}
PATCH  /api/v1/tickets/{ticket}
DELETE /api/v1/tickets/{ticket}
GET    /api/v1/tickets/{ticket}/comments
POST   /api/v1/tickets/{ticket}/comments
GET    /api/v1/users
GET    /api/v1/users/{user}
```

---

# Tasks and Marks

## Task 1 — API Foundation and Consistent Responses (8 marks)

Create a reusable API response helper or trait.

Success response format:

```json
{
  "status": "success",
  "message": "Ticket created successfully.",
  "data": {}
}
```

Error response format:

```json
{
  "status": "error",
  "message": "Validation failed.",
  "errors": {}
}
```

Requirements:

- Use appropriate HTTP status codes.
- Do not return raw Eloquent models directly.
- All API responses must be JSON.

---

## Task 2 — API Versioning (6 marks)

Implement API versioning using `/api/v1`.

Requirements:

- Routes must be grouped clearly.
- Controllers should be namespaced/grouped for V1.
- README must explain how a future `/api/v2` could be introduced without breaking existing clients.

---

## Task 3 — Sanctum Token Authentication (12 marks)

Implement token-based login and logout.

### `POST /api/v1/auth/token`

Accept:

```json
{
  "email": "customer@example.com",
  "password": "password",
  "device_name": "Postman"
}
```

Return:

```json
{
  "status": "success",
  "message": "Token created successfully.",
  "data": {
    "token": "plain-text-token",
    "abilities": ["tickets:view", "tickets:create"]
  }
}
```

Requirements:

- Use Laravel Sanctum personal access tokens.
- Use abilities based on user role.
- Protect private routes with `auth:sanctum`.
- Implement token revocation on logout.
- Include token expiration awareness in README, even if you keep default expiration.

---

## Task 4 — Migrations, Models, Factories, Seeders (10 marks)

Create migrations, models, factories, and seeders for:

- Users
- Tickets
- Ticket Comments

Seeder must create:

- 1 admin user
- 2 agent users
- 5 customer users
- At least 30 tickets
- At least 2 comments per ticket

Seeder credentials must be documented in README.

---

## Task 5 — API Resources and Payload Design (12 marks)

Create resource classes:

- `UserResource`
- `TicketResource`
- `TicketCollection`
- `TicketCommentResource`

Requirements:

- Do not expose sensitive user fields.
- Ticket resource should include:
  - id
  - title
  - description
  - status
  - priority
  - customer summary
  - assigned agent summary, if assigned
  - timestamps
- Use resource collections for lists.
- Include pagination metadata for ticket listing.

---

## Task 6 — Conditional Includes (8 marks)

Implement an `include` query parameter.

Examples:

```txt
GET /api/v1/tickets/1?include=customer,assignedAgent,comments
GET /api/v1/tickets?include=customer
```

Supported includes:

- `customer`
- `assignedAgent`
- `comments`

Requirements:

- Only load supported includes.
- Ignore or reject unsupported includes consistently.
- Use conditional resource loading, not manual array duplication.
- Avoid N+1 queries.

---

## Task 7 — Filtering (10 marks)

Implement filtering on ticket listing.

Examples:

```txt
GET /api/v1/tickets?filter[status]=open
GET /api/v1/tickets?filter[priority]=urgent
GET /api/v1/tickets?filter[customer_id]=5
GET /api/v1/tickets?filter[assigned_to]=2
GET /api/v1/tickets?filter[created_after]=2026-01-01
```

Requirements:

- Create a dedicated filter class, for example `TicketFilter`.
- Map query string filters to methods.
- Validate allowed filters.
- Normal customers must not be able to use `customer_id` to view other users' tickets.

---

## Task 8 — Sorting (8 marks)

Implement sorting on ticket listing.

Examples:

```txt
GET /api/v1/tickets?sort=created_at
GET /api/v1/tickets?sort=-created_at
GET /api/v1/tickets?sort=priority,-created_at
```

Requirements:

- Allow sorting only by:
  - `created_at`
  - `updated_at`
  - `priority`
  - `status`
- `-field` means descending order.
- Unsupported sort fields should return a clear 400 JSON error.
- Sorting must work together with filters and pagination.

---

## Task 9 — Create Ticket with POST (8 marks)

Implement:

```txt
POST /api/v1/tickets
```

Request:

```json
{
  "title": "Payment failed",
  "description": "I paid for the plan, but my account is not upgraded.",
  "priority": "high"
}
```

Validation:

- title: required, string, min 5, max 120
- description: required, string, min 20
- priority: required, in low, medium, high, urgent

Rules:

- Customer can create tickets only for themselves.
- Agent cannot create a ticket for a customer unless token has ability `tickets:create-any`.
- Admin can create tickets for anyone.

---

## Task 10 — PUT vs PATCH Update Behaviour (10 marks)

Implement both:

```txt
PUT /api/v1/tickets/{ticket}
PATCH /api/v1/tickets/{ticket}
```

### PUT requirement

PUT should behave like full replacement.

Required fields:

- title
- description
- status
- priority
- assigned_to

If a required field is missing, return 422.

### PATCH requirement

PATCH should behave like partial update.

Rules:

- Only update provided fields.
- Missing fields should remain unchanged.
- Customers cannot update status, priority, or assigned_to.
- Agents can update status and priority for assigned tickets.
- Admin can update all fields.

---

## Task 11 — Delete Ticket (5 marks)

Implement:

```txt
DELETE /api/v1/tickets/{ticket}
```

Rules:

- Customer can delete their own ticket only while status is `open`.
- Agent cannot delete tickets.
- Admin can delete any ticket.

Return `204 No Content` or a consistent success response.

---

## Task 12 — Nested Resource: Ticket Comments (8 marks)

Implement:

```txt
GET  /api/v1/tickets/{ticket}/comments
POST /api/v1/tickets/{ticket}/comments
```

Rules:

- Customers can see only public comments.
- Agents/admins can see public and internal comments.
- Customers can create public comments only.
- Agents/admins can create public or internal comments.
- Comment body validation: required, string, min 3, max 2000.

---

## Task 13 — Policies and Token Abilities (12 marks)

Create policies for:

- Ticket
- TicketComment
- User

Use both:

- Laravel policies for user/resource ownership rules
- Sanctum token abilities for API permission scope

Required abilities:

```txt
tickets:view
tickets:create
tickets:update
tickets:delete
tickets:create-any
tickets:update-any
tickets:delete-any
comments:view
comments:create
comments:create-internal
users:view
users:manage
```

Examples:

- A customer token should not include `users:view`.
- An agent token may include `tickets:update` but not `tickets:delete-any`.
- An admin token can include `*` or all abilities.

---

## Task 14 — Principle of Least Privilege (5 marks)

Review your validation and update logic so users cannot update fields they should not control.

Required examples:

- Customer cannot set `assigned_to`.
- Customer cannot set `status`.
- Customer cannot create internal comments.
- Agent cannot update tickets not assigned to them unless they have the correct ability.
- API must ignore or reject forbidden attributes consistently.

Document your approach in README.

---

## Task 15 — Error Handling (8 marks)

Implement consistent JSON errors for:

- 400 bad query parameter
- 401 unauthenticated
- 403 forbidden
- 404 not found
- 422 validation failed
- 500 unexpected server error

Requirements:

- Override or configure exception handling where needed.
- Validation errors must return JSON.
- Unauthenticated API requests must not redirect to login.
- Do not expose stack traces in production responses.

---

## Task 16 — API Documentation with Scribe (8 marks)

Install and configure Scribe.

Requirements:

- Document all API endpoints.
- Include authentication instructions.
- Include request examples.
- Include sample responses.
- Generate documentation using:

```bash
php artisan scribe:generate
```

README must explain where to access generated docs.

---

## Task 17 — Postman Collection (5 marks)

Create a Postman collection or exported environment file.

Minimum collection sections:

- Auth
- Tickets
- Ticket Comments
- Users
- Error Examples

Postman should include:

- Login request
- Token variable usage
- At least one request using filters
- At least one request using sorting
- One forbidden request example

---

## Task 18 — Automated Tests (15 marks)

Write feature tests for:

1. User can generate token with valid credentials.
2. Invalid login returns 422 or 401 JSON error.
3. Unauthenticated user cannot list tickets.
4. Customer can create own ticket.
5. Customer cannot view another customer's ticket.
6. Admin can view all tickets.
7. Customer cannot update `status`.
8. Agent can update assigned ticket.
9. Agent cannot update unassigned ticket.
10. DELETE follows role rules.
11. Include parameter loads comments.
12. Filtering by status works.
13. Sorting descending by created_at works.
14. Unsupported filter or sort returns 400.
15. Scribe docs generation command is documented.

---

# Bonus Tasks (up to +15)

## Bonus 1 — Rate Limiting (+5)

Apply API rate limiting to auth and ticket routes.

Example:

- Auth token endpoint: stricter limit
- Ticket listing: normal API limit

Document the limits in README.

---

## Bonus 2 — API Health Endpoint (+3)

Add:

```txt
GET /api/v1/health
```

Response:

```json
{
  "status": "ok",
  "version": "v1"
}
```

---

## Bonus 3 — Soft Deletes (+4)

Add soft deletes for tickets.

Rules:

- Admin can restore deleted tickets.
- Normal users cannot access deleted tickets.

---

## Bonus 4 — OpenAPI Export (+3)

Configure Scribe to generate an OpenAPI spec or Postman collection output and include it in the submission.

---

# Suggested Folder Structure

```txt
app/
  Http/
    Controllers/
      Api/
        V1/
          AuthTokenController.php
          TicketController.php
          TicketCommentController.php
          UserController.php
    Requests/
      Api/
        V1/
          StoreTicketRequest.php
          UpdateTicketRequest.php
          ReplaceTicketRequest.php
          StoreTicketCommentRequest.php
    Resources/
      V1/
        TicketResource.php
        TicketCollection.php
        TicketCommentResource.php
        UserResource.php
  Models/
    Ticket.php
    TicketComment.php
  Policies/
    TicketPolicy.php
    TicketCommentPolicy.php
    UserPolicy.php
  Support/
    ApiResponse.php
    Filters/
      TicketFilter.php
```

---

# Scoring Summary

| Area | Marks |
|---|---:|
| API foundation and responses | 8 |
| API versioning | 6 |
| Sanctum authentication | 12 |
| Database/model setup | 10 |
| API Resources and payloads | 12 |
| Conditional includes | 8 |
| Filtering | 10 |
| Sorting | 8 |
| POST create | 8 |
| PUT/PATCH behaviour | 10 |
| DELETE behaviour | 5 |
| Nested comments | 8 |
| Policies and token abilities | 12 |
| Least privilege | 5 |
| Error handling | 8 |
| Scribe documentation | 8 |
| Postman collection | 5 |
| Automated tests | 15 |
| **Total** | **160** |

Final score will be normalised to 100.

---

# Evaluation Notes

I will review the submission for:

- Whether the API is actually runnable
- Whether responses are consistent
- Whether raw Eloquent models are avoided in API responses
- Whether authorization is enforced at controller/policy level, not only hidden from responses
- Whether token abilities are meaningful and tested
- Whether filters/sorts are safe and validated
- Whether PUT and PATCH behave differently
- Whether error responses are API-friendly JSON
- Whether Scribe documentation is generated and usable
- Whether the README is complete enough for another developer to run the project

---

# Submission Checklist

Before submitting, confirm:

- [ ] `composer install` works
- [ ] `php artisan migrate --seed` works
- [ ] `php artisan test` passes
- [ ] `php artisan scribe:generate` works
- [ ] Postman collection is included
- [ ] No `vendor/`, `node_modules/`, `.env`, or local DB file in ZIP
- [ ] README includes all setup and testing instructions
- [ ] Auth tokens are not hardcoded in committed files
- [ ] Normal users cannot access other users' tickets
- [ ] Customers cannot create internal comments
- [ ] Unsupported filters/sorts return clear JSON errors
