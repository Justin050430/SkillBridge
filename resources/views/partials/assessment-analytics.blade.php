{{--
    Assessment Performance panel shown on the course edit pages.
    $analytics is produced by App\Services\CourseAnalyticsClient, which
    CONSUMES the Assessment module's web service
    (GET /api/assessments/course/{course}/stats). It is null when the service
    is unavailable (graceful degradation).
--}}

<div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <h2 class="text-lg font-semibold text-slate-900 mb-1">Assessment Performance</h2>
    <p class="text-sm text-slate-500 mb-6">
        Live analytics consumed from the Assessment &amp; Progress module web service
        (<code class="text-xs">GET /api/assessments/course/{{ $course->id }}/stats</code>).
    </p>

    @if (($analytics ?? null) === null)
        <div class="text-sm text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-4">
            Assessment analytics are temporarily unavailable. The Assessment &amp; Progress web service could not be reached.
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                <div class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Assessments</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $analytics['assessment_count'] }}</div>
            </div>
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                <div class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Questions</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $analytics['question_count'] }}</div>
            </div>
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                <div class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Attempts</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $analytics['attempt_count'] }}</div>
            </div>
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                <div class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Average Score</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $analytics['average_score'] ?? '—' }}{{ $analytics['average_score'] !== null ? '%' : '' }}</div>
            </div>
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                <div class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Pass Rate</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $analytics['pass_rate'] ?? '—' }}{{ $analytics['pass_rate'] !== null ? '%' : '' }}</div>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Source Module</div>
                <div class="mt-1 text-sm font-semibold text-slate-700">Assessment &amp; Progress</div>
            </div>
        </div>
    @endif
</div>

