# Endpoints Needed for User Detail View

## User Details
- **GET** `/api/admin/students/{id}` - Get detailed information about a specific student
- **GET** `/api/admin/teachers/{id}` - Get detailed information about a specific teacher

## User Groups
- **GET** `/api/admin/students/{id}/groups` - Get groups a specific student belongs to

## Exam Summary
- **GET** `/api/admin/students/{id}/exams` - Get exam data for a specific student (upcoming, completed, next exam)

## Payment Summary
- **GET** `/api/admin/students/{id}/payments` - Get payment information for a specific student

## Admin Notes
- **GET** `/api/admin/users/{id}/notes` - Get admin notes for a specific user
- **POST** `/api/admin/users/{id}/notes` - Save admin notes for a specific user

## Update User Profile
- **PUT** `/api/admin/students/{id}` - Update student information
- **PUT** `/api/admin/teachers/{id}` - Update teacher information