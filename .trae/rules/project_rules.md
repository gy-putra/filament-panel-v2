# Project Rules & Development Guidelines for Laravel Project

## 1. Purpose
This document defines the **technical rules, architectural conventions, and quality standards** for all development activities within the Laravel-based system.  
The goal is to maintain a **consistent, secure, and scalable** project structure that ensures long-term maintainability and clarity across all modules.

---

## 2. Project Structure & Architecture

### 2.1 Folder Structure
Follow the official Laravel architecture with clear separation of concerns:
app/
├── Models/
├── Services/
├── Actions/
├── Http/
│ ├── Controllers/
│ ├── Requests/
│ └── Middleware/
├── Filament/
│ ├── Resources/
│ ├── Pages/
│ └── Widgets/
database/
├── migrations/
├── seeders/
└── factories/
resources/
├── views/
└── js/

- **Services** handle reusable business logic.  
- **Actions** represent atomic operations (e.g., “PostTransactionAction”).  
- **Resources** represent Filament panels and data management layers.

### 2.2 Project Principles
- Keep controllers **thin**, models **smart**, and services **focused**.
- Apply **Repository or Service pattern** for complex business logic.
- Avoid placing logic directly in routes or Blade templates.

---

## 3. Database Design & Standards

### 3.1 Naming Conventions
- Table names use **snake_case plural** (e.g., `users`, `sales_orders`).
- Primary key: `id` (BIGINT, auto-increment).  
- Foreign key: `*_id` referencing parent table.  
- Use `created_at` and `updated_at` timestamps by default.

### 3.2 Data Integrity
- Always define foreign key constraints.  
- Use appropriate data types (`DECIMAL`, `DATE`, `ENUM`, etc.).  
- Never store computed data unless necessary for performance.

### 3.3 Migration Rules
- Each migration must be **reversible** using `down()` method.  
- Migrations must be atomic; one file per feature or table.  
- Avoid altering production tables manually.

---

## 4. Filament & Panel Management

### 4.1 Panel Organization
- Maintain separate Filament panels for distinct domains (e.g., `/admin`, `/dashboard`).  
- Use `$navigationGroup` and `$navigationSort` to organize resource visibility.  
- Each resource should belong to one logical group (e.g., "Finance", "Reports", "Master Data").

### 4.2 Resource Standards
- Each resource must contain:
  - **Form**: clean, minimal, and validated fields.
  - **Table**: consistent columns, searchable fields, and filters.
  - **Actions**: `Create`, `Edit`, `Delete`, `Export`, and optional `Import`.
  - **Widgets**: contextual summaries or statistics (optional).
- Use **listWithLineBreaks()** or custom formatters for multi-value fields.

---

## 5. Code & Logic Standards

### 5.1 Coding Style
- Follow **PSR-12** and Laravel conventions.
- Use meaningful variable names and avoid abbreviations.
- Limit function length to ~50 lines; extract logic when necessary.

### 5.2 Business Logic
- Place reusable business rules in `app/Services/` or `app/Actions/`.
- Each service should perform one responsibility (e.g., “CalculateOmzetService”).
- Reuse logic through dependency injection rather than duplication.

### 5.3 Validation
- Use **FormRequest** for all POST/PUT requests.
- Define validation logic in a dedicated request class.
- Include user-friendly error messages.

---

## 6. Data Flow & API Design

### 6.1 RESTful Standards
- Use resourceful routes (`Route::resource`) whenever possible.
- Follow standard HTTP verbs:
  - `GET` → Retrieve data  
  - `POST` → Create record  
  - `PUT/PATCH` → Update record  
  - `DELETE` → Remove record

### 6.2 API Response Structure
All API responses must follow a uniform structure:
```json
{
  "success": true,
  "data": {},
  "message": "Operation successful"
}