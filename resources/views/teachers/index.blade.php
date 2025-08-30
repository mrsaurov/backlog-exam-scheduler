<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Teachers - {{$exam->examname}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Header -->
        <div class="row bg-primary text-white p-3 mb-4">
            <div class="col">
                <h2><i class="bi bi-people-fill"></i> Teacher Management</h2>
                <p class="mb-0">Exam: {{$exam->exam_name}}</p>
            </div>
            <div class="col-auto">
                <a href="/admin" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Admin
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="teacherTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="teachers-tab" data-bs-toggle="tab" data-bs-target="#teachers" type="button" role="tab">
                    <i class="bi bi-person-badge"></i> Teachers
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                    <i class="bi bi-diagram-3"></i> Course Assignments
                </button>
            </li>
        </ul>

        <div class="tab-content" id="teacherTabsContent">
            <!-- Teachers Tab -->
            <div class="tab-pane fade show active" id="teachers" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <!-- Add Teacher Form -->
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5><i class="bi bi-person-plus"></i> Add New Teacher</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/teachers">
                                    @csrf
                                    <input type="hidden" name="exam_id" value="{{$exam->id}}">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                                        <select class="form-select" id="designation" name="designation" required>
                                            <option value="">Select Designation</option>
                                            <option value="Professor">Professor</option>
                                            <option value="Associate Professor">Associate Professor</option>
                                            <option value="Assistant Professor">Assistant Professor</option>
                                            <option value="Lecturer">Lecturer</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                        <select class="form-select" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <option value="CSE">CSE</option>
                                            <option value="EEE">EEE</option>
                                            <option value="Mathematics">Mathematics</option>
                                            <option value="Physics">Physics</option>
                                            <option value="Chemistry">Chemistry</option>
                                            <option value="Humanities">Humanities</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-person-plus"></i> Add Teacher
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Teachers List -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="bi bi-list"></i> Teachers List</h5>
                            </div>
                            <div class="card-body">
                                @if($teachers->count() > 0)
                                    @php
                                        $teachersByDepartment = $teachers->groupBy('department');
                                    @endphp
                                    
                                    @foreach($teachersByDepartment as $department => $deptTeachers)
                                        <div class="mb-4">
                                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                                <i class="bi bi-building"></i> 
                                                {{ $department ?: 'No Department Specified' }}
                                                <span class="badge bg-primary ms-2">{{ $deptTeachers->count() }} teachers</span>
                                            </h6>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Email</th>
                                                            <th>Designation</th>
                                                            <th>Phone</th>
                                                            <th>Assigned Courses</th>
                                                            <th width="120">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($deptTeachers as $teacher)
                                                        <tr>
                                                            <td>
                                                                <strong>{{$teacher->name}}</strong>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">{{$teacher->email}}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary">
                                                                    {{ucwords(str_replace('_', ' ', $teacher->designation))}}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">{{$teacher->phone ?? 'N/A'}}</small>
                                                            </td>
                                                            <td>
                                                                @if($teacher->courseAssignments->count() > 0)
                                                                    @foreach($teacher->courseAssignments as $assignment)
                                                                        <div class="mb-1">
                                                                            <span class="badge bg-primary small">
                                                                                {{$assignment->course->course_code}} - {{$assignment->course->course_title}}
                                                                            </span>
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <span class="text-muted small">No assignments</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <button class="btn btn-outline-primary btn-sm edit-teacher" 
                                                                            data-teacher-id="{{$teacher->id}}"
                                                                            data-name="{{$teacher->name}}"
                                                                            data-email="{{$teacher->email}}"
                                                                            data-phone="{{$teacher->phone}}"
                                                                            data-designation="{{$teacher->designation}}"
                                                                            data-department="{{$teacher->department}}"
                                                                            title="Edit Teacher">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>
                                                                    <button class="btn btn-outline-danger btn-sm delete-teacher" 
                                                                            data-teacher-id="{{$teacher->id}}"
                                                                            data-teacher-name="{{$teacher->name}}"
                                                                            data-assignments-count="{{$teacher->courseAssignments->count()}}"
                                                                            title="Delete Teacher">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <!-- Summary -->
                                    <div class="row mt-4 pt-3 border-top">
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h5 class="text-primary">{{$teachers->count()}}</h5>
                                                <small class="text-muted">Total Teachers</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h5 class="text-success">{{$teachersByDepartment->count()}}</h5>
                                                <small class="text-muted">Departments</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                @php
                                                    $assignedTeachers = $teachers->filter(function($teacher) {
                                                        return $teacher->courseAssignments->count() > 0;
                                                    })->count();
                                                @endphp
                                                <h5 class="text-info">{{$assignedTeachers}}</h5>
                                                <small class="text-muted">With Assignments</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                @php
                                                    $totalAssignments = $teachers->sum(function($teacher) {
                                                        return $teacher->courseAssignments->count();
                                                    });
                                                @endphp
                                                <h5 class="text-warning">{{$totalAssignments}}</h5>
                                                <small class="text-muted">Total Assignments</small>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No teachers added yet</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Assignments Tab -->
            <div class="tab-pane fade" id="assignments" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <div>
                            <h5><i class="bi bi-diagram-3"></i> Course Teacher Assignments</h5>
                            <small>Assign up to 2 teachers per course. Teachers are sorted by department.</small>
                        </div>
                        <button type="button" id="saveAllAssignments" class="btn btn-success btn-lg" style="display: none;">
                            <i class="bi bi-check-circle"></i> Save All Changes
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="assignment-alerts"></div>
                        @if($courses->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%;">Course</th>
                                            <th style="width: 25%;">Teacher 1</th>
                                            <th style="width: 25%;">Teacher 2</th>
                                            <th style="width: 10%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($courses as $course)
                                            @php
                                                $courseTeachers = $assignments->where('course_id', $course->id)->sortBy('id');
                                                $teacher1 = $courseTeachers->first();
                                                $teacher2 = $courseTeachers->skip(1)->first();
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong class="text-primary">{{$course->course_code}}</strong>
                                                    </div>
                                                    <div class="text-muted small">{{$course->course_title}}</div>
                                                    @if($course->department)
                                                        <div class="text-muted small">Dept: {{$course->department}}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm teacher-select" 
                                                            data-course-id="{{$course->id}}" 
                                                            data-position="1"
                                                            data-original="{{$teacher1 ? $teacher1->teacher_id : ''}}">
                                                        <option value="">Select Teacher 1</option>
                                                        @foreach($teachers->groupBy('department') as $department => $deptTeachers)
                                                            <optgroup label="{{$department ?: 'No Department'}}">
                                                                @foreach($deptTeachers as $teacher)
                                                                    <option value="{{$teacher->id}}" 
                                                                            {{$teacher1 && $teacher1->teacher_id == $teacher->id ? 'selected' : ''}}>
                                                                        {{$teacher->name}} ({{$teacher->designation}})
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm teacher-select" 
                                                            data-course-id="{{$course->id}}" 
                                                            data-position="2"
                                                            data-original="{{$teacher2 ? $teacher2->teacher_id : ''}}">
                                                        <option value="">Select Teacher 2</option>
                                                        @foreach($teachers->groupBy('department') as $department => $deptTeachers)
                                                            <optgroup label="{{$department ?: 'No Department'}}">
                                                                @foreach($deptTeachers as $teacher)
                                                                    <option value="{{$teacher->id}}" 
                                                                            {{$teacher2 && $teacher2->teacher_id == $teacher->id ? 'selected' : ''}}>
                                                                        {{$teacher->name}} ({{$teacher->designation}})
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <span class="assignment-status" data-course-id="{{$course->id}}">
                                                        <i class="bi bi-check-circle text-success" title="No changes"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                <h4 class="text-muted mt-3">No Courses Available</h4>
                                <p class="text-muted">No courses are mapped to this exam. Please add courses to the exam first.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Assignment Summary -->
                @if($courses->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="bi bi-info-circle"></i> Assignment Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary">{{$courses->count()}}</h4>
                                    <small class="text-muted">Total Courses</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    @php
                                        $assignedCourses = $assignments->pluck('course_id')->unique()->count();
                                    @endphp
                                    <h4 class="text-success">{{$assignedCourses}}</h4>
                                    <small class="text-muted">Courses with Teachers</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info">{{$assignments->count()}}</h4>
                                    <small class="text-muted">Total Assignments</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning">{{$teachers->count()}}</h4>
                                    <small class="text-muted">Available Teachers</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fixed-bottom p-3" style="right: 20px; left: auto; width: auto;">
            <div class="btn-group-vertical">
                <a href="/mail/{{$exam->id}}" class="btn btn-primary">
                    <i class="bi bi-envelope"></i> Mail Management
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editTeacherModalLabel">
                        <i class="bi bi-pencil"></i> Edit Teacher
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTeacherForm" method="POST" action="/teachers">
                    @csrf
                    <input type="hidden" name="submit" value="update">
                    <input type="hidden" name="exam_id" value="{{$exam->id}}">
                    <input type="hidden" name="teacher_id" id="edit_teacher_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_designation" class="form-label">Designation <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_designation" name="designation" required>
                                <option value="">Select Designation</option>
                                <option value="Professor">Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Lecturer">Lecturer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_department" name="department" required>
                                <option value="">Select Department</option>
                                <option value="CSE">CSE</option>
                                <option value="EEE">EEE</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Physics">Physics</option>
                                <option value="Chemistry">Chemistry</option>
                                <option value="Humanities">Humanities</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Update Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Teacher Modal -->
    <div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTeacherModalLabel">
                        <i class="bi bi-trash"></i> Delete Teacher
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteTeacherForm" method="POST" action="/teachers">
                    @csrf
                    <input type="hidden" name="submit" value="delete">
                    <input type="hidden" name="exam_id" value="{{$exam->id}}">
                    <input type="hidden" name="teacher_id" id="delete_teacher_id">
                    
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning!</strong> This action cannot be undone.
                        </div>
                        
                        <p>Are you sure you want to delete the following teacher?</p>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6 id="delete_teacher_name" class="card-title"></h6>
                                <p id="delete_teacher_assignments" class="card-text text-muted"></p>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Note: All course assignments for this teacher will also be removed.
                            </small>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let pendingChanges = {};
            
            // Handle edit teacher button
            $('.edit-teacher').on('click', function() {
                const teacherId = $(this).data('teacher-id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const phone = $(this).data('phone');
                const designation = $(this).data('designation');
                const department = $(this).data('department');
                
                // Populate edit form
                $('#edit_teacher_id').val(teacherId);
                $('#edit_name').val(name);
                $('#edit_email').val(email);
                $('#edit_phone').val(phone || '');
                $('#edit_designation').val(designation);
                $('#edit_department').val(department || '');
                
                // Show modal
                $('#editTeacherModal').modal('show');
            });
            
            // Handle delete teacher button
            $('.delete-teacher').on('click', function() {
                const teacherId = $(this).data('teacher-id');
                const teacherName = $(this).data('teacher-name');
                const assignmentsCount = $(this).data('assignments-count');
                
                // Populate delete form
                $('#delete_teacher_id').val(teacherId);
                $('#delete_teacher_name').text(teacherName);
                
                if (assignmentsCount > 0) {
                    $('#delete_teacher_assignments').text(`This teacher has ${assignmentsCount} course assignment(s) that will be removed.`);
                } else {
                    $('#delete_teacher_assignments').text('This teacher has no course assignments.');
                }
                
                // Show modal
                $('#deleteTeacherModal').modal('show');
            });
            
            // Handle teacher selection change
            $('.teacher-select').on('change', function() {
                const courseId = $(this).data('course-id');
                const position = $(this).data('position');
                const teacherId = $(this).val();
                const originalValue = $(this).data('original');
                
                // Initialize course changes if not exists
                if (!pendingChanges[courseId]) {
                    pendingChanges[courseId] = {};
                }
                
                // Store the change
                pendingChanges[courseId][position] = teacherId;
                
                // Check if this is actually a change from original
                const isChanged = teacherId != originalValue;
                
                // Update status indicator
                updateCourseStatus(courseId);
                
                // Show/hide save button based on pending changes
                updateSaveButtonVisibility();
                
                // Validate teacher selections for this course
                validateTeacherSelections(courseId);
            });
            
            // Validate that same teacher isn't selected for both positions
            function validateTeacherSelections(courseId) {
                const teacher1 = $(`.teacher-select[data-course-id="${courseId}"][data-position="1"]`).val();
                const teacher2 = $(`.teacher-select[data-course-id="${courseId}"][data-position="2"]`).val();
                
                if (teacher1 && teacher2 && teacher1 === teacher2) {
                    showAlert('error', 'Same teacher cannot be assigned to both positions for the same course!');
                    // Reset the second selection
                    $(`.teacher-select[data-course-id="${courseId}"][data-position="2"]`).val('');
                    if (pendingChanges[courseId]) {
                        pendingChanges[courseId]['2'] = '';
                    }
                    updateCourseStatus(courseId);
                }
            }
            
            // Update course status indicator
            function updateCourseStatus(courseId) {
                const statusEl = $(`.assignment-status[data-course-id="${courseId}"]`);
                let hasChanges = false;
                
                if (pendingChanges[courseId]) {
                    for (let position in pendingChanges[courseId]) {
                        const currentValue = $(`.teacher-select[data-course-id="${courseId}"][data-position="${position}"]`).val();
                        const originalValue = $(`.teacher-select[data-course-id="${courseId}"][data-position="${position}"]`).data('original');
                        
                        if (currentValue != originalValue) {
                            hasChanges = true;
                            break;
                        }
                    }
                }
                
                if (hasChanges) {
                    statusEl.html('<i class="bi bi-exclamation-circle text-warning" title="Has unsaved changes"></i>');
                } else {
                    statusEl.html('<i class="bi bi-check-circle text-success" title="No changes"></i>');
                }
            }
            
            // Update save button visibility
            function updateSaveButtonVisibility() {
                let hasAnyChanges = false;
                
                $('.teacher-select').each(function() {
                    const currentValue = $(this).val();
                    const originalValue = $(this).data('original');
                    
                    if (currentValue != originalValue) {
                        hasAnyChanges = true;
                        return false; // break loop
                    }
                });
                
                if (hasAnyChanges) {
                    $('#saveAllAssignments').show();
                } else {
                    $('#saveAllAssignments').hide();
                }
            }
            
            // Handle save all assignments
            $('#saveAllAssignments').on('click', function() {
                const saveBtn = $(this);
                const originalText = saveBtn.html();
                
                // Show loading state
                saveBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Saving...');
                
                // Collect all changes
                const changes = [];
                $('.teacher-select').each(function() {
                    const courseId = $(this).data('course-id');
                    const position = $(this).data('position');
                    const currentValue = $(this).val();
                    const originalValue = $(this).data('original');
                    
                    if (currentValue != originalValue) {
                        changes.push({
                            course_id: courseId,
                            position: position,
                            teacher_id: currentValue,
                            original_teacher_id: originalValue
                        });
                    }
                });
                
                if (changes.length === 0) {
                    showAlert('info', 'No changes to save.');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                // Save all changes
                saveAllChanges(changes).then(function(success) {
                    if (success) {
                        showAlert('success', 'All assignments saved successfully!');
                        
                        // Switch to teachers tab after successful save
                        $('#teachers-tab').tab('show');
                        
                        // Reload page to refresh data
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        saveBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Save all changes function
            async function saveAllChanges(changes) {
                let allSuccessful = true;
                
                for (let change of changes) {
                    try {
                        const response = await $.ajax({
                            url: '/teachers/assign-teacher',
                            method: 'POST',
                            data: {
                                exam_id: {{ $exam->id }},
                                course_id: change.course_id,
                                teacher_id: change.teacher_id,
                                position: change.position,
                                _token: '{{ csrf_token() }}'
                            }
                        });
                        
                        if (!response.success) {
                            showAlert('error', `Error saving course assignment: ${response.message}`);
                            allSuccessful = false;
                            break;
                        }
                    } catch (error) {
                        showAlert('error', 'Error saving assignment. Please try again.');
                        allSuccessful = false;
                        break;
                    }
                }
                
                return allSuccessful;
            }
            
            // Show alert messages
            function showAlert(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 
                                 type === 'error' ? 'alert-danger' : 
                                 type === 'info' ? 'alert-info' : 'alert-warning';
                
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i> 
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                $('#assignment-alerts').html(alertHtml);
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    $('#assignment-alerts .alert').alert('close');
                }, 5000);
            }
            
            // Prevent selection of same teacher for both positions in same course
            $('.teacher-select').on('focus', function() {
                const courseId = $(this).data('course-id');
                const currentPosition = $(this).data('position');
                const otherPosition = currentPosition == 1 ? 2 : 1;
                const otherSelectedTeacher = $(`.teacher-select[data-course-id="${courseId}"][data-position="${otherPosition}"]`).val();
                
                // Enable all options first
                $(this).find('option').prop('disabled', false);
                
                // Disable the option that's selected in the other position
                if (otherSelectedTeacher) {
                    $(this).find(`option[value="${otherSelectedTeacher}"]`).prop('disabled', true);
                }
            });
        });
    </script>
</body>
</html>
