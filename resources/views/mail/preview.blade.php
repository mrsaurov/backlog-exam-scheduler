<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Preview Mail Template - {{$template->name}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row bg-dark text-white p-3 mb-4">
            <div class="col">
                <h2><i class="bi bi-eye-fill"></i> Mail Template Preview</h2>
                <p class="mb-0">Template: {{$template->name}} | Exam: {{$template->exam->examname}}</p>
            </div>
            <div class="col-auto">
                <button onclick="window.close()" class="btn btn-light me-2">
                    <i class="bi bi-x"></i> Close
                </button>
                <a href="/mail/{{$template->exam_id}}/edit/{{$template->id}}" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit Template
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Mail Content Preview -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-envelope"></i> Email Preview</h5>
                    </div>
                    <div class="card-body">
                        <!-- Email Header -->
                        <div class="border p-3 mb-3" style="background-color: #f8f9fa;">
                            <div class="row">
                                <div class="col-sm-2"><strong>Subject:</strong></div>
                                <div class="col-sm-10">{{$template->subject}}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-sm-2"><strong>Type:</strong></div>
                                <div class="col-sm-10">
                                    @if($template->type === 'general')
                                        <span class="badge bg-primary">General Mail</span>
                                        <small class="text-muted">(All teachers assigned to this exam)</small>
                                    @else
                                        <span class="badge bg-warning text-dark">Customized Mail</span>
                                        <small class="text-muted">(Teachers assigned to specific courses)</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Email Body -->
                        <div class="border p-4" style="background-color: white; min-height: 300px;">
                            <div style="font-family: Arial, sans-serif; line-height: 1.6;">
                                {!! nl2br(e($template->content)) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Info -->
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h6><i class="bi bi-info-circle"></i> Template Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Created:</strong> {{$template->created_at->format('d M Y, h:i A')}}</p>
                                <p><strong>Last Modified:</strong> {{$template->updated_at->format('d M Y, h:i A')}}</p>
                            </div>
                            <div class="col-md-6">
                                @if($template->type === 'customized' && $template->assigned_courses)
                                    <p><strong>Assigned Courses:</strong></p>
                                    @foreach($template->assigned_courses as $courseId)
                                        @php
                                            $course = App\Models\Course::find($courseId);
                                        @endphp
                                        @if($course)
                                            <span class="badge bg-secondary me-1">{{$course->course_code}}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipients List -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-people"></i> Recipients ({{$recipients->count()}})</h5>
                    </div>
                    <div class="card-body">
                        @if($recipients->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recipients as $recipient)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{$recipient->name}}</h6>
                                            <small class="text-muted">{{ucwords(str_replace('_', ' ', $recipient->designation))}}</small>
                                        </div>
                                        <p class="mb-1 text-muted">{{$recipient->email}}</p>
                                        
                                        @if($template->type === 'customized' && $recipient->courseAssignments->count() > 0)
                                            <small>
                                                <strong>Courses:</strong>
                                                @foreach($recipient->courseAssignments as $assignment)
                                                    <span class="badge bg-light text-dark">{{$assignment->course->course_code}}</span>
                                                @endforeach
                                            </small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <!-- Send Mail Button (Future Implementation) -->
                            <div class="mt-3 d-grid">
                                <button class="btn btn-success" disabled>
                                    <i class="bi bi-send"></i> Send Mail to {{$recipients->count()}} Recipients
                                </button>
                                <small class="text-muted mt-2">
                                    <i class="bi bi-info-circle"></i> Mail sending functionality will be implemented in the next phase
                                </small>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2">No recipients found</p>
                                <small class="text-muted">
                                    @if($template->type === 'general')
                                        Make sure teachers are assigned to courses in this exam
                                    @else
                                        Make sure teachers are assigned to the selected courses
                                    @endif
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h6><i class="bi bi-graph-up"></i> Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary">{{$recipients->count()}}</h4>
                                    <small class="text-muted">Recipients</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info">
                                    @if($template->type === 'general')
                                        ALL
                                    @else
                                        {{count($template->assigned_courses ?? [])}}
                                    @endif
                                </h4>
                                <small class="text-muted">Courses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
