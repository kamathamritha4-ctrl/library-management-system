# Library Management System

Built using PHP, MySQL, HTML, and CSS.

## Project Goal
This system manages book cataloging, circulation (issue/return), student search, fines, and administrative controls for a college/school library.

## Functional Requirements

### 1) Fine and Due-Date Rules
- Standard borrowing period: **15 days** from issue date.
- Fine rule: **₹5 per day** after the due date.
- Grace for non-working return day:
  - If the effective return day is a **Sunday** or **government holiday**, move the due date to the **next working day**.
  - Fine starts only after this adjusted due date.

#### Fine Formula
- `overdue_days = max(0, actual_return_date - adjusted_due_date)`
- `fine_amount = overdue_days * 5`

### 2) Book Data Fields
Maintain these full fields for each book record:
1. Date of Accession
2. Accession Number (e.g., 1550, 1551)
3. Subject
4. Author
5. Title & Volume
6. Publisher
7. Year
8. Price (Rs)
9. Total
10. Bill No & Date
11. Supplier
12. Edition
13. Remarks

### 3) Student-Facing Visibility (Limited Fields)
Students should only see:
- Accession No
- Subject
- Author
- Title
- Copies Available

### 4) Issue-Time Full Book Details
When issuing a book using Book Accession Number, show full book details to verify correctness before final issue.

### 5) Overdue Notification Email
If a student has not returned a book after due date:
- Automatically calculate fine amount.
- Send reminder email to the student inbox with:
  - Student name/ID
  - Book accession number and title
  - Due date and current overdue days
  - Current accumulated fine

### 6) Admin Controls
Admin role should have full permissions:
- Add books
- Edit books
- Delete books
- Issue books
- Manage issued books / returns
- Export records (CSV/Excel)

### 7) UI/UX Expectations
- Clean, modern, and responsive interface.
- Works on desktop, tablet, and mobile.
- Clear role-based screens (Admin, Student, Faculty).

---

## Suggested Module Breakdown

### Module A: Authentication & Role Management
**Purpose:** Login/logout and role-based access control.
- User roles: Admin, Student, Faculty
- Session handling
- Route guards for protected pages

### Module B: Book Catalog Management
**Purpose:** Maintain all master data about books.
- Add/edit/delete book records
- Search by accession number, title, author, subject
- Validate required fields and unique accession number

### Module C: Student/Faculty Search Portal
**Purpose:** Public/restricted search with limited fields.
- Student/Faulty searchable catalog view
- Display only allowed fields for non-admin users

### Module D: Circulation (Issue & Return)
**Purpose:** Manage borrowing lifecycle.
- Issue by accession number
- Show complete book details at issue time
- Track issue date, due date, return date
- Update availability/copy count

### Module E: Fine Engine
**Purpose:** Reliable due-date adjustment and fine computation.
- 15-day due date calculation
- Sunday/holiday adjustment logic
- ₹5/day overdue fine calculation
- API/service function reusable across UI and reports

### Module F: Holiday Calendar Management
**Purpose:** Support due-date adjustment using real holiday data.
- Admin-maintained holiday list
- Import yearly government holiday list
- Utility to check working day vs holiday/weekend

### Module G: Notifications (Email)
**Purpose:** Alert students about overdue books and fines.
- Scheduled job (daily) to detect overdue issues
- Email template engine
- Delivery logging and retry handling

### Module H: Reports & Export
**Purpose:** Operational reporting and data export.
- Overdue books report
- Fine collection report
- Active issue report
- Export CSV/Excel/PDF

### Module I: UI Layer
**Purpose:** Responsive and user-friendly presentation.
- Shared layout/components
- Role-based dashboards
- Mobile-first styling

---

## Recommended Database Tables (High-Level)
- `users` (role, contact details)
- `students` / `faculty` (profile and academic data)
- `books` (all catalog metadata fields)
- `book_copies` or copy/quantity tracking columns
- `issues` (issue_date, due_date, returned_at, status)
- `holidays` (date, description, type)
- `fines` (issue_id, overdue_days, amount, paid_status)
- `notifications` (type, recipient, subject, status, sent_at)

---

## Non-Functional Requirements
- Input validation and server-side sanitization.
- SQL injection protection (prepared statements).
- Audit-friendly logs for admin actions.
- Timezone consistency for all date calculations.
- Backup strategy for catalog and transaction records.

---

## Implementation Notes for This Repository
Current pages indicate base modules already present:
- Admin: `add_book.php`, `edit_book.php`, `manage_books.php`, `issue_book.php`, `issued_book.php`, `dashboard.php`
- Search: `student/search.php`, `faculty/search.php`

Next improvements should prioritize:
1. Fine engine with Sunday/holiday adjustment.
2. Holiday table + admin maintenance UI.
3. Overdue email scheduler.
4. Export enhancements and responsive UI polishing.
