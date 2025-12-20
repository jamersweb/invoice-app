<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Modules\Invoices\Controllers\InvoicesController;
use App\Modules\Offers\Controllers\OffersController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Modules\Funding\Models\Funding;
use App\Modules\Repayments\Models\ReceivedRepayment;
use App\Modules\Invoices\Models\Invoice;
use App\Models\User;
use App\Models\Agreement;
use App\Models\AgreementTemplate;

Route::get('/', function () {
    $locale = app()->getLocale();
    $blocks = \App\Models\CmsBlock::query()
        ->where('locale', $locale)
        ->where('is_active', true)
        ->whereIn('key', ['hero_primary', 'hero_secondary', 'features_1', 'features_2', 'features_3', 'footer_cta'])
        ->get()
        ->keyBy('key');
    return Inertia::render('Public/Home', [
        'cms' => $blocks->map->only(['key', 'title', 'body', 'cta_text', 'cta_href', 'image_url'])
    ]);
});

Route::get('/how-it-works', function () {
    return Inertia::render('Public/HowItWorks');
})->name('public.how');

Route::get('/faqs', function () {
    return Inertia::render('Public/Faqs');
})->name('public.faqs');

Route::get('/contact', function () {
    return Inertia::render('Public/Contact');
})->name('public.contact');

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email'],
        'message' => ['required', 'string', 'max:2000'],
    ]);
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Contact form from {$data['name']} ({$data['email']}):\n\n{$data['message']}",
            function ($m) {
                $m->to(config('mail.from.address'))->subject('Contact Form');
            }
        );
    } catch (\Throwable $e) {
        // ignore mail failures in minimal setup
    }
    return back(303)->with('success', true);
})->name('public.contact.submit');

// Lightweight analytics (GET to avoid CSRF for public pages)
Route::get('/api/v1/analytics/pv', function (\Illuminate\Http\Request $request) {
    try {
        \App\Models\AnalyticsEvent::create([
            'type' => 'pageview',
            'path' => (string) $request->query('path', '/'),
            'referrer' => (string) $request->headers->get('referer'),
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
            'ua' => (string) $request->userAgent(),
        ]);
    } catch (\Throwable $e) {
    }
    return response()->json(['ok' => true]);
})->name('api.analytics.pv');

// API v1 - KYB Queue (minimal, authenticated + permission)
Route::middleware(['auth', 'permission:review_documents'])->prefix('api/v1')->group(function () {
    Route::get('/admin/kyb-queue', function (Request $request) {
        $query = \App\Models\Document::query();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['pending', 'pending_review', 'under_review']);
        }
        if ($assigned = $request->query('assigned_to')) {
            $query->where('assigned_to', $assigned);
        }
        if ($vip = $request->boolean('vip', null)) {
            $query->where('vip', $vip);
        }
        if ($age = $request->query('age')) {
            // age in hours or days like "24h" / "2d"
            if (preg_match('/^(\d+)([hd])$/', $age, $m)) {
                $val = (int) $m[1];
                $col = $m[2] === 'h' ? now()->subHours($val) : now()->subDays($val);
                $query->where('created_at', '<=', $col);
            }
        }
        $sort = $request->query('sort', 'priority');
        $dir = $request->query('dir', 'desc');
        if ($sort === 'priority') {
            $query->orderBy('vip', 'desc')->orderBy('priority', $dir)->orderBy('created_at');
        } else {
            $query->orderBy($sort, $dir);
        }
        $docs = $query->paginate(20, ['id', 'document_type_id', 'status', 'owner_type', 'owner_id', 'created_at', 'assigned_to', 'priority', 'vip']);
        return response()->json($docs);
    })->name('api.kyb.queue');

    Route::post('/admin/kyb-queue/{document}/claim', function (\App\Models\Document $document) {
        abort_unless(auth()->user()?->can('assign', $document), 403);
        $old = ['assigned_to' => $document->assigned_to];
        $document->assigned_to = auth()->id();
        $document->save();
        \App\Models\AuditEvent::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'entity_type' => \App\Models\Document::class,
            'entity_id' => $document->id,
            'action' => 'document_claimed',
            'diff_json' => ['old' => $old, 'new' => ['assigned_to' => $document->assigned_to]],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);
        return response()->json(['ok' => true]);
    })->name('api.kyb.claim');

    Route::post('/admin/kyb-queue/{document}/reassign', function (\App\Models\Document $document, Request $request) {
        abort_unless(auth()->user()?->can('assign', $document), 403);
        $request->validate(['assigned_to' => ['required', 'integer']]);
        $old = ['assigned_to' => $document->assigned_to];
        $document->assigned_to = $request->integer('assigned_to');
        $document->save();
        \App\Models\AuditEvent::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'entity_type' => \App\Models\Document::class,
            'entity_id' => $document->id,
            'action' => 'document_reassigned',
            'diff_json' => ['old' => $old, 'new' => ['assigned_to' => $document->assigned_to]],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);
        return response()->json(['ok' => true]);
    })->name('api.kyb.reassign');

    Route::post('/admin/kyb-queue/{document}/approve', function (Request $request, $documentId) {
        $document = \App\Models\Document::findOrFail($documentId);
        $user = auth()->user();

        // Check permissions
        if (!$user->can('review_documents')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $oldStatus = $document->status;

        // Update document status
        $document->update([
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes', '')
        ]);

        // Update supplier status if this is the final approval
        $supplier = $document->supplier;
        if ($supplier) {
            $oldSupplierStatus = $supplier->kyb_status;
            $supplier->update([
                'kyb_status' => 'approved',
                'kyb_approved_at' => now(),
                'kyb_approved_by' => $user->id,
                'grade' => $request->input('grade', 'B'),
                'kyb_notes' => $request->input('notes', '')
            ]);

            // Send notification
            $notificationService = new \App\Services\KycNotificationService();
            $notificationService->sendStatusUpdateNotification(
                $supplier,
                $oldSupplierStatus,
                'approved',
                $request->input('notes')
            );

            // Send welcome notification for approved suppliers
            if ($oldSupplierStatus !== 'approved') {
                $notificationService->sendWelcomeNotification($supplier);
            }
        }

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => $user->id,
            'entity_type' => \App\Models\Document::class,
            'entity_id' => $document->id,
            'action' => 'document_approved',
            'diff_json' => [
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'approved', 'reviewed_by' => $user->id, 'reviewed_at' => now()],
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Document approved successfully']);
    })->middleware(['permission:review_documents'])->name('api.kyb.approve');

    Route::post('/admin/kyb-queue/{document}/reject', function (Request $request, $documentId) {
        $document = \App\Models\Document::findOrFail($documentId);
        $user = auth()->user();

        // Check permissions
        if (!$user->can('review_documents')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $oldStatus = $document->status;

        // Update document status
        $request->validate(['notes' => ['required', 'string']]);
        $document->update([
            'status' => 'rejected',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes', '')
        ]);

        // Update supplier status
        $supplier = $document->supplier;
        if ($supplier) {
            $oldSupplierStatus = $supplier->kyb_status;
            $supplier->update([
                'kyb_status' => 'rejected',
                'kyb_notes' => $request->input('notes', '')
            ]);

            // Send notification
            $notificationService = new \App\Services\KycNotificationService();
            $notificationService->sendStatusUpdateNotification(
                $supplier,
                $oldSupplierStatus,
                'rejected',
                $request->input('notes')
            );
        }

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => $user->id,
            'entity_type' => \App\Models\Document::class,
            'entity_id' => $document->id,
            'action' => 'document_rejected',
            'diff_json' => [
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'rejected', 'reviewed_by' => $user->id, 'reviewed_at' => now()],
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Document rejected']);
    })->middleware(['permission:review_documents'])->name('api.kyb.reject');

    // Document details + history
    Route::get('/admin/documents/{document}', function (\App\Models\Document $document) {
        $doc = $document->only(['id', 'document_type_id', 'status', 'file_path', 'owner_type', 'owner_id', 'created_at', 'assigned_to', 'priority', 'vip', 'review_notes']);
        $doc['document_type'] = optional(\App\Models\DocumentType::find($document->document_type_id))->name;
        $history = \App\Models\AuditEvent::query()
            ->where('entity_type', \App\Models\Document::class)
            ->where('entity_id', $document->id)
            ->latest()
            ->limit(50)
            ->get(['id', 'action', 'created_at', 'actor_id', 'diff_json'])
            ->map(function ($h) {
                $h->actor_name = optional(\App\Models\User::find($h->actor_id))->name;
                return $h;
            });
        return response()->json(['document' => $doc, 'history' => $history]);
    })->name('api.documents.show');

    // Reviewers list (Admins/Analysts)
    Route::get('/admin/reviewers', function () {
        $reviewers = User::role(['Admin', 'Analyst'])->get(['id', 'name']);
        return response()->json($reviewers);
    })->name('api.kyb.reviewers');
});

// API v1 - Dashboard metrics
Route::middleware(['auth'])->prefix('api/v1')->group(function () {
    Route::get('/dashboard/metrics', function (Request $request) {
        $from = $request->date('from') ?: now()->subDays(30);
        $to = $request->date('to') ?: now();
        $totalFunded = (float) (Funding::query()->sum('amount') ?? 0);
        $totalRepaid = (float) (ReceivedRepayment::query()->sum('amount') ?? 0);
        $outstanding = max($totalFunded - $totalRepaid, 0);
        // Sum overdue by invoice amount to avoid relying on missing columns in some environments
        $overdue = (float) (Invoice::query()->where('status', 'overdue')->sum('amount') ?? 0);

        // Daily series between from/to
        $period = new \DatePeriod(\Carbon\Carbon::parse($from)->startOfDay(), new \DateInterval('P1D'), \Carbon\Carbon::parse($to)->addDay()->startOfDay());
        $series = [];
        foreach ($period as $day) {
            $dayStart = \Carbon\Carbon::parse($day)->startOfDay();
            $dayEnd = \Carbon\Carbon::parse($day)->endOfDay();
            $series[] = [
                'date' => $dayStart->toDateString(),
                'funded' => (float) (Funding::query()->whereBetween('created_at', [$dayStart, $dayEnd])->sum('amount') ?? 0),
                'repaid' => (float) (ReceivedRepayment::query()->whereBetween('created_at', [$dayStart, $dayEnd])->sum('amount') ?? 0),
            ];
        }

        return response()->json([
            'kpis' => [
                'totalFunded' => $totalFunded,
                'totalRepaid' => $totalRepaid,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
            ],
            'series' => $series,
        ]);
    })->name('api.dashboard.metrics');

    Route::get('/dashboard/payment-stats', function () {
        $totalInvoiced = (float) (Invoice::query()->sum('amount') ?? 0);
        $paid = (float) (Invoice::query()->where('status', 'paid')->sum('amount') ?? 0);
        $partiallyPaid = (float) (Invoice::query()->where('status', 'partial')->sum('amount') ?? 0);
        $overdue = (float) (Invoice::query()->where('status', 'overdue')->sum('amount') ?? 0);

        return response()->json([
            'total' => $totalInvoiced,
            'paid' => $paid,
            'partiallyPaid' => $partiallyPaid,
            'overdue' => $overdue,
        ]);
    })->name('api.dashboard.payment-stats');

    Route::get('/dashboard/overview', function () {
        $today = now()->startOfDay();
        $newInvoicesToday = Invoice::query()->whereDate('created_at', $today)->count();
        $kybPending = \App\Models\Supplier::query()->where('kyb_status', 'pending')->count();
        $fundingApprovals = Funding::query()->where('status', 'approved')->whereDate('created_at', $today)->count();

        return response()->json([
            'items' => [
                [
                    'title' => 'New invoices today',
                    'value' => $newInvoicesToday,
                    'icon' => '🧾',
                    'status' => 'success',
                ],
                [
                    'title' => 'KYB pending',
                    'value' => $kybPending,
                    'icon' => '🪪',
                    'status' => 'warning',
                ],
                [
                    'title' => 'Funding approvals today',
                    'value' => $fundingApprovals,
                    'icon' => '✅',
                    'status' => 'info',
                ],
            ],
        ]);
    })->name('api.dashboard.overview');

    Route::get('/dashboard/recent-activity', function () {
        $activities = [];
        $limit = 10;

        try {
            // Recent fundings
            $fundings = Funding::query()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            foreach ($fundings as $funding) {
                $invoice = Invoice::find($funding->invoice_id);
                $invoiceNumber = $invoice ? ($invoice->invoice_number ?? $invoice->id) : $funding->invoice_id;
                $activities[] = [
                    'id' => $funding->id,
                    'type' => 'funding',
                    'title' => 'Funding Approved',
                    'description' => 'Invoice #' . $invoiceNumber . ' was funded',
                    'amount' => (float) $funding->amount,
                    'date' => $funding->created_at->toISOString(),
                    'status' => 'success',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if fundings table doesn't exist or has issues
        }

        try {
            // Recent repayments
            $repayments = ReceivedRepayment::query()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            foreach ($repayments as $repayment) {
                $activities[] = [
                    'id' => $repayment->id + 1000000, // Offset to avoid ID conflicts
                    'type' => 'repayment',
                    'title' => 'Repayment Received',
                    'description' => 'Payment received from buyer',
                    'amount' => (float) $repayment->amount,
                    'date' => $repayment->created_at->toISOString(),
                    'status' => 'success',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if repayments table doesn't exist or has issues
        }

        try {
            // Recent invoices
            $invoices = Invoice::query()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            foreach ($invoices as $invoice) {
                $activities[] = [
                    'id' => $invoice->id + 2000000, // Offset to avoid ID conflicts
                    'type' => 'invoice',
                    'title' => 'New Invoice',
                    'description' => 'Invoice #' . ($invoice->invoice_number ?? $invoice->id) . ' created',
                    'amount' => (float) $invoice->amount,
                    'date' => $invoice->created_at->toISOString(),
                    'status' => $invoice->status === 'overdue' ? 'error' : ($invoice->status === 'paid' ? 'success' : 'info'),
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if invoices table doesn't exist or has issues
        }

        // Sort by date and limit
        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return response()->json([
            'items' => array_slice($activities, 0, $limit),
        ]);
    })->name('api.dashboard.recent-activity');

    // Repayments: received posting and allocation
    // e-ID/KYC verification (adapter-backed, mock provider default)
    Route::post('/kyc/verify', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'id_number' => ['required', 'string', 'max:190'],
            'document_url' => ['nullable', 'string', 'max:190'],
        ]);
        $provider = app(\App\Services\KycProviderInterface::class, []);
        if (!$provider)
            $provider = new \App\Services\MockKycProvider();
        $result = $provider->verify($data);
        $supplier = \App\Models\Supplier::firstOrCreate(['contact_email' => auth()->user()->email], ['kyb_status' => 'pending']);
        $kycData = $supplier->kyc_data ?? [];
        $kycData['eid'] = $result;
        $supplier->kyc_data = $kycData;
        $supplier->save();
        return response()->json($result);
    })->name('api.kyc.verify');
    Route::post('/admin/repayments', function (Request $request) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $data = $request->validate([
            'buyer_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'received_date' => ['required', 'date'],
            'bank_reference' => ['nullable', 'string', 'max:190'],
        ]);
        $rr = \App\Modules\Repayments\Models\ReceivedRepayment::create([
            'buyer_id' => $data['buyer_id'],
            'amount' => $data['amount'],
            'received_date' => $data['received_date'],
            'bank_reference' => $data['bank_reference'] ?? null,
            'allocated_amount' => 0,
            'unallocated_amount' => $data['amount'],
        ]);

        // FIFO allocation by buyer_id and oldest due_date
        $remaining = (float) $rr->unallocated_amount;
        $expecteds = \App\Modules\Repayments\Models\ExpectedRepayment::query()
            ->where('buyer_id', $rr->buyer_id)
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->get();
        foreach ($expecteds as $er) {
            if ($remaining <= 0)
                break;
            $alreadyAllocated = (float) (\App\Modules\Repayments\Models\RepaymentAllocation::where('expected_repayment_id', $er->id)->sum('amount') ?? 0);
            $due = max((float) $er->amount - $alreadyAllocated, 0);
            if ($due <= 0)
                continue;
            $alloc = min($due, $remaining);
            \App\Modules\Repayments\Models\RepaymentAllocation::create([
                'received_repayment_id' => $rr->id,
                'expected_repayment_id' => $er->id,
                'amount' => $alloc,
            ]);
            $remaining -= $alloc;

            // Update expected status
            if ($alloc >= $due) {
                $er->status = 'settled';
            } else {
                $er->status = 'partial';
            }
            $er->save();

            // If fully settled, mark invoice settled
            if ($er->status === 'settled') {
                $invoice = \App\Modules\Invoices\Models\Invoice::find($er->invoice_id);
                if ($invoice && $invoice->status !== 'settled') {
                    $old = $invoice->status;
                    $invoice->status = 'settled';
                    $invoice->save();
                    \App\Models\AuditEvent::create([
                        'actor_type' => \App\Models\User::class,
                        'actor_id' => auth()->id(),
                        'entity_type' => get_class($invoice),
                        'entity_id' => $invoice->id,
                        'action' => 'invoice_status_changed',
                        'diff_json' => ['old_values' => ['status' => $old], 'new_values' => ['status' => 'settled']],
                    ]);
                }
            }
        }
        $rr->allocated_amount = $rr->amount - $remaining;
        $rr->unallocated_amount = $remaining;
        $rr->save();

        return response()->json(['id' => $rr->id, 'allocated' => (float) $rr->allocated_amount, 'unallocated' => (float) $rr->unallocated_amount]);
    })->name('api.admin.repayments.store');

    Route::get('/admin/repayments/unallocated', function () {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $rows = \App\Modules\Repayments\Models\ReceivedRepayment::where('unallocated_amount', '>', 0)->orderByDesc('id')->paginate(20);
        return response()->json($rows);
    })->name('api.admin.repayments.unallocated');

    Route::post('/admin/repayments/{id}/allocate', function (Request $request, $id) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $data = $request->validate([
            'expected_id' => ['required', 'integer', 'exists:expected_repayments,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        $rr = \App\Modules\Repayments\Models\ReceivedRepayment::findOrFail($id);
        $er = \App\Modules\Repayments\Models\ExpectedRepayment::findOrFail($data['expected_id']);
        $allocAmt = min((float) $data['amount'], (float) $rr->unallocated_amount);
        if ($allocAmt <= 0)
            return response()->json(['error' => 'Nothing to allocate'], 422);
        \App\Modules\Repayments\Models\RepaymentAllocation::create([
            'received_repayment_id' => $rr->id,
            'expected_repayment_id' => $er->id,
            'amount' => $allocAmt,
        ]);
        $rr->allocated_amount += $allocAmt;
        $rr->unallocated_amount -= $allocAmt;
        $rr->save();
        $alreadyAllocated = (float) (\App\Modules\Repayments\Models\RepaymentAllocation::where('expected_repayment_id', $er->id)->sum('amount') ?? 0);
        $due = max((float) $er->amount - $alreadyAllocated, 0);
        $er->status = $due <= 0 ? 'settled' : 'partial';
        $er->save();
        return response()->json(['ok' => true]);
    })->name('api.admin.repayments.allocate');

    // Overdue updater
    Route::post('/admin/expected-repayments/overdue/run', function () {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $count = \App\Modules\Repayments\Models\ExpectedRepayment::whereDate('due_date', '<', now()->toDateString())
            ->where('status', 'open')->update(['status' => 'overdue']);
        return response()->json(['updated' => $count]);
    })->name('api.admin.expected_repayments.overdue.run');

    // OCR extraction endpoint (for invoice files)
    Route::post('/invoices/ocr', function (Request $request) {
        $request->validate(['file' => ['required', 'file']]);
        $file = $request->file('file');
        $ocr = app(\App\Services\OcrServiceInterface::class, []);
        if (!$ocr)
            $ocr = new \App\Services\TesseractOcrService();
        $data = $ocr->extract($file);
        return response()->json(['data' => $data]);
    })->name('api.invoices.ocr');

    // Offers & Invoice Approval (stubs for integration)
    // Invoice approval now handled by InvoiceReviewController

    Route::post('/admin/offers/generate', function (Request $request) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.admin.offers.generate');

    Route::get('/offers/{id}', function ($id) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.offers.show');

    Route::post('/offers/{id}/accept', function ($id) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.offers.accept');

    Route::post('/offers/{id}/decline', function (Request $request, $id) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.offers.decline');

    Route::get('/suppliers/{id}/offers/active', function ($id) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.suppliers.offers.active');

    Route::get('/suppliers/{id}/offers/history', function ($id) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.suppliers.offers.history');

    Route::post('/admin/offers/{id}/manual-pricing', function (Request $request, $id) {
        return response()->json(['error' => 'Not implemented'], 501);
    })->name('api.admin.offers.manual_pricing');
});

// Funding Queue & Batches
Route::middleware(['auth'])->prefix('api/v1')->group(function () {
    Route::get('/admin/funding-queue', function () {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $rows = \App\Modules\Funding\Models\Funding::query()
            ->where('status', 'queued')
            ->with('invoice')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'invoice_id', 'offer_id', 'amount', 'status', 'created_at']);
        return response()->json(['data' => $rows]);
    })->name('api.admin.funding.queue');

    Route::post('/admin/funding-batches', function (Request $request) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $validated = $request->validate([
            'max_items' => ['nullable', 'integer', 'min:1', 'max:50'],
            'max_total' => ['nullable', 'numeric', 'min:0'],
        ]);
        $maxItems = $validated['max_items'] ?? 50;
        $maxTotal = $validated['max_total'] ?? 10000000; // 10M default

        $queued = \App\Modules\Funding\Models\Funding::query()
            ->whereNull('batch_id')->where('status', 'queued')
            ->orderByDesc('id')->get();

        $picked = [];
        $total = 0.0;
        foreach ($queued as $f) {
            if (count($picked) >= $maxItems)
                break;
            if ($total + (float) $f->amount > $maxTotal)
                continue;
            $picked[] = $f;
            $total += (float) $f->amount;
        }
        if (empty($picked)) {
            return response()->json(['error' => 'No items to batch'], 422);
        }
        $batch = \App\Modules\Funding\Models\FundingBatch::create([
            'total_amount' => $total,
            'status' => 'created',
            'created_by' => auth()->id(),
        ]);
        foreach ($picked as $f) {
            $f->batch_id = $batch->id;
            $f->status = 'validated';
            $f->save();
        }
        return response()->json(['id' => $batch->id, 'total_amount' => (float) $batch->total_amount, 'count' => count($picked)]);
    })->name('api.admin.funding_batches.store');

    Route::get('/admin/funding-batches/{id}', function ($id) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $batch = \App\Modules\Funding\Models\FundingBatch::findOrFail($id);
        $items = \App\Modules\Funding\Models\Funding::where('batch_id', $batch->id)->get(['id', 'invoice_id', 'amount', 'status']);
        return response()->json(['batch' => $batch, 'items' => $items]);
    })->name('api.admin.funding_batches.show');

    Route::post('/admin/funding-batches/{id}/approve', function ($id) {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
        $batch = \App\Modules\Funding\Models\FundingBatch::findOrFail($id);
        $batch->status = 'approved';
        $batch->approved_by = auth()->id();
        $batch->save();
        \App\Modules\Funding\Models\Funding::where('batch_id', $batch->id)->update(['status' => 'approved']);
        return response()->json(['ok' => true]);
    })->name('api.admin.funding_batches.approve');

    Route::post('/admin/funding-batches/{id}/execute', function ($id) {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
        $batch = \App\Modules\Funding\Models\FundingBatch::findOrFail($id);
        $items = \App\Modules\Funding\Models\Funding::where('batch_id', $batch->id)->get();

        // Check bank accounts before execution
        $missingBankAccounts = [];
        foreach ($items as $f) {
            $invoice = \App\Modules\Invoices\Models\Invoice::find($f->invoice_id);
            if (!$invoice)
                continue;

            $supplier = \App\Models\Supplier::find($invoice->supplier_id);
            if ($supplier) {
                $bankAccount = \App\Models\BankAccount::where('supplier_id', $supplier->id)->first();
                if (!$bankAccount) {
                    $missingBankAccounts[] = [
                        'invoice_id' => $invoice->id,
                        'supplier_name' => $supplier->company_name ?? $supplier->legal_name ?? 'N/A',
                        'invoice_number' => $invoice->invoice_number,
                    ];
                }
            }
        }

        if (!empty($missingBankAccounts)) {
            return response()->json([
                'error' => 'Some suppliers are missing bank account details',
                'missing_accounts' => $missingBankAccounts
            ], 422);
        }

        foreach ($items as $f) {
            $invoice = \App\Modules\Invoices\Models\Invoice::find($f->invoice_id);
            if (!$invoice)
                continue;
            $old = $invoice->status;
            $invoice->status = 'funded';
            $invoice->save();
            $f->status = 'executed';
            $f->funded_at = now();
            $f->save();
            // Create expected repayment upon execution
            \App\Modules\Repayments\Models\ExpectedRepayment::create([
                'invoice_id' => $invoice->id,
                'buyer_id' => $invoice->buyer_id,
                'amount' => $f->amount,
                'due_date' => $invoice->due_date,
                'status' => 'open',
            ]);
            \App\Models\AuditEvent::create([
                'actor_type' => \App\Models\User::class,
                'actor_id' => auth()->id(),
                'entity_type' => get_class($invoice),
                'entity_id' => $invoice->id,
                'action' => 'invoice_status_changed',
                'diff_json' => ['old_values' => ['status' => $old], 'new_values' => ['status' => 'funded']],
            ]);
        }
        $batch->status = 'executed';
        $batch->executed_by = auth()->id();
        $batch->executed_at = now();
        $batch->save();
        return response()->json(['ok' => true]);
    })->name('api.admin.funding_batches.execute');

    Route::post('/admin/fundings/{id}/record', function ($id) {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
        $f = \App\Modules\Funding\Models\Funding::findOrFail($id);
        $invoice = \App\Modules\Invoices\Models\Invoice::find($f->invoice_id);

        if ($invoice) {
            $supplier = \App\Models\Supplier::find($invoice->supplier_id);
            if ($supplier) {
                $bankAccount = \App\Models\BankAccount::where('supplier_id', $supplier->id)->first();
                if (!$bankAccount) {
                    return response()->json([
                        'error' => 'Supplier bank account details are required before recording funding',
                        'supplier_name' => $supplier->company_name ?? $supplier->legal_name ?? 'N/A',
                    ], 422);
                }
            }
        }

        $f->status = 'executed';
        $f->funded_at = now();
        $f->save();
        return response()->json(['ok' => true]);
    })->name('api.admin.fundings.record');

    Route::patch('/admin/fundings/{id}/status', function (Request $request, $id) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $request->validate(['status' => ['required', 'in:queued,validated,approved,executed,failed']]);
        \App\Modules\Funding\Models\Funding::where('id', $id)->update(['status' => $request->string('status')]);
        return response()->json(['ok' => true]);
    })->name('api.admin.fundings.status');

    Route::get('/suppliers/{id}/fundings', function ($id) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $invoices = \App\Modules\Invoices\Models\Invoice::where('supplier_id', $id)->pluck('id');
        $fundings = \App\Modules\Funding\Models\Funding::whereIn('invoice_id', $invoices)->orderByDesc('id')->get();
        return response()->json(['data' => $fundings]);
    })->name('api.suppliers.fundings.index');

    Route::get('/fundings/{id}/documents', function ($id) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        return response()->json(['data' => []]);
    })->name('api.fundings.documents');

    Route::post('/admin/fundings/{id}/retry', function ($id) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        \App\Modules\Funding\Models\Funding::where('id', $id)->update(['status' => 'approved']);
        return response()->json(['ok' => true]);
    })->name('api.admin.fundings.retry');
});

// Admin Reporting Widgets (basic)
Route::middleware(['auth'])->prefix('api/v1')->group(function () {
    Route::get('/admin/reporting/aging', function () {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $today = now()->toDateString();
        $rows = \App\Modules\Repayments\Models\ExpectedRepayment::selectRaw(
            "sum(case when status='open' and due_date>=? then amount else 0 end) as current, " .
            "sum(case when status in ('open','overdue') and due_date<? and julianday(?) - julianday(due_date) between 1 and 30 then amount else 0 end) as d1_30, " .
            "sum(case when status in ('open','overdue') and julianday(?) - julianday(due_date) between 31 and 60 then amount else 0 end) as d31_60, " .
            "sum(case when status in ('open','overdue') and julianday(?) - julianday(due_date) > 60 then amount else 0 end) as d60p",
        )
            ->setBindings([$today, $today, $today, $today, $today])
            ->first();
        return response()->json($rows);
    })->name('api.admin.reporting.aging');

    Route::get('/admin/reporting/top-suppliers', function () {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);
        $rows = \App\Modules\Invoices\Models\Invoice::query()
            ->join('fundings', 'fundings.invoice_id', '=', 'invoices.id')
            ->selectRaw('invoices.supplier_id, sum(fundings.amount) as total')
            ->groupBy('invoices.supplier_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        return response()->json(['data' => $rows]);
    })->name('api.admin.reporting.top_suppliers');
});

// API v1 - Collections (overdue)
Route::middleware(['auth'])->prefix('api/v1')->group(function () {
    Route::get('/admin/collections', function (Request $request) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Collector']), 403);
        $q = Invoice::query()->where('status', 'overdue');
        if ($assigned = $request->query('assigned_to'))
            $q->where('assigned_to', $assigned);
        if ($min = $request->query('min_amount'))
            $q->where('amount', '>=', (float) $min);
        if ($age = $request->query('age')) {
            if (preg_match('/^(\d+)([hd])$/', $age, $m)) {
                $val = (int) $m[1];
                $col = $m[2] === 'h' ? now()->subHours($val) : now()->subDays($val);
                $q->where('due_date', '<=', $col);
            }
        }
        $q->orderBy('priority', 'desc')->orderBy('due_date');
        return response()->json($q->paginate(20, ['id', 'invoice_number', 'amount', 'supplier_id', 'buyer_id', 'due_date', 'assigned_to', 'priority']));
    })->name('api.collections.index');

    Route::post('/admin/collections/{invoice}/claim', function (Invoice $invoice) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Collector']), 403);

        $oldAssignedTo = $invoice->assigned_to;
        $invoice->assigned_to = auth()->id();
        $invoice->save();

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'collections_claim',
            'diff_json' => [
                'old_values' => ['assigned_to' => $oldAssignedTo],
                'new_values' => ['assigned_to' => auth()->id()],
            ],
        ]);

        return response()->json(['ok' => true]);
    })->name('api.collections.claim');

    Route::post('/admin/collections/{invoice}/reassign', function (Invoice $invoice, Request $request) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Collector']), 403);
        $request->validate(['assigned_to' => ['required', 'integer']]);

        $oldAssignedTo = $invoice->assigned_to;
        $invoice->assigned_to = $request->integer('assigned_to');
        $invoice->save();

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'collections_reassign',
            'diff_json' => [
                'old_values' => ['assigned_to' => $oldAssignedTo],
                'new_values' => ['assigned_to' => $request->integer('assigned_to')],
            ],
        ]);

        return response()->json(['ok' => true]);
    })->name('api.collections.reassign');

    Route::post('/admin/collections/{invoice}/remind', function (Invoice $invoice) {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Collector']), 403);

        // Get buyer contact email (placeholder for now)
        $buyerEmail = 'buyer@example.com'; // TODO: Get from buyer relationship

        // Send reminder email
        \Illuminate\Support\Facades\Mail::to($buyerEmail)->send(
            new \App\Mail\CollectionsReminderMail($invoice)
        );

        // TODO: Send SMS if phone number available
        // \App\Services\SmsService::send($buyerPhone, __('messages.collections_reminder_subject'));

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'collections_remind',
            'diff_json' => [
                'old_values' => [],
                'new_values' => ['reminder_sent_at' => now()],
            ],
        ]);

        return response()->json(['ok' => true, 'message' => 'Reminder sent successfully']);
    })->name('api.collections.remind');
});

// Dashboard routes - Role-based access
Route::middleware(['auth', 'verified'])->group(function () {
    // Supplier Dashboard
    Route::get('/supplier/dashboard', function () {
        $user = auth()->user();

        // Ensure email is verified
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Ensure KYC/KYB is completed
        $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
        if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
            return redirect()->route('onboarding.kyc');
        }

        return Inertia::render('Supplier/Dashboard', [
            't' => [
                'dashboard' => __('messages.dashboard'),
                'total_funded' => __('messages.total_funded'),
                'total_repaid' => __('messages.total_repaid'),
                'outstanding' => __('messages.outstanding'),
                'overdue' => __('messages.overdue'),
                'revenue' => __('messages.revenue'),
                'overview_new_invoices' => __('messages.overview_new_invoices'),
                'overview_kyb_pending' => __('messages.overview_kyb_pending'),
                'overview_funding_approvals' => __('messages.overview_funding_approvals'),
            ],
        ]);
    })->middleware(['role:Supplier'])->name('supplier.dashboard');

    // Admin Dashboard
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard', [
            't' => [
                'dashboard' => __('messages.dashboard'),
                'total_funded' => __('messages.total_funded'),
                'total_repaid' => __('messages.total_repaid'),
                'outstanding' => __('messages.outstanding'),
                'overdue' => __('messages.overdue'),
                'revenue' => __('messages.revenue'),
                'overview_new_invoices' => __('messages.overview_new_invoices'),
                'overview_kyb_pending' => __('messages.overview_kyb_pending'),
                'overview_funding_approvals' => __('messages.overview_funding_approvals'),
            ],
        ]);
    })->middleware(['role:Admin'])->name('admin.dashboard');

    // Legacy dashboard route - redirects based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->hasRole('Admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('Supplier')) {
            // Check onboarding status for suppliers
            // Step 1: Email verification
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            // Step 2: KYC/KYB completion
            $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
                return redirect()->route('onboarding.kyc');
            }

            return redirect()->route('supplier.dashboard');
        }
        // Default fallback
        return redirect()->route('supplier.dashboard');
    })->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/customer/dashboard', function () {
        return Inertia::render('Customer/Dashboard');
    })->name('customer.dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/documents', [\App\Http\Controllers\DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/upload', [\App\Http\Controllers\DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [\App\Http\Controllers\DocumentController::class, 'store'])->name('documents.store');
    Route::post('/documents/{document}/review', [\App\Http\Controllers\DocumentController::class, 'update'])->middleware('permission:review_documents')->name('documents.review');
    Route::get('/agreements', [\App\Http\Controllers\AgreementController::class, 'index'])->name('agreements.index');
    Route::post('/agreements', [\App\Http\Controllers\AgreementController::class, 'store'])->name('agreements.store');
    Route::post('/agreements/sign', [\App\Http\Controllers\AgreementController::class, 'sign'])->name('agreements.sign');
    Route::post('/agreements/send', [\App\Http\Controllers\AgreementController::class, 'send'])->name('agreements.send');
    Route::get('/bank', [\App\Http\Controllers\BankAccountController::class, 'index'])->name('bank.index');
    Route::post('/bank', [\App\Http\Controllers\BankAccountController::class, 'store'])->name('bank.store');
    Route::get('/invoices', function () {
        return Inertia::render('Invoices/Index');
    })->name('invoices.index');
    Route::get('/invoices/submit', function () {
        $supplier = \App\Models\Supplier::where('contact_email', auth()->user()->email)->first();
        return Inertia::render('Invoices/SubmitInvoice', [
            'templates' => AgreementTemplate::all(['id', 'name', 'version']),
            'hasSignedAgreement' => Agreement::where('status', 'signed')
                ->where('signer_id', auth()->id())
                ->exists(),
            'supplier_id' => $supplier?->id,
            'buyers' => \App\Models\Buyer::all(['id', 'name']),
        ]);
    })->name('invoices.submit');
    Route::get('/customers', function () {
        return Inertia::render('Customers/Index');
    })->name('customers.index');
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');
    Route::post('/invoices', [InvoicesController::class, 'store'])->middleware('throttle:uploads')->name('invoices.store');
    Route::post('/invoices/bulk', [InvoicesController::class, 'bulkStore'])->middleware('throttle:uploads')->name('invoices.bulk.store');
    Route::post('/offers/issue', [OffersController::class, 'issue'])->middleware('throttle:60,1')->name('offers.issue');
    Route::post('/offers/accept', [OffersController::class, 'accept'])->middleware('throttle:30,1')->name('offers.accept');
    Route::post('/offers/decline', [OffersController::class, 'decline'])->middleware('throttle:30,1')->name('offers.decline');
    Route::get('/admin/kyb-queue', function () {
        return Inertia::render('Admin/KybQueue');
    })->middleware(['permission:review_documents'])->name('admin.kyb.queue');

    Route::get('/admin/collections', function () {
        return Inertia::render('Admin/CollectionsQueue');
    })->middleware(['role:Admin|Collector'])->name('admin.collections');

    // Admin Settings
    Route::prefix('admin/api/settings')->middleware(['role:Admin'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('/', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');
        Route::get('/reminder-email', [\App\Http\Controllers\Admin\SettingsController::class, 'reminderSettings'])->name('admin.settings.reminder');
        Route::put('/reminder-email', [\App\Http\Controllers\Admin\SettingsController::class, 'updateReminderSettings'])->name('admin.settings.reminder.update');
    });

    // Forfaiting Routes
    Route::prefix('forfaiting')->name('forfaiting.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Forfaiting\DashboardController::class, 'index'])
            ->middleware(['role:Admin'])->name('dashboard');

        // Investments
        Route::get('/investments', [\App\Http\Controllers\Forfaiting\InvestmentController::class, 'index'])
            ->middleware(['role:Admin'])->name('investments.index');
        Route::post('/investments', [\App\Http\Controllers\Forfaiting\InvestmentController::class, 'store'])
            ->middleware(['role:Admin'])->name('investments.store');
        Route::delete('/investments/{investment}', [\App\Http\Controllers\Forfaiting\InvestmentController::class, 'destroy'])
            ->middleware(['role:Admin'])->name('investments.destroy');
        Route::get('/investments/export', [\App\Http\Controllers\Forfaiting\InvestmentController::class, 'export'])
            ->middleware(['role:Admin'])->name('investments.export');

        // Transactions
        Route::get('/transactions', [\App\Http\Controllers\Forfaiting\TransactionController::class, 'index'])
            ->middleware(['role:Admin'])->name('transactions.index');
        Route::post('/transactions', [\App\Http\Controllers\Forfaiting\TransactionController::class, 'store'])
            ->middleware(['role:Admin'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [\App\Http\Controllers\Forfaiting\TransactionController::class, 'update'])
            ->middleware(['role:Admin'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [\App\Http\Controllers\Forfaiting\TransactionController::class, 'destroy'])
            ->middleware(['role:Admin'])->name('transactions.destroy');

        // Profit Allocations
        Route::get('/profit-allocations', [\App\Http\Controllers\Forfaiting\ProfitAllocationController::class, 'index'])
            ->middleware(['role:Admin'])->name('profit-allocations.index');
        Route::post('/profit-allocations', [\App\Http\Controllers\Forfaiting\ProfitAllocationController::class, 'store'])
            ->middleware(['role:Admin'])->name('profit-allocations.store');
        Route::post('/profit-allocations/recalculate', [\App\Http\Controllers\Forfaiting\ProfitAllocationController::class, 'recalculate'])
            ->middleware(['role:Admin'])->name('profit-allocations.recalculate');

        // Expenses
        Route::get('/expenses', [\App\Http\Controllers\Forfaiting\ExpenseController::class, 'index'])
            ->middleware(['role:Admin'])->name('expenses.index');
        Route::post('/expenses', [\App\Http\Controllers\Forfaiting\ExpenseController::class, 'store'])
            ->middleware(['role:Admin'])->name('expenses.store');
        Route::put('/expenses/{expense}/status', [\App\Http\Controllers\Forfaiting\ExpenseController::class, 'updateStatus'])
            ->middleware(['role:Admin'])->name('expenses.update-status');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\Forfaiting\ExpenseController::class, 'destroy'])
            ->middleware(['role:Admin'])->name('expenses.destroy');

        // Customers
        Route::get('/customers', [\App\Http\Controllers\Forfaiting\CustomerController::class, 'index'])
            ->middleware(['role:Admin'])->name('customers.index');
        Route::get('/customers/{customer}', [\App\Http\Controllers\Forfaiting\CustomerController::class, 'show'])
            ->middleware(['role:Admin'])->name('customers.show');
        Route::post('/customers', [\App\Http\Controllers\Forfaiting\CustomerController::class, 'store'])
            ->middleware(['role:Admin'])->name('customers.store');
        Route::put('/customers/{customer}', [\App\Http\Controllers\Forfaiting\CustomerController::class, 'update'])
            ->middleware(['role:Admin'])->name('customers.update');
        Route::post('/customers/{customer}/documents', [\App\Http\Controllers\Forfaiting\CustomerController::class, 'uploadDocument'])
            ->middleware(['role:Admin'])->name('customers.upload-document');

        // Investors
        Route::get('/investors', [\App\Http\Controllers\Forfaiting\InvestorController::class, 'index'])
            ->middleware(['role:Admin'])->name('investors.index');
        Route::get('/investors/dashboard', [\App\Http\Controllers\Forfaiting\InvestorController::class, 'dashboard'])
            ->name('investors.dashboard');
        Route::post('/investors/{user}/generate-id', [\App\Http\Controllers\Forfaiting\InvestorController::class, 'generateInvestorId'])
            ->middleware(['role:Admin'])->name('investors.generate-id');

        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\Forfaiting\AnalyticsController::class, 'index'])
            ->middleware(['role:Admin'])->name('analytics.index');

        // Contact Requests
        Route::get('/contact-requests', [\App\Http\Controllers\Forfaiting\ContactRequestController::class, 'index'])
            ->middleware(['role:Admin'])->name('contact-requests.index');
        Route::post('/contact-requests', [\App\Http\Controllers\Forfaiting\ContactRequestController::class, 'store'])
            ->name('contact-requests.store');
        Route::put('/contact-requests/{contactRequest}/status', [\App\Http\Controllers\Forfaiting\ContactRequestController::class, 'updateStatus'])
            ->middleware(['role:Admin'])->name('contact-requests.update-status');
        Route::post('/contact-requests/{contactRequest}/assign', [\App\Http\Controllers\Forfaiting\ContactRequestController::class, 'assign'])
            ->middleware(['role:Admin'])->name('contact-requests.assign');

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Forfaiting\NotificationController::class, 'index'])
            ->middleware(['role:Admin'])->name('notifications.index');
        Route::post('/notifications', [\App\Http\Controllers\Forfaiting\NotificationController::class, 'store'])
            ->middleware(['role:Admin'])->name('notifications.store');
        Route::put('/notifications/{notification}/status', [\App\Http\Controllers\Forfaiting\NotificationController::class, 'updateStatus'])
            ->middleware(['role:Admin'])->name('notifications.update-status');
    });

    Route::get('/admin/kyb-queue/export', function (Request $request) {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kyb_queue.csv"',
        ];
        $callback = function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'document_type_id', 'status', 'owner_type', 'owner_id', 'created_at', 'assigned_to', 'priority', 'vip']);
            $q = \App\Models\Document::query();
            if ($status = $request->query('status'))
                $q->where('status', $status);
            else
                $q->whereIn('status', ['pending', 'pending_review', 'under_review']);
            if ($assigned = $request->query('assigned_to'))
                $q->where('assigned_to', $assigned);
            if (($vip = $request->boolean('vip', null)) !== null)
                $q->where('vip', $vip);
            if ($age = $request->query('age')) {
                if (preg_match('/^(\d+)([hd])$/', $age, $m)) {
                    $val = (int) $m[1];
                    $col = $m[2] === 'h' ? now()->subHours($val) : now()->subDays($val);
                    $q->where('created_at', '<=', $col);
                }
            }
            $q->orderBy('vip', 'desc')->orderBy('priority', 'desc')->orderBy('created_at');
            $q->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [$r->id, $r->document_type_id, $r->status, $r->owner_type, $r->owner_id, $r->created_at, $r->assigned_to, $r->priority, $r->vip]);
                }
            });
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    })->middleware(['permission:review_documents'])->name('admin.kyb.queue.export');
});

// E-sign provider webhook (token header required)
Route::post('/webhooks/esign', [\App\Http\Controllers\AgreementController::class, 'webhook'])->name('webhooks.esign');

// Apply Now funnel (Public - No authentication required)
Route::middleware('guest')->group(function () {
    Route::get('/apply', [\App\Http\Controllers\LeadController::class, 'create'])->name('apply.step1');
    Route::post('/apply', [\App\Http\Controllers\LeadController::class, 'store'])->name('apply.register');
    Route::get('/apply/step2', [\App\Http\Controllers\LeadController::class, 'step2'])->name('apply.step2');
    Route::get('/apply/verify', [\App\Http\Controllers\LeadController::class, 'verify'])->name('apply.verify');
});

// Google OAuth
Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// KYC/KYB Onboarding Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding/kyc', function () {
        return Inertia::render('Onboarding/KycKybForm');
    })->name('onboarding.kyc');

    Route::post('/api/v1/supplier/kyc/save', function (Request $request) {
        $user = auth()->user();

        // Find or create supplier
        $supplier = \App\Models\Supplier::firstOrCreate(
            ['contact_email' => $user->email],
            ['kyb_status' => 'pending']
        );

        // Update supplier data
        $supplier->update($request->only([
            'company_name',
            'legal_name',
            'tax_registration_number',
            'website',
            'business_type',
            'industry',
            'incorporation_date',
            'country',
            'state_province',
            'city',
            'address',
            'postal_code',
            'contact_email',
            'contact_phone',
            'kyc_data'
        ]));
        // Inertia forms expect a redirect, not JSON
        if ($request->header('X-Inertia')) {
            return back(303)->with('success', true);
        }
        return response()->json(['success' => true, 'supplier' => $supplier]);
    })->name('api.supplier.kyc.save');

    Route::post('/api/v1/supplier/kyc/submit', function (Request $request) {
        $user = auth()->user();

        // Find or create supplier (avoid 404 on first submit)
        $supplier = \App\Models\Supplier::firstOrCreate(
            ['contact_email' => $user->email],
            ['kyb_status' => 'pending']
        );

        // Update supplier status
        $supplier->update([
            'kyb_status' => 'under_review',
            'kyc_data' => $request->input('kyc_data', [])
        ]);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('documents', 'public');

                \App\Models\Document::create([
                    'document_type_id' => 1, // Default document type
                    'owner_type' => 'App\Models\Supplier',
                    'owner_id' => $supplier->id,
                    'supplier_id' => $supplier->id,
                    'status' => 'pending_review',
                    'file_path' => $path,
                ]);
            }
        }

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => $user->id,
            'entity_type' => \App\Models\Supplier::class,
            'entity_id' => $supplier->id,
            'action' => 'kyc_submitted',
            'diff_json' => [
                'old_values' => ['kyb_status' => 'pending'],
                'new_values' => ['kyb_status' => 'under_review'],
            ],
        ]);

        // Inertia forms expect a redirect
        if ($request->header('X-Inertia')) {
            return redirect()->route('onboarding.success');
        }
        return response()->json(['success' => true, 'message' => 'Application submitted successfully']);
    })->name('api.supplier.kyc.submit');

    Route::get('/api/v1/supplier/profile', function () {
        $user = auth()->user();
        $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();

        return response()->json(['supplier' => $supplier]);
    })->name('api.supplier.profile');

    Route::get('/api/v1/supplier/documents', function () {
        $user = auth()->user();
        $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();

        if (!$supplier) {
            return response()->json(['documents' => []]);
        }

        $documents = \App\Models\Document::where('supplier_id', $supplier->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['documents' => $documents]);
    })->name('api.supplier.documents');

    Route::get('/api/v1/supplier/export', function (Request $request) {
        $user = auth()->user();
        $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();

        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        $format = $request->query('format', 'excel');

        try {
            $exportService = new \App\Services\KycExportService();
            $filepath = $exportService->exportSupplierData($supplier, $format);

            $file = storage_path('app/public/' . $filepath);

            if (!file_exists($file)) {
                throw new \Exception('Export file not found');
            }

            $mimeType = match ($format) {
                'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv' => 'text/csv',
                default => 'application/octet-stream'
            };

            $filename = basename($filepath);

            return response()->download($file, $filename, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    })->name('api.supplier.export');

    Route::get('/kyc-status', function () {
        return Inertia::render('Supplier/KycStatus');
    })->name('supplier.kyc.status');

    Route::get('/onboarding/success', function () {
        return Inertia::render('Onboarding/Success');
    })->name('onboarding.success');

    // Supplier-facing dashboard widgets (for current user)
    Route::prefix('api/v1')->group(function () {
        Route::get('/me/offers/active', function () {
            $user = auth()->user();
            $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            if (!$supplier)
                return response()->json(['data' => []]);
            $offers = \App\Modules\Offers\Models\Offer::whereHas('invoice', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            })->where('status', 'issued')->orderByDesc('issued_at')->limit(10)->get();
            return response()->json(['data' => $offers]);
        })->name('api.me.offers.active');

        Route::get('/me/invoices/recent', function () {
            $user = auth()->user();
            $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            if (!$supplier)
                return response()->json(['data' => []]);
            $invoices = \App\Modules\Invoices\Models\Invoice::where('supplier_id', $supplier->id)
                ->orderByDesc('created_at')->limit(10)->get(['id', 'invoice_number', 'amount', 'status', 'created_at']);
            return response()->json(['data' => $invoices]);
        })->name('api.me.invoices.recent');

        Route::get('/me/repayments/schedule', function () {
            $user = auth()->user();
            $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            if (!$supplier)
                return response()->json(['data' => []]);
            $expected = \App\Modules\Repayments\Models\ExpectedRepayment::where('supplier_id', $supplier->id)
                ->whereIn('status', ['open', 'partial', 'overdue'])
                ->orderBy('due_date')->limit(20)
                ->get(['id', 'invoice_id', 'buyer_id', 'amount', 'due_date', 'status']);
            return response()->json(['data' => $expected]);
        })->name('api.me.repayments.schedule');

        // KYB Checklist for current supplier type
        Route::get('/me/kyb/checklist', function () {
            $user = auth()->user();
            $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            $type = $supplier?->business_type ?: 'Default';
            $rules = \App\Models\KybChecklist::with('documentType')
                ->where('is_active', true)
                ->whereIn('customer_type', [$type, 'Default'])
                ->orderByRaw("case when customer_type=? then 0 else 1 end", [$type])
                ->get();
            $out = $rules->map(function ($r) {
                return [
                    'document_type_id' => $r->document_type_id,
                    'document_type' => optional($r->documentType)->name,
                    'is_required' => (bool) $r->is_required,
                    'expires_in_days' => $r->expires_in_days,
                ];
            });
            return response()->json(['data' => $out]);
        })->name('api.me.kyb.checklist');
    });

    // Admin CMS CRUD
    Route::prefix('admin')->middleware(['role:Admin'])->group(function () {
        Route::get('/cms', function () {
            return Inertia::render('Admin/Cms');
        })->name('admin.cms');

        Route::get('/api/cms', function (Request $request) {
            $q = \App\Models\CmsBlock::query();
            if ($locale = $request->query('locale'))
                $q->where('locale', $locale);
            return response()->json($q->orderBy('key')->paginate(20));
        })->name('admin.api.cms.index');

        Route::post('/api/cms', function (Request $request) {
            $data = $request->validate([
                'key' => ['required', 'string', 'max:120'],
                'locale' => ['nullable', 'string', 'max:10'],
                'title' => ['nullable', 'string', 'max:190'],
                'body' => ['nullable', 'string'],
                'cta_text' => ['nullable', 'string', 'max:120'],
                'cta_href' => ['nullable', 'string', 'max:190'],
                'image_url' => ['nullable', 'string', 'max:190'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $data['locale'] = $data['locale'] ?? app()->getLocale();
            $data['is_active'] = (bool) ($data['is_active'] ?? true);
            $row = \App\Models\CmsBlock::create($data);
            return response()->json($row, 201);
        })->name('admin.api.cms.store');

        Route::put('/api/cms/{id}', function (Request $request, $id) {
            $row = \App\Models\CmsBlock::findOrFail($id);
            $data = $request->validate([
                'title' => ['nullable', 'string', 'max:190'],
                'body' => ['nullable', 'string'],
                'cta_text' => ['nullable', 'string', 'max:120'],
                'cta_href' => ['nullable', 'string', 'max:190'],
                'image_url' => ['nullable', 'string', 'max:190'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $row->update($data);
            return response()->json(['ok' => true]);
        })->name('admin.api.cms.update');

        Route::delete('/api/cms/{id}', function ($id) {
            \App\Models\CmsBlock::where('id', $id)->delete();
            return response()->json(['ok' => true]);
        })->name('admin.api.cms.delete');

        // Admin Supplier Review
        Route::get('/suppliers', [\App\Http\Controllers\Admin\SupplierReviewController::class, 'index'])->name('admin.suppliers.index');
        Route::get('/suppliers/{supplier}', [\App\Http\Controllers\Admin\SupplierReviewController::class, 'show'])->name('admin.suppliers.show');
        Route::post('/suppliers/{supplier}/status', [\App\Http\Controllers\Admin\SupplierReviewController::class, 'updateStatus'])->name('admin.suppliers.status.update');

        // Admin KYB Checklist CRUD
        Route::get('/kyb-checklist', function () {
            return Inertia::render('Admin/KybChecklist');
        })->name('admin.kyb.checklist');

        Route::get('/api/kyb-checklist', function (Request $request) {
            $q = \App\Models\KybChecklist::query()->with('documentType');
            if ($ct = $request->query('customer_type'))
                $q->where('customer_type', $ct);
            return response()->json($q->orderBy('customer_type')->orderBy('document_type_id')->paginate(50));
        })->name('admin.api.kyb_checklist.index');

        Route::post('/api/kyb-checklist', function (Request $request) {
            $data = $request->validate([
                'customer_type' => ['required', 'string', 'max:120'],
                'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
                'is_required' => ['nullable', 'boolean'],
                'expires_in_days' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $row = \App\Models\KybChecklist::create([
                'customer_type' => $data['customer_type'],
                'document_type_id' => $data['document_type_id'],
                'is_required' => (bool) ($data['is_required'] ?? true),
                'expires_in_days' => $data['expires_in_days'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
            return response()->json($row, 201);
        })->name('admin.api.kyb_checklist.store');

        Route::put('/api/kyb-checklist/{id}', function (Request $request, $id) {
            $row = \App\Models\KybChecklist::findOrFail($id);
            $data = $request->validate([
                'is_required' => ['nullable', 'boolean'],
                'expires_in_days' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $row->update($data);
            return response()->json(['ok' => true]);
        })->name('admin.api.kyb_checklist.update');

        Route::delete('/api/kyb-checklist/{id}', function ($id) {
            \App\Models\KybChecklist::where('id', $id)->delete();
            return response()->json(['ok' => true]);
        })->name('admin.api.kyb_checklist.delete');

        // Admin Pricing Rules CRUD
        Route::get('/pricing-rules', function () {
            return Inertia::render('Admin/PricingRules');
        })->name('admin.pricing.rules');
        Route::get('/api/pricing-rules', function () {
            return response()->json(\App\Models\PricingRule::orderBy('tenor_min')->paginate(50));
        })->name('admin.api.pricing_rules.index');
        Route::post('/api/pricing-rules', function (Request $request) {
            $data = $request->validate([
                'tenor_min' => ['required', 'integer', 'min:0'],
                'tenor_max' => ['required', 'integer', 'gte:tenor_min'],
                'amount_min' => ['required', 'numeric', 'min:0'],
                'amount_max' => ['required', 'numeric', 'gte:amount_min'],
                'base_rate' => ['required', 'numeric'],
                'vip_adjust' => ['nullable', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $row = \App\Models\PricingRule::create($data + ['is_active' => (bool) ($data['is_active'] ?? true)]);
            return response()->json($row, 201);
        })->name('admin.api.pricing_rules.store');
        Route::put('/api/pricing-rules/{id}', function (Request $request, $id) {
            $row = \App\Models\PricingRule::findOrFail($id);
            $data = $request->validate([
                'tenor_min' => ['nullable', 'integer', 'min:0'],
                'tenor_max' => ['nullable', 'integer'],
                'amount_min' => ['nullable', 'numeric', 'min:0'],
                'amount_max' => ['nullable', 'numeric'],
                'base_rate' => ['nullable', 'numeric'],
                'vip_adjust' => ['nullable', 'numeric'],
                'is_active' => ['nullable', 'boolean'],
            ]);
            $row->update($data);
            return response()->json(['ok' => true]);
        })->name('admin.api.pricing_rules.update');
        Route::delete('/api/pricing-rules/{id}', function ($id) {
            \App\Models\PricingRule::where('id', $id)->delete();
            return response()->json(['ok' => true]);
        })->name('admin.api.pricing_rules.delete');

        // Admin Leads list + export
        Route::get('/leads', function () {
            return Inertia::render('Admin/Leads');
        })->name('admin.leads');
        Route::get('/api/leads', function (Request $request) {
            $q = \App\Models\Lead::query();
            if ($s = $request->query('search')) {
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%$s%");
                    $qq->orWhere('email', 'like', "%$s%");
                    $qq->orWhere('company', 'like', "%$s%");
                });
            }
            return response()->json($q->orderByDesc('id')->paginate(20));
        })->name('admin.api.leads.index');
        Route::get('/api/leads/export', function () {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="leads.csv"',
            ];
            $callback = function () {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['id', 'name', 'email', 'phone', 'company', 'created_at']);
                \App\Models\Lead::orderByDesc('id')->chunk(1000, function ($rows) use ($out) {
                    foreach ($rows as $r)
                        fputcsv($out, [$r->id, $r->name, $r->email, $r->phone, $r->company, $r->created_at]);
                });
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        })->name('admin.api.leads.export');

        // Admin Agreement Templates (versions + effective dates)
        Route::get('/agreements/templates', function () {
            return Inertia::render('Admin/AgreementTemplates');
        })->name('admin.agreements.templates');
        Route::get('/api/agreements/templates', function () {
            return response()->json(\App\Models\AgreementTemplate::orderByDesc('id')->paginate(50));
        })->name('admin.api.agreements.templates.index');
        Route::post('/api/agreements/templates', function (Request $request) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:190'],
                'version' => ['required', 'string', 'max:50'],
                'effective_from' => ['nullable', 'date'],
                'effective_to' => ['nullable', 'date', 'after:effective_from'],
                'content' => ['required', 'string'],
            ]);
            $row = \App\Models\AgreementTemplate::create($data);
            return response()->json($row, 201);
        })->name('admin.api.agreements.templates.store');
        Route::put('/api/agreements/templates/{id}', function (Request $request, $id) {
            $row = \App\Models\AgreementTemplate::findOrFail($id);
            $data = $request->validate([
                'name' => ['nullable', 'string', 'max:190'],
                'version' => ['nullable', 'string', 'max:50'],
                'effective_from' => ['nullable', 'date'],
                'effective_to' => ['nullable', 'date', 'after:effective_from'],
                'content' => ['nullable', 'string'],
            ]);
            $row->update($data);
            return response()->json(['ok' => true]);
        })->name('admin.api.agreements.templates.update');
        Route::delete('/api/agreements/templates/{id}', function ($id) {
            \App\Models\AgreementTemplate::where('id', $id)->delete();
            return response()->json(['ok' => true]);
        })->name('admin.api.agreements.templates.delete');

        // Document Requests per supplier
        Route::get('/doc-requests', function () {
            return Inertia::render('Admin/DocRequests');
        })->name('admin.doc_requests');
        Route::get('/api/doc-requests', function (Request $request) {
            $q = \App\Models\RequestedDocument::query();
            if ($sid = $request->query('supplier_id'))
                $q->where('supplier_id', $sid);
            return response()->json($q->orderByDesc('id')->paginate(50));
        })->name('admin.api.doc_requests.index');
        Route::post('/api/doc-requests', function (Request $request) {
            $data = $request->validate([
                'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
                'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
                'note' => ['nullable', 'string', 'max:190'],
            ]);
            $row = \App\Models\RequestedDocument::create($data + [
                'requested_by' => auth()->id(),
                'requested_at' => now(),
            ]);
            return response()->json($row, 201);
        })->name('admin.api.doc_requests.store');
        Route::post('/api/doc-requests/{id}/resolve', function ($id) {
            $row = \App\Models\RequestedDocument::findOrFail($id);
            $row->resolved_at = now();
            $row->save();
            return response()->json(['ok' => true]);
        })->name('admin.api.doc_requests.resolve');

        // Admin Banking
        Route::get('/admin/bank', [\App\Http\Controllers\Admin\BankController::class, 'index'])->name('admin.bank');
        Route::put('/admin/api/bank/{id}', [\App\Http\Controllers\Admin\BankController::class, 'update'])->name('admin.api.bank.update');

        // Admin Funding Logs
        Route::get('/admin/funding-logs', [\App\Http\Controllers\Admin\FundingLogsController::class, 'index'])->name('admin.funding-logs');
        Route::post('/admin/api/funding-logs', [\App\Http\Controllers\Admin\FundingLogsController::class, 'store'])->name('admin.api.funding-logs');
        Route::get('/admin/api/funding-logs/export', [\App\Http\Controllers\Admin\FundingLogsController::class, 'export'])->name('admin.api.funding-logs.export');

        // Admin Audit Log
        Route::get('/admin/audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-log');
        Route::get('/admin/api/audit-log/{id}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('admin.api.audit-log.show');
        Route::get('/admin/api/audit-log/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('admin.api.audit-log.export');

        // Buyers CRUD
        Route::get('/admin/buyers', [\App\Http\Controllers\Admin\BuyerController::class, 'index'])->name('admin.buyers');
        Route::get('/admin/api/buyers', function (Request $request) {
            $query = \App\Models\Buyer::query();
            if ($search = $request->query('search')) {
                $query->where('name', 'like', "%{$search}%");
            }
            if ($grade = $request->query('risk_grade')) {
                $query->where('risk_grade', $grade);
            }
            return response()->json($query->orderBy('name')->paginate(20));
        })->name('admin.api.buyers.index');
        Route::post('/admin/api/buyers', [\App\Http\Controllers\Admin\BuyerController::class, 'store'])->name('admin.api.buyers.store');
        Route::put('/admin/api/buyers/{buyer}', [\App\Http\Controllers\Admin\BuyerController::class, 'update'])->name('admin.api.buyers.update');
        Route::delete('/admin/api/buyers/{buyer}', [\App\Http\Controllers\Admin\BuyerController::class, 'destroy'])->name('admin.api.buyers.delete');

        // Risk Grades CRUD
        Route::get('/admin/risk-grades', [\App\Http\Controllers\Admin\RiskGradeController::class, 'index'])->name('admin.risk-grades');
        Route::get('/admin/api/risk-grades', function () {
            return response()->json(\App\Models\RiskGrade::orderBy('sort_order')->orderBy('code')->paginate(50));
        })->name('admin.api.risk-grades.index');
        Route::post('/admin/api/risk-grades', [\App\Http\Controllers\Admin\RiskGradeController::class, 'store'])->name('admin.api.risk-grades.store');
        Route::put('/admin/api/risk-grades/{riskGrade}', [\App\Http\Controllers\Admin\RiskGradeController::class, 'update'])->name('admin.api.risk-grades.update');
        Route::delete('/admin/api/risk-grades/{riskGrade}', [\App\Http\Controllers\Admin\RiskGradeController::class, 'destroy'])->name('admin.api.risk-grades.delete');

        // Invoice Review Queue
        Route::get('/admin/invoice-review', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'index'])->name('admin.invoice-review');
        Route::get('/admin/api/invoice-review/queue', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'queue'])->name('admin.api.invoice-review.queue');
        Route::post('/admin/api/invoice-review/{invoice}/claim', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'claim'])->name('admin.api.invoice-review.claim');
        Route::post('/admin/api/invoice-review/{invoice}/approve', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'approve'])->name('admin.api.invoice-review.approve');
        Route::post('/admin/api/invoice-review/{invoice}/reject', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'reject'])->name('admin.api.invoice-review.reject');
        Route::post('/admin/api/invoice-review/{invoice}/dispute-note', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'addDisputeNote'])->name('admin.api.invoice-review.dispute-note');
        Route::post('/admin/api/invoice-review/{invoice}/write-off', [\App\Http\Controllers\Admin\InvoiceReviewController::class, 'writeOff'])->name('admin.api.invoice-review.write-off');

        // User/Role Management
        Route::get('/admin/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('admin.users');
        Route::get('/admin/api/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'users'])->name('admin.api.users.index');
        Route::get('/admin/api/users/roles', [\App\Http\Controllers\Admin\UserManagementController::class, 'roles'])->name('admin.api.users.roles');
        Route::get('/admin/api/users/permissions', [\App\Http\Controllers\Admin\UserManagementController::class, 'permissions'])->name('admin.api.users.permissions');
        Route::post('/admin/api/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('admin.api.users.store');
        Route::put('/admin/api/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('admin.api.users.update');
        Route::delete('/admin/api/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('admin.api.users.delete');

        // Exports
        Route::get('/admin/api/exports/invoices', function (Request $request) {
            $service = new \App\Services\ExportService();
            $format = $request->query('format', 'excel');
            $filters = $request->only(['status', 'supplier_id', 'buyer_id', 'from_date', 'to_date']);
            $filepath = $service->exportInvoices($filters, $format);
            return response()->download(storage_path('app/public/' . $filepath));
        })->name('admin.api.exports.invoices');
        Route::get('/admin/api/exports/fundings', function (Request $request) {
            $service = new \App\Services\ExportService();
            $format = $request->query('format', 'excel');
            $filters = $request->only(['status', 'from_date', 'to_date']);
            $filepath = $service->exportFundings($filters, $format);
            return response()->download(storage_path('app/public/' . $filepath));
        })->name('admin.api.exports.fundings');
        Route::get('/admin/api/exports/repayments', function (Request $request) {
            $service = new \App\Services\ExportService();
            $format = $request->query('format', 'excel');
            $filters = $request->only(['buyer_id', 'from_date', 'to_date']);
            $filepath = $service->exportRepayments($filters, $format);
            return response()->download(storage_path('app/public/' . $filepath));
        })->name('admin.api.exports.repayments');

        // Supplier Statements
        Route::get('/admin/api/statements/{supplier}', function (\App\Models\Supplier $supplier, Request $request) {
            $service = new \App\Services\StatementGeneratorService();
            $from = $request->date('from');
            $to = $request->date('to');
            $filepath = $service->generateStatement($supplier, $from, $to);
            return response()->download(storage_path('app/public/' . $filepath));
        })->name('admin.api.statements.generate');
    });
});

// Health Check (public)
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check'])->name('health.check');

require __DIR__ . '/auth.php';
