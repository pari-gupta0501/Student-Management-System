# Project documentation notes

## Title
Student Management System — A PHP and MySQL Based Web Application

## Abstract
This application manages student profiles through a browser interface. PHP handles server-side form processing and MySQL stores student records persistently. The system supports add, view, update, delete, search, department filtering and CSV export. It uses prepared statements and basic form protections.

## Architecture
Browser (HTML/CSS forms) → Apache/PHP application → PDO database connection → MySQL `students` table → PHP renders updated HTML.

## Modules
1. Student registration
2. Student listing
3. Student update
4. Student deletion
5. Search and department filtering
6. Dashboard statistics
7. CSV export

## Data dictionary
- id: auto-increment primary key
- name: student full name
- roll_number: unique institutional roll number
- email: unique email address
- phone: optional contact
- course: course/programme
- department: academic department
- semester: integer from 1 to 8
- created_at / updated_at: timestamps

## Limitations and future scope
The prototype does not include production-grade admin login, user roles, attendance, fees, examination results or audit logs. Future work can add authentication, role permissions, attendance, fees, results, backups and reporting.

## Testing checklist
Test add, required-field validation, duplicate roll/email rejection, edit, delete, search, department filter, CSV export and persistence after page refresh. Mark cases passed only after running them locally.
