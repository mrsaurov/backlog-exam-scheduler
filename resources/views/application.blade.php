<html>
    <head>
        <style>
            @page { margin: 80px; }
            table {
                width: 100%;
            }
            tr {
                border: solid;
                border-color: black;
            }
            td {
                border: solid;
                border-color: black;
            }
            .div-table {
                display: table;         
                width: auto;         
                background-color: #eee;         
                border: 1px solid #666666;         
                border-spacing: 5px; /* cellspacing:poor IE support for  this */
            }
            .div-table-row {
                display: table-row;
                width: auto;
                clear: both;
            }
            .div-table-col {
                float: left; /* fix for  buggy browsers */
                display: table-column;         
                width: 200px;         
                background-color: #ccc;  
            }
        </style>
    </head>
    <body>
        <div>
            <p><b>Date:</b> {{date('d-m-Y')}}</p>
            <p>To<br>
            Head<br>
            Department of {{$exam->department}}<br>
            Rajshahi University of Engineering &amp; Technology</p>
            <p><b>Medium:</b>&nbsp;Course advisor, {{$exam->series}} series, Department of {{$exam->department}}, RUET.</p>
            <p><b>Subject:</b>&nbsp;Permission to attend the {{$exam->exam_name}}.</p>
            <p>Sir,<br>
            With due respect, I am {{$student['name']}}, Roll no.: {{$student['roll']}}, Registration no.: {{$student['registration']}}, a regular student of your department. I want to participate in the following subjects in the upcoming {{$exam->exam_name}}.  </p>
            
            <p>
            <table>
                <tbody>
                    <tr>
                        <td>SL. No.</td>
                        <td>Course Code</td>
                        <td>Course Title</td>
                    </tr>
                    @foreach($courses as $course)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$course->course_code}}</td>
                        <td>{{$course->course_title}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </p>
            <p>
                So, I pray and hope that you will be kind enough to give me permission to participate in the above mentioned examinations in the upcoming {{$exam->exam_name}}.
            </p>
            <br>
            <br>
            <p>Your's faithfully</p>
            <br>
            <br>
            <br>
            <br>
            <p>{{$student['name']}}<br>
            Roll no.: {{$student['roll']}}<br>
            Registration No.: {{$student['registration']}}</p>
        </div>
    </body>
</html>