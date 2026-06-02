# Complete API Endpoints - Localhost

Base URL: **http://localhost:8000**

---

## Authentication

### 1. Login (Create Token)
```
POST http://localhost:8000/api/v1/auth/token
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password",
  "device_name": "Postman"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Token created successfully.",
  "data": {
    "token": "1|abc123...",
    "abilities": ["tickets:view", "tickets:create", "..."]
  }
}
```

### 2. Logout (Revoke Token)
```
DELETE http://localhost:8000/api/v1/auth/token
Authorization: Bearer {your_token}
```

---

## Tickets

### 3. List All Tickets
```
GET http://localhost:8000/api/v1/tickets
Authorization: Bearer {your_token}
```

### 4. List Tickets with Filters
```
GET http://localhost:8000/api/v1/tickets?filter[status]=open&filter[priority]=high
Authorization: Bearer {your_token}
```

**Available Filters:**
- `filter[status]` - Values: `open`, `in_progress`, `resolved`, `closed`
- `filter[priority]` - Values: `low`, `medium`, `high`, `urgent`
- `filter[customer_id]` - Integer (customer user ID)
- `filter[assigned_to]` - Integer (agent user ID)
- `filter[created_after]` - Date format: `YYYY-MM-DD` (e.g., `2026-01-01`)

### 5. List Tickets with Sorting
```
GET http://localhost:8000/api/v1/tickets?sort=-created_at
Authorization: Bearer {your_token}
```

**Available Sort Fields:**
- `created_at` (ascending) or `-created_at` (descending)
- `updated_at` (ascending) or `-updated_at` (descending)
- `priority` (ascending) or `-priority` (descending)
- `status` (ascending) or `-status` (descending)

### 6. List Tickets with Includes
```
GET http://localhost:8000/api/v1/tickets?include=customer,assignedAgent,comments
Authorization: Bearer {your_token}
```

**Available Includes:**
- `customer` - Include customer details
- `assignedAgent` - Include assigned agent details
- `comments` - Include all comments

### 7. List Tickets with Pagination
```
GET http://localhost:8000/api/v1/tickets?page=2&per_page=20
Authorization: Bearer {your_token}
```

### 8. Combined Query Example
```
GET http://localhost:8000/api/v1/tickets?filter[status]=open&sort=-created_at&include=customer&page=1&per_page=15
Authorization: Bearer {your_token}
```

### 9. Create Ticket
```
POST http://localhost:8000/api/v1/tickets
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": "Payment failed",
  "description": "I paid for the plan, but my account is not upgraded. Please help me resolve this issue.",
  "priority": "high"
}
```

### 10. Show Single Ticket
```
GET http://localhost:8000/api/v1/tickets/1
Authorization: Bearer {your_token}
```

### 11. Show Ticket with Includes
```
GET http://localhost:8000/api/v1/tickets/1?include=customer,comments
Authorization: Bearer {your_token}
```

### 12. Update Ticket (PATCH – Partial Update)
```
PATCH http://localhost:8000/api/v1/tickets/1
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": "Updated title",
  "description": "Updated description"
}
```

**For Admin/Agent (can also update):**
```json
{
  "title": "Updated title",
  "status": "in_progress",
  "priority": "urgent",
  "assigned_to": 2
}
```

### 13. Replace Ticket (PUT – Full Replacement)
```
PUT http://localhost:8000/api/v1/tickets/1
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": "Complete ticket title",
  "description": "Complete ticket description with at least 20 characters",
  "status": "resolved",
  "priority": "medium",
  "assigned_to": 2
}
```

### 14. Delete Ticket
```
DELETE http://localhost:8000/api/v1/tickets/1
Authorization: Bearer {your_token}
```

---

##  Ticket Comments

### 15. List Ticket Comments
```
GET http://localhost:8000/api/v1/tickets/1/comments
Authorization: Bearer {your_token}
```

### 16. Create Public Comment
```
POST http://localhost:8000/api/v1/tickets/1/comments
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "body": "This issue has been resolved. Thank you for your patience."
}
```

### 17. Create Internal Comment (Admin/Agent Only)
```
POST http://localhost:8000/api/v1/tickets/1/comments
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "body": "Internal note: Customer was refunded.",
  "is_internal": true
}
```

---

##  Users (Admin/Agent Only)

### 18. List All Users
```
GET http://localhost:8000/api/v1/users
Authorization: Bearer {your_token}
```

### 19. Show Single User
```
GET http://localhost:8000/api/v1/users/1
Authorization: Bearer {your_token}
```

---

##  Health Check

### 20. API Health Check
```
GET http://localhost:8000/api/v1/health
```

**Response:**
```json
{
  "status": "ok",
  "version": "v1"
}
```

---

##  Test User Credentials

| Role | Email | Password | Use Case |
|------|-------|----------|----------|
| **Admin** | admin@example.com | password | Full access to all endpoints |
| **Agent 1** | agent1@example.com | password | Can manage assigned tickets |
| **Agent 2** | agent2@example.com | password | Can manage assigned tickets |
| **Customer 1** | customer1@example.com | password | Can manage own tickets |
| **Customer 2** | customer2@example.com | password | Can manage own tickets |
| **Customer 3** | customer3@example.com | password | Can manage own tickets |
| **Customer 4** | customer4@example.com | password | Can manage own tickets |
| **Customer 5** | customer5@example.com | password | Can manage own tickets |

---

##  Quick Start Guide

### Step 1: Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password",
    "device_name": "My Device"
  }'
```

### Step 2: Copy the Token
From the response, copy the `data.token` value.

### Step 3: Use the Token
```bash
curl -X GET http://localhost:8000/api/v1/tickets \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Response Formats

### Success Response
```json
{
  "status": "success",
  "message": "Request successful.",
  "data": { ... }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error message.",
  "errors": { ... }
}
```

### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed.",
  "errors": {
    "title": ["The title must be at least 5 characters."],
    "description": ["The description must be at least 20 characters."]
  }
}
```

### Unauthorized (401)
```json
{
  "status": "error",
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "status": "error",
  "message": "This action is forbidden."
}
```

### Not Found (404)
```json
{
  "status": "error",
  "message": "Resource not found."
}
```

### Bad Request (400)
```json
{
  "status": "error",
  "message": "Unsupported filter(s): invalid_field. Allowed filters: status, priority, customer_id, assigned_to, created_after"
}
```

---

##  Rate Limits

- **Authentication endpoints**: 5 requests per minute per IP
- **API endpoints**: 60 requests per minute per user

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Retry-After: 60 (when limit exceeded)
```

---

## Testing Examples

### Example 1: Create and Update a Ticket
```bash
# 1. Login as customer
curl -X POST http://localhost:8000/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"customer1@example.com","password":"password","device_name":"Test"}'

# 2. Create ticket (use token from step 1)
curl -X POST http://localhost:8000/api/v1/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Ticket","description":"This is a test ticket with enough characters","priority":"high"}'

# 3. Update ticket
curl -X PATCH http://localhost:8000/api/v1/tickets/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Updated Test Ticket"}'
```

### Example 2: Filter and Sort Tickets
```bash
# Get open tickets sorted by priority
curl -X GET "http://localhost:8000/api/v1/tickets?filter[status]=open&sort=-priority" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Example 3: Add Comment to Ticket
```bash
curl -X POST http://localhost:8000/api/v1/tickets/1/comments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"This is a test comment"}'
```

---

## Additional Resources

- **Interactive API Documentation**: http://localhost:8000/docs
- **OpenAPI Specification**: `storage/app/private/scribe/openapi.yaml`
- **Postman Collection**: `postman_collection.json` (in project root)

---

##  Important Notes

1. **All API requests must include `Content-Type: application/json` header** (except GET requests)
2. **Protected endpoints require `Authorization: Bearer {token}` header**
3. **Customers can only access their own tickets**
4. **Agents can only update tickets assigned to them** (unless they have `tickets:update-any` ability)
5. **Admins have full access to all endpoints**
6. **Unsupported filters/sorts will return 400 Bad Request**
7. **Invalid filter values will return 400 Bad Request**
8. **Customers cannot set `status`, `priority`, or `assigned_to` fields**
9. **Customers cannot create internal comments**
10. **Date filters must use `YYYY-MM-DD` format**

---

## Common Errors and Solutions

### Error: "Unauthenticated"
**Solution**: Make sure you're including the `Authorization: Bearer {token}` header

### Error: "Unsupported filter(s)"
**Solution**: Check the allowed filters list and use only supported filters

### Error: "Invalid status value"
**Solution**: Use only: `open`, `in_progress`, `resolved`, `closed`

### Error: "Invalid priority value"
**Solution**: Use only: `low`, `medium`, `high`, `urgent`

### Error: "Invalid date format"
**Solution**: Use `YYYY-MM-DD` format (e.g., `2026-01-01`)

### Error: "Unsupported sort field"
**Solution**: Use only: `created_at`, `updated_at`, `priority`, `status` (with optional `-` prefix)

### Error: "This action is forbidden"
**Solution**: Check your user role and token abilities

---

##  Quick Reference

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api/v1/auth/token` | No | Login |
| DELETE | `/api/v1/auth/token` | Yes | Logout |
| GET | `/api/v1/tickets` | Yes | List tickets |
| POST | `/api/v1/tickets` | Yes | Create ticket |
| GET | `/api/v1/tickets/{id}` | Yes | Show ticket |
| PATCH | `/api/v1/tickets/{id}` | Yes | Update ticket (partial) |
| PUT | `/api/v1/tickets/{id}` | Yes | Replace ticket (full) |
| DELETE | `/api/v1/tickets/{id}` | Yes | Delete ticket |
| GET | `/api/v1/tickets/{id}/comments` | Yes | List comments |
| POST | `/api/v1/tickets/{id}/comments` | Yes | Create comment |
| GET | `/api/v1/users` | Yes | List users (admin/agent) |
| GET | `/api/v1/users/{id}` | Yes | Show user (admin/agent) |
| GET | `/api/v1/health` | No | Health check |
