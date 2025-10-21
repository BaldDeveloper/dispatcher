# ✅ Project Review Prompt Library

A reusable set of prompts for reviewing architecture, security, deployment readiness, schema design, and harness workflows. Each section is modular and can be adapted based on project phase or focus.

---

## 🔧 General Architecture

**Prompt:**  
@project Please review the current project implementation and identify areas for improvement.  
Focus on:
- Architectural soundness and scalability
- Maintainability and modularity
- Separation of concerns and routing clarity
- Reusability of components and workflows
- Auditability and privilege boundaries

Feel free to suggest alternative patterns or trade-offs worth considering.

---

## 🔐 Security & Privilege Audit

**Prompt:**  
Conduct a security audit of the project.  
Assess:
- Authentication and authorization flows
- Privilege escalation risks
- Exposure of sensitive data or endpoints
- Input validation and sanitization
- Logging and error handling for traceability

Highlight any misconfigurations or areas needing tighter controls.

---

## 🚀 Pre-Deployment Validation

**Prompt:**  
Review the project for deployment readiness.  
Validate:
- Environment variable usage and secrets management
- HTTPS and mixed content handling
- Error logging and fallback mechanisms
- CI/CD integration and rollback strategy
- Harness-friendly scaffolds for onboarding and testing

Flag any blockers or risks before production rollout.

---

## 🧱 Schema & Database Design Review

**Prompt:**  
Evaluate the current database schema and migration strategy.  
Focus on:
- Normalization and relational integrity
- Indexing and query efficiency
- Privilege-aware access patterns
- Migration safety and rollback options
- Audit trails and timestamping for key entities

Suggest improvements for scalability and maintainability.

---

## 🧪 Harness & Workflow Audit

**Prompt:**  
Review the modular workflows and harness integration.  
Assess:
- Form modes and context-driven behavior
- Validation consistency across includes
- Reproducibility of test cases and scaffolds
- Logging and auditability of user actions
- Clarity of onboarding instructions and walkthroughs

Recommend improvements for team usability and long-term success.

---

## 🧾 Codebase Review

**Prompt:**
Review the following code for:
- Logic or syntax errors
- Code improvements and optimizations
- Consistency in style and formatting
- Security vulnerabilities or bad practices
- Avoidance of hardcoded values
- Naming conventions and clarity
- Code duplication or redundancy
- Readability and maintainability
- Missing or unclear documentation

Please provide suggestions with explanations and, where practical, improved code snippets. For each finding include severity, a clear explanation, and a suggested fix (with a minimal copy-pastable code example when useful).

When presenting findings, use the following template for each item:

1) File / symbol: <path or symbol>
2) Category: <Logic|Security|Style|Duplication|Docs>
3) Issue: <short description>
4) Explanation: <why it’s an issue>
5) Severity: <Low|Medium|High|Critical>
6) Suggested fix: <text + code snippet if applicable>
7) Notes: <risk/rollback/migration steps>

Recommended reproducible checks to run during the review (include commands):
- Static analysis / linting (example): run project linter or PHPStan / PHPCS for PHP files
- Unit tests / quick smoke: run any available test suite or a small integration script
- Security scan: run a dependency/secret scan (composer audit, npm audit, or SCA tool)

Add this section to the prompt library so reviewers and automated assistants can use a consistent, actionable format when auditing code.
