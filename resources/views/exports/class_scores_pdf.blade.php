<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Bảng điểm</title>
	<style>
		@page { margin: 18px 18px; }
		body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
		.header { margin-bottom: 10px; }
		.title { font-size: 18px; font-weight: 700; margin: 0 0 6px 0; }
		.meta { font-size: 12px; margin: 0; line-height: 1.4; }
		.meta strong { font-weight: 700; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #333; padding: 6px 6px; }
		th { background: #f2f2f2; text-align: center; font-weight: 700; }
		td { vertical-align: top; }
		td.num { text-align: right; }
		td.center { text-align: center; }
		.footer { margin-top: 10px; font-size: 11px; color: #444; }
	</style>
</head>
<body>
	@php
		$courseName = (string) data_get($class, 'courseVersion.course.course_name', data_get($class, 'courseVersion.course.name', ''));
		$semesterName = (string) data_get($class, 'semester.semester_name', data_get($class, 'semester.name', ''));
		$classCode = (string) data_get($class, 'class_code', data_get($class, 'code', ''));
		$statusName = (string) data_get($class, 'status.name', '');
	@endphp

	<div class="header">
		<div class="title">BẢNG ĐIỂM HỌC PHẦN</div>
		<p class="meta">
			<strong>Môn:</strong> {{ $courseName !== '' ? $courseName : '—' }}
			@if($classCode !== '')
				&nbsp;|&nbsp; <strong>Mã lớp:</strong> {{ $classCode }}
			@endif
			@if($semesterName !== '')
				&nbsp;|&nbsp; <strong>Học kỳ:</strong> {{ $semesterName }}
			@endif
			@if($statusName !== '')
				&nbsp;|&nbsp; <strong>Trạng thái:</strong> {{ $statusName }}
			@endif
		</p>
		<p class="meta">
			<strong>Thời gian xuất:</strong> {{ $generatedAt?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}
		</p>
	</div>

	<table>
		<thead>
			<tr>
				<th style="width:36px;">STT</th>
				<th style="width:92px;">Mã SV</th>
				<th>Họ tên</th>
				@foreach(($components ?? []) as $c)
					@php
						$w = isset($c['weight_percent']) ? (float) $c['weight_percent'] : 0.0;
						$name = (string) ($c['component_name'] ?? '');
					@endphp
					<th style="width:72px;">{{ $name !== '' ? $name : 'TP' }}<br><span style="font-weight:400;">({{ (int) round($w) }}%)</span></th>
				@endforeach
				<th style="width:86px;">Chuyên cần<br><span style="font-weight:400;">(%)</span></th>
				<th style="width:86px;">Tổng kết</th>
				<th style="width:90px;">Kết quả</th>
			</tr>
		</thead>
		<tbody>
			@forelse(($rows ?? []) as $idx => $r)
				@php
					$scores = is_array($r['scores'] ?? null) ? $r['scores'] : [];
				@endphp
				<tr>
					<td class="center">{{ $idx + 1 }}</td>
					<td class="center">{{ $r['student_code'] ?? '' }}</td>
					<td>{{ $r['full_name'] ?? '' }}</td>

					@foreach(($components ?? []) as $c)
						@php
							$cid = (int) ($c['component_id'] ?? 0);
							$v = $cid > 0 ? ($scores[$cid] ?? null) : null;
						@endphp
						<td class="num">{{ $v === null ? '' : (is_numeric($v) ? number_format((float)$v, 2, '.', '') : $v) }}</td>
					@endforeach

					<td class="num">{{ isset($r['attendance_percent']) ? number_format((float) $r['attendance_percent'], 0) : '0' }}</td>
					<td class="num">{{ $r['final_score'] === null ? '' : number_format((float)$r['final_score'], 2, '.', '') }}</td>
					<td class="center">{{ $r['final_status_text'] ?? '' }}</td>
				</tr>
			@empty
				<tr>
					<td colspan="{{ 6 + count($components ?? []) }}" class="center">Không có dữ liệu</td>
				</tr>
			@endforelse
		</tbody>
	</table>

	<div class="footer">
		<div>Ghi chú: Bảng điểm được xuất tự động từ hệ thống.</div>
	</div>
</body>
</html>
