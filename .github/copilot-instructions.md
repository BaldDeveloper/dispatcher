# Project Structure and Best Practices

## Directory Layout

- `/database/` (outside `public/`): Contains all backend PHP files for database access and business logic. **Never place sensitive PHP files inside the `public/` folder.**
- `/public/`: Contains only files that should be directly accessible by the web server (HTML, JS, CSS, and entry-point PHP files).
- `/public/assets/`: Static assets (images, fonts, etc.)
- `/public/js/`: JavaScript files
- `/public/css/`: Stylesheets

## Database Files
- All PHP files for database access (e.g., `TransportData.php`, `CustomerData.php`, etc.) must be in `/database/`.
- Do not duplicate or move these files into `/public/database/`.

## Includes/Requires
- When including database files in PHP scripts under `/public/`, always use the correct relative path:
  ```php
  require_once __DIR__ . '/../database/TransportData.php';
  ```
- Never use paths that reference `/public/database/`.

## Migration Steps
- If any database files are found in `/public/database/`, move them to `/database/`.
- Update all PHP scripts in `/public/` to reference the correct path.

## Why?
- Keeping backend code outside the public folder prevents direct web access and improves security.
- Consistent structure avoids confusion and errors like undefined methods or duplicate classes.

## Troubleshooting
- If you get errors about undefined methods or missing classes, check for duplicate files and ensure all includes use the correct path.

---

## Centralized Validation Logic
- All server-side validation logic (e.g., email, phone number) must be placed in `/includes/validation.php`.
- Use the constants defined in `validation.php` (e.g., `EMAIL_PATTERN`, `PHONE_PATTERN`) for HTML `pattern` attributes in forms. Reference these constants directly in the form markup to ensure consistency between client and server validation.
- Use the functions in `validation.php` (e.g., `is_valid_email`, `is_valid_phone`) for server-side validation in all PHP scripts. Always call these functions from a dedicated validation function (e.g., `validate_customer_fields`) in each form handler.
- Always require/include `validation.php` in any PHP file that performs validation for consistency.
- Sanitize and trim all input values before validation.
- Display error messages returned from validation clearly above or near the form, and highlight invalid fields using Bootstrap classes (e.g., `is-invalid`).

---

## UI Consistency Guidelines

### Tables and Lists
- When adding a new table (for listing data), use the same HTML structure, Bootstrap classes, and output escaping as in `customer-list.php`.
- For paginated lists, **always use the same pagination logic, GET parameters, page size selector, and Bootstrap pagination bar as in `customer-list.php`**. This includes:
  - Calculating page, pageSize, offset, and totalPages in PHP
  - Fetching only the relevant records for the current page
  - Displaying a page size selector (`<select name="pageSize">`) and navigation bar
  - Ensuring the UI and code structure match `customer-list.php` for all list pages (e.g., transport-list.php, pouch-list.php, etc.)
- Always use `<table class="table table-bordered table-hover mb-0">` and wrap tables in a responsive `<div class="table-responsive">`.
- Escape all output using `htmlspecialchars()` to prevent XSS.

### Required Fields in Forms
- For every required field in any form:
  - Always display an asterisk (<span class="text-danger">*</span>) next to the label, using either the <code>required</code> class or explicit markup.
  - Always display the message "Please fill out this field." using a Bootstrap <code>invalid-feedback</code> element directly below the field.
  - Use the HTML5 <code>required</code> attribute for all required fields.
  - Use the appropriate <code>pattern</code> attribute referencing validation constants for fields like email and phone.
  - Ensure the error message is visible when the field is invalid, matching the pattern used in <code>customer-edit.php</code> and <code>transport-edit.php</code>.
  - Maintain consistent styling and placement for required field messages across all forms in the project.
  - When adding required fields to forms, use the same validation logic, error messaging, and field styling as in <code>customer-edit.php</code>.
  - Display error messages clearly above or near the form.
  - Highlight required fields and errors using Bootstrap classes (e.g., <code>is-invalid</code>, <code>text-danger</code>).
  - Use server-side validation to check for required fields and display appropriate error messages.
  - After a successful add, always clear all form fields and display a success message, so the form is ready for a new entry.
  - Use a helper function (such as <code>render_invalid_feedback</code> in <code>form_helpers.php</code>) for rendering error messages for required fields, to ensure consistency across all forms.
  - **Every page with required fields must have a <code>validate_&lt;page name&gt;_fields</code> function, and must display the message "Please fill in all required fields." above the form if any required field is missing. This message must always be visible at the top of the form when required fields are missing, regardless of scroll position or which field is invalid.**

### Dropdowns
- Always include a default "Select" option as the first option in any new dropdown (`<select>`) added to the project. This option should have an empty value (`value=""`) and be selected by default when adding new records.
- The label should clearly indicate what is being selected (e.g., "Select Firm", "Select Gender").
- Ensure this is consistent for all dropdowns in add/edit forms, including those for locations, users, types, etc.
- When editing, pre-select the correct value if available; otherwise, default to the "Select" option.

---

## PHP Coding Standards

- Always define global/shared variables at the top of each PHP file to avoid undefined variable warnings and improve code clarity. For variables only used in a specific scope, define them near their usage.
- Follow consistent naming conventions for variables, functions, and classes.
- Use meaningful and descriptive names for all identifiers.
- Write comments and PHPDoc blocks for all functions, classes, and complex code sections.
- Maintain consistent indentation and spacing throughout the code.
- Use `===` and `!==` for comparisons to avoid type juggling issues.
- Sanitize and validate all external inputs (e.g., GET/POST data, file uploads) to prevent security vulnerabilities.

---
**Always follow this structure and these UI guidelines for all future development.**

## Form Reset on Successful Add
- Whenever adding a new row to the data for any table (e.g., customer, coroner, transport, etc.), **clear all form fields after a successful add** so the form is ready for a new entry. This should be the default behavior unless explicitly told otherwise by the user.
- The success message should still be displayed after the add.
- This applies to all add forms in the project for consistency and improved user experience.

## Error Highlighting for Time/Date Fields
- When validating time or date fields (e.g., datetime-local inputs), always:
  - Add the `is-invalid` Bootstrap class to any field with a validation error.
  - Display a `<div class="invalid-feedback">` directly below the field, showing the specific error message if present, or the default required message otherwise.
  - Use the same structure and logic as in `times-edit.php` (see transport-edit page) for all similar time/date validation on other pages.
- This ensures users receive clear, field-specific feedback and consistent UI/UX across all forms.
