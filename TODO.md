# API Endpoints Needed

## Users & Groups Panel

### Students Endpoints
- ✅ GET `/api/admin/students` - Get all students
- ✅ POST `/api/admin/students` - Create a new student
- ✅ GET `/api/admin/students/:id` - Get student details
- ✅ PUT `/api/admin/students/:id` - Update student information
- ✅ DELETE `/api/admin/students/:id` - Delete a student

### Teachers Endpoints
- ✅ GET `/api/admin/teachers` - Get all teachers
- ✅ POST `/api/admin/teachers` - Create a new teacher
- ✅ GET `/api/admin/teachers/:id` - Get teacher details
- ✅ PUT `/api/admin/teachers/:id` - Update teacher information
- ✅ DELETE `/api/admin/teachers/:id` - Delete a teacher

### Groups Endpoints
- ✅ GET `/api/admin/groups` - Get all groups
- ✅ POST `/api/admin/groups` - Create a new group
- ✅ GET `/api/admin/groups/:id` - Get group details
- ✅ PUT `/api/admin/groups/:id` - Update group information
- ✅ DELETE `/api/admin/groups/:id` - Delete a group
- ✅ PUT `/api/admin/groups/:id/members` - Update group members
- 🔄 PUT `/api/admin/groups/:id` - Update group information
- 🔄 DELETE `/api/admin/groups/:id` - Delete a group
- 🔄 PUT `/api/admin/groups/:id/members` - Update group members

### Courses Endpoints
- 🔄 GET `/api/admin/courses` - Get all courses
- 🔄 POST `/api/admin/courses` - Create a new course
- 🔄 GET `/api/admin/courses/:id` - Get course details
- 🔄 PUT `/api/admin/courses/:id` - Update course information
- 🔄 DELETE `/api/admin/courses/:id` - Delete a course

### Archive Endpoints
- 🔄 GET `/api/admin/archived/courses` - Get archived courses
- 🔄 GET `/api/admin/archived/groups` - Get archived groups
- 🔄 PUT `/api/admin/archived/courses/:id/restore` - Restore an archived course
- 🔄 PUT `/api/admin/archived/groups/:id/restore` - Restore an archived group

Legend:
- ✅ Implemented and working
- 🔄 Needs to be implemented