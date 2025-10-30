<p>تنبيه: وثيقتك تقترب من انتهاء الصلاحية.</p>
<p>معرّف الوثيقة: <?= $document->id ?></p>
<p>تاريخ الانتهاء: <?= optional($document->expiry_at)->toDateString() ?></p>

