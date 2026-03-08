<p>Dear {{ $student->name }},</p>

<p>You currently have a shortage of attendance (<strong>{{ $percentage }}%</strong>) in <strong>{{ $sectionName
        }}</strong> taken by <strong>{{ $facultyName }}</strong>.</p>

<p>Please meet with the corresponding faculty ASAP to discuss your academic standing.</p>

<p>Thank you,<br>
    {{ env('APP_NAME', 'Administration') }}</p>